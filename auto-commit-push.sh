#!/usr/bin/env bash
# Codex CLI version: 0.73.0 (verified)
set -euo pipefail

usage() {
  cat <<'EOF'
auto-commit-push.sh

Continuously commits + pushes any local Git changes, generating commit messages
from the staged diff via Codex (using a cheaper model by default).

Usage:
  ./auto-commit-push.sh [options]

Options:
  --interval SECONDS      Sleep between checks (default: 30)
  --once                 Run at most one commit+push, then exit
  --max-commits N         Stop after N commits (default: 0 = infinite)
  --remote NAME           Remote to push to (default: origin)
  --branch NAME           Branch to push (default: current branch)
  --model NAME            Codex model (default: gpt-5.1)
  --tracked-only          Stage only tracked files (git add -u)
  --no-push               Commit but do not push
  --dry-run               Print actions without committing/pushing
  -h, --help              Show help

Environment:
  CODEX_BIN               Codex binary (default: codex)

Notes:
  - This sends staged diffs to Codex to generate a detailed commit message with file-by-file descriptions.
  - Commits are made automatically with the generated message.
  - Press Ctrl+C to stop when running in loop mode.
  - Requires Codex CLI (codex-cli) version 0.73.0 or compatible.
EOF
}

CODEX_BIN="${CODEX_BIN:-codex}"

interval_seconds=30
run_once=false
max_commits=0
remote_name="origin"
branch_name=""
model_name="gpt-5.1"
tracked_only=false
push_enabled=true
dry_run=false

while [[ $# -gt 0 ]]; do
  case "$1" in
    --interval)
      interval_seconds="${2:-}"; shift 2 ;;
    --once)
      run_once=true; shift ;;
    --max-commits)
      max_commits="${2:-}"; shift 2 ;;
    --remote)
      remote_name="${2:-}"; shift 2 ;;
    --branch)
      branch_name="${2:-}"; shift 2 ;;
    --model)
      model_name="${2:-}"; shift 2 ;;
    --tracked-only)
      tracked_only=true; shift ;;
    --no-push)
      push_enabled=false; shift ;;
    --dry-run)
      dry_run=true; shift ;;
    -h|--help)
      usage; exit 0 ;;
    *)
      echo "Unknown option: $1" >&2
      usage
      exit 2
      ;;
  esac
done

if ! command -v git >/dev/null 2>&1; then
  echo "git not found in PATH" >&2
  exit 1
fi

if ! command -v "$CODEX_BIN" >/dev/null 2>&1; then
  echo "codex not found in PATH (set CODEX_BIN if needed)" >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Not inside a Git repository: $SCRIPT_DIR" >&2
  exit 1
fi

if [[ -z "$branch_name" ]]; then
  branch_name="$(git rev-parse --abbrev-ref HEAD)"
fi

stage_all() {
  if $tracked_only; then
    git add -u
  else
    git add -A
  fi
}

