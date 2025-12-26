@php
    $ruleModel = $rule ?? null;
    $startsAtValue = old('starts_at', $ruleModel && $ruleModel->starts_at ? $ruleModel->starts_at->format('Y-m-d\\TH:i') : '');
    $expiresAtValue = old('expires_at', $ruleModel && $ruleModel->expires_at ? $ruleModel->expires_at->format('Y-m-d\\TH:i') : '');
    $conditionTypeValue = old('condition_type', $ruleModel->condition_type ?? 'manual');
    $badgeIdValue = old('badge_id', $ruleModel->badge_id ?? '');
    $ruleConditions = $ruleModel->conditions ?? [];
    $conditionField = old('conditions.0.field', $ruleConditions[0]['field'] ?? '');
    $conditionOperator = old('conditions.0.operator', $ruleConditions[0]['operator'] ?? '=');
    $conditionValue = old('conditions.0.value', $ruleConditions[0]['value'] ?? '');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Badge</label>
            <select name="badge_id" class="w-full rounded border border-slate-300 px-3 py-2" required>
                <option value="">Select a badge</option>
                @foreach($badges as $badge)
                    <option value="{{ $badge->id }}" {{ (string) $badgeIdValue === (string) $badge->id ? 'selected' : '' }}>
                        {{ $badge->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Condition type</label>
            <select name="condition_type" class="w-full rounded border border-slate-300 px-3 py-2" required>
                <option value="manual" {{ $conditionTypeValue === 'manual' ? 'selected' : '' }}>Manual</option>
                <option value="automatic" {{ $conditionTypeValue === 'automatic' ? 'selected' : '' }}>Automatic</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Rule name</label>
            <input type="text" name="name" value="{{ old('name', $ruleModel->name ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
            <input type="number" name="priority" value="{{ old('priority', $ruleModel->priority ?? 0) }}" class="w-full rounded border border-slate-300 px-3 py-2" min="0" max="100">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('description', $ruleModel->description ?? '') }}</textarea>
    </div>

    <div class="bg-slate-50 border border-slate-200 rounded p-4">
        <h4 class="text-sm font-semibold text-slate-700 mb-3">Primary condition</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Field</label>
                <input type="text" name="conditions[0][field]" value="{{ $conditionField }}" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="e.g. price">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Operator</label>
                <select name="conditions[0][operator]" class="w-full rounded border border-slate-300 px-3 py-2">
                    <option value="=" {{ $conditionOperator === '=' ? 'selected' : '' }}>=</option>
                    <option value=">" {{ $conditionOperator === '>' ? 'selected' : '' }}>&gt;</option>
                    <option value=">=" {{ $conditionOperator === '>=' ? 'selected' : '' }}>&gt;=</option>
                    <option value="<" {{ $conditionOperator === '<' ? 'selected' : '' }}>&lt;</option>
                    <option value="<=" {{ $conditionOperator === '<=' ? 'selected' : '' }}>&lt;=</option>
                    <option value="contains" {{ $conditionOperator === 'contains' ? 'selected' : '' }}>contains</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Value</label>
                <input type="text" name="conditions[0][value]" value="{{ $conditionValue }}" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="e.g. 100">
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-6">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', $ruleModel->is_active ?? true) ? 'checked' : '' }}>
            Active
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Starts at</label>
            <input type="datetime-local" name="starts_at" value="{{ $startsAtValue }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Expires at</label>
            <input type="datetime-local" name="expires_at" value="{{ $expiresAtValue }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save rule</button>
    </div>
</div>
