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
  - This sends a truncated staged diff to Codex to generate the commit message.
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
  diff_patch="$(git diff --cached --unified=0 --no-color | head -n 200)"

  prompt="$(cat <<EOF
Write a Git commit subject line for the staged changes.

Rules:
- Output ONLY the subject line.
- Imperative mood (e.g., "Add …", "Fix …", "Refactor …", "Enhance …", "Update …").
- Maximum detailed inforamtion about all changes
- Be specific (mention the main area if it helps), but keep it short.

Changed files (name-status):
$name_status

Diffstat:
$diffstat

Diff (truncated):
$diff_patch
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

  result="$(sanitize_commit_subject "$result")"
  if [[ -z "$result" ]]; then
    result="Update project files"
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
  echo "Commit: $msg"

  if ! $dry_run; then
    git commit -m "$msg"
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
