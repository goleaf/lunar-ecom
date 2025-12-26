@php
    $badgeModel = $badge ?? null;
    $startsAtValue = old('starts_at', $badgeModel && $badgeModel->starts_at ? $badgeModel->starts_at->format('Y-m-d\\TH:i') : '');
    $endsAtValue = old('ends_at', $badgeModel && $badgeModel->ends_at ? $badgeModel->ends_at->format('Y-m-d\\TH:i') : '');
    $typeValue = old('type', $badgeModel->type ?? 'new');
    $positionValue = old('position', $badgeModel->position ?? 'top-left');
    $styleValue = old('style', $badgeModel->style ?? 'rounded');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Badge name</label>
            <input type="text" name="name" value="{{ old('name', $badgeModel->name ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Handle</label>
            <input type="text" name="handle" value="{{ old('handle', $badgeModel->handle ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
            <select name="type" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="new" {{ $typeValue === 'new' ? 'selected' : '' }}>New</option>
                <option value="sale" {{ $typeValue === 'sale' ? 'selected' : '' }}>Sale</option>
                <option value="hot" {{ $typeValue === 'hot' ? 'selected' : '' }}>Hot</option>
                <option value="limited" {{ $typeValue === 'limited' ? 'selected' : '' }}>Limited</option>
                <option value="exclusive" {{ $typeValue === 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                <option value="custom" {{ $typeValue === 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Label</label>
            <input type="text" name="label" value="{{ old('label', $badgeModel->label ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full rounded border border-slate-300 px-3 py-2">{{ old('description', $badgeModel->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Text color</label>
            <input type="text" name="color" value="{{ old('color', $badgeModel->color ?? '#0f172a') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Background color</label>
            <input type="text" name="background_color" value="{{ old('background_color', $badgeModel->background_color ?? '#e2e8f0') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Border color</label>
            <input type="text" name="border_color" value="{{ old('border_color', $badgeModel->border_color ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Position</label>
            <select name="position" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="top-left" {{ $positionValue === 'top-left' ? 'selected' : '' }}>Top left</option>
                <option value="top-right" {{ $positionValue === 'top-right' ? 'selected' : '' }}>Top right</option>
                <option value="bottom-left" {{ $positionValue === 'bottom-left' ? 'selected' : '' }}>Bottom left</option>
                <option value="bottom-right" {{ $positionValue === 'bottom-right' ? 'selected' : '' }}>Bottom right</option>
                <option value="center" {{ $positionValue === 'center' ? 'selected' : '' }}>Center</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Style</label>
            <select name="style" class="w-full rounded border border-slate-300 px-3 py-2">
                <option value="rounded" {{ $styleValue === 'rounded' ? 'selected' : '' }}>Rounded</option>
                <option value="square" {{ $styleValue === 'square' ? 'selected' : '' }}>Square</option>
                <option value="pill" {{ $styleValue === 'pill' ? 'selected' : '' }}>Pill</option>
                <option value="custom" {{ $styleValue === 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
            <input type="number" name="priority" value="{{ old('priority', $badgeModel->priority ?? 0) }}" class="w-full rounded border border-slate-300 px-3 py-2" min="0" max="100">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Font size</label>
            <input type="number" name="font_size" value="{{ old('font_size', $badgeModel->font_size ?? 12) }}" class="w-full rounded border border-slate-300 px-3 py-2" min="8" max="24">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Padding X</label>
            <input type="number" name="padding_x" value="{{ old('padding_x', $badgeModel->padding_x ?? 8) }}" class="w-full rounded border border-slate-300 px-3 py-2" min="0" max="20">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Padding Y</label>
            <input type="number" name="padding_y" value="{{ old('padding_y', $badgeModel->padding_y ?? 4) }}" class="w-full rounded border border-slate-300 px-3 py-2" min="0" max="20">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Border radius</label>
            <input type="number" name="border_radius" value="{{ old('border_radius', $badgeModel->border_radius ?? 8) }}" class="w-full rounded border border-slate-300 px-3 py-2" min="0" max="50">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Icon</label>
            <input type="text" name="icon" value="{{ old('icon', $badgeModel->icon ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="e.g. star">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Animation type</label>
            <input type="text" name="animation_type" value="{{ old('animation_type', $badgeModel->animation_type ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2" placeholder="e.g. pulse">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Max display count</label>
            <input type="number" name="max_display_count" value="{{ old('max_display_count', $badgeModel->max_display_count ?? '') }}" class="w-full rounded border border-slate-300 px-3 py-2" min="1">
        </div>
    </div>

    <div class="flex flex-wrap gap-6">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="show_icon" value="0">
            <input type="checkbox" name="show_icon" value="1" class="rounded" {{ old('show_icon', $badgeModel->show_icon ?? false) ? 'checked' : '' }}>
            Show icon
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="animated" value="0">
            <input type="checkbox" name="animated" value="1" class="rounded" {{ old('animated', $badgeModel->animated ?? false) ? 'checked' : '' }}>
            Animated
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="auto_assign" value="0">
            <input type="checkbox" name="auto_assign" value="1" class="rounded" {{ old('auto_assign', $badgeModel->auto_assign ?? false) ? 'checked' : '' }}>
            Auto assign
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', $badgeModel->is_active ?? true) ? 'checked' : '' }}>
            Active
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Starts at</label>
            <input type="datetime-local" name="starts_at" value="{{ $startsAtValue }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Ends at</label>
            <input type="datetime-local" name="ends_at" value="{{ $endsAtValue }}" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save badge</button>
    </div>
</div>