sanitize_commit_subject() {
  local subject="$1"

  subject="${subject//$'\r'/}"
  subject="${subject//$'\n'/ }"
  subject="$(printf '%s' "$subject" | sed -E 's/^[[:space:]]+|[[:space:]]+$//g')"
  subject="${subject#\"}"; subject="${subject%\"}"
  subject="${subject#\'}"; subject="${subject%\'}"

  if [[ ${#subject} -gt 72 ]]; then
    subject="${subject:0:72}"
  fi

  printf '%s\n' "$subject"
}

generate_commit_message() {
  local tmp_out
  tmp_out="$(mktemp)"

  local name_status diffstat diff_patch prompt result
  name_status="$(git diff --cached --name-status --no-color)"
  diffstat="$(git diff --cached --stat --no-color)"
  diff_patch="$(git diff --cached --unified=0 --no-color | head -n 500)"

  # Get individual file diffs for detailed descriptions
  local changed_files
  changed_files="$(git diff --cached --name-only --no-color)"
  
  local file_diffs=""
  while IFS= read -r file; do
    if [[ -n "$file" ]]; then
      local file_diff
      file_diff="$(git diff --cached --unified=3 --no-color -- "$file" | head -n 100)"
      file_diffs="${file_diffs}\n\n--- File: $file ---\n${file_diff}"
    fi
  done <<< "$changed_files"

  prompt="$(cat <<EOF
Write a comprehensive Git commit message for the staged changes.

Format:
- First line: Subject line (imperative mood, max 72 chars)
- Blank line
- Detailed description paragraph explaining the overall changes
- Blank line
- For each changed file, include a bullet point describing what changed:
  * filename: description of changes
  (blank line between each file description, but NO blank line after the last file)

Rules:
- Subject line: Imperative mood (e.g., "Add …", "Fix …", "Refactor …", "Enhance …", "Update …")
- Be specific and detailed about what changed in each file
- Maximum detailed information about all changes
- Use clear, concise language
- Add one blank line between each file bullet point, but do NOT add a blank line after the last file

Changed files (name-status):
$name_status

Diffstat:
$diffstat

File-by-file diffs:
$file_diffs
EOF
)"

  if "$CODEX_BIN" exec \
    --model "$model_name" \
    --sandbox read-only \
    --color never \
    --output-last-message "$tmp_out" \
    -c model_reasoning_effort="low" \
    - <<<"$prompt" >/dev/null 2>&1; then
    result="$(cat "$tmp_out")"
  else
    result=""
  fi

  rm -f "$tmp_out"

  # Clean up the result but preserve multi-line structure
  result="${result//$'\r'/}"
  result="$(printf '%s' "$result" | sed -E 's/^[[:space:]]+|[[:space:]]+$//g')"
  
  # Normalize spacing: ensure one blank line between file entries (lines starting with *)
  # but remove trailing blank lines
  result="$(printf '%s' "$result" | awk '
    /^[*]/ {
      if (prev_was_file && prev_line != "") print ""
      print $0
      prev_was_file = 1
      prev_line = $0
      next
    }
    {
      if (prev_was_file && prev_line != "") {
        prev_was_file = 0
      }
      print $0
      prev_line = $0
    }
    END {
      # Remove trailing blank lines
    }
  ' | sed -E ':a; /^\n*$/{$d;N;ba}')"
  
  if [[ -z "$result" ]]; then
    result="Update project files

Changes:
$name_status"
  fi

  printf '%s\n' "$result"
}

push_current_branch() {
  if ! $push_enabled; then
    return 0
  fi

  if git rev-parse --abbrev-ref --symbolic-full-name "@{upstream}" >/dev/null 2>&1; then
    git push "$remote_name" "$branch_name"
  else
    git push -u "$remote_name" "$branch_name"
  fi
}

commits_done=0

while :; do
  if git diff --name-only --diff-filter=U --no-color | grep -q .; then
    echo "Merge conflicts detected; resolve them before auto-committing." >&2
    exit 1
  fi

  if [[ -z "$(git status --porcelain)" ]]; then
    if $run_once; then
      exit 0
    fi
    sleep "$interval_seconds"
    continue
  fi

  stage_all

  if git diff --cached --quiet; then
    if $run_once; then
      exit 0
    fi
    sleep "$interval_seconds"
    continue
  fi

  msg="$(generate_commit_message)"
  echo "Generated commit message:"
  echo "$msg"
  echo ""

  if ! $dry_run; then
    # Create temporary file with commit message
    commit_msg_file="$(mktemp)"
    printf '%s\n' "$msg" > "$commit_msg_file"
    
    # Use git commit -F to commit automatically with the generated message
    git commit -F "$commit_msg_file"
    
    rm -f "$commit_msg_file"
    push_current_branch
  fi

  commits_done=$((commits_done + 1))
  if [[ "$max_commits" -gt 0 && "$commits_done" -ge "$max_commits" ]]; then
    exit 0
  fi

  if $run_once; then
    exit 0
  fi

  sleep "$interval_seconds"
done
