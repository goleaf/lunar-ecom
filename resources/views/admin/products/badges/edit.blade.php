@extends('admin.layout')

@section('title', 'Edit Product Badge')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Edit Product Badge</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.products.badges.update', $badge) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" value="{{ old('name', $badge->name) }}" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                    <select name="type" required class="w-full border rounded px-3 py-2">
                        <option value="new" {{ $badge->type === 'new' ? 'selected' : '' }}>New</option>
                        <option value="sale" {{ $badge->type === 'sale' ? 'selected' : '' }}>Sale</option>
                        <option value="hot" {{ $badge->type === 'hot' ? 'selected' : '' }}>Hot</option>
                        <option value="limited" {{ $badge->type === 'limited' ? 'selected' : '' }}>Limited</option>
                        <option value="exclusive" {{ $badge->type === 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                        <option value="custom" {{ $badge->type === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Label (Display Text)</label>
                    <input type="text" name="label" value="{{ old('label', $badge->label) }}" class="w-full border rounded px-3 py-2" placeholder="Leave empty to use name">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                    <select name="position" required class="w-full border rounded px-3 py-2">
                        <option value="top-left" {{ $badge->position === 'top-left' ? 'selected' : '' }}>Top Left</option>
                        <option value="top-right" {{ $badge->position === 'top-right' ? 'selected' : '' }}>Top Right</option>
                        <option value="bottom-left" {{ $badge->position === 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="bottom-right" {{ $badge->position === 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                        <option value="center" {{ $badge->position === 'center' ? 'selected' : '' }}>Center</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Text Color *</label>
                    <input type="color" name="color" value="{{ old('color', $badge->color) }}" required class="w-full h-10 border rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Background Color *</label>
                    <input type="color" name="background_color" value="{{ old('background_color', $badge->background_color) }}" required class="w-full h-10 border rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Border Color</label>
                    <input type="color" name="border_color" value="{{ old('border_color', $badge->border_color) }}" class="w-full h-10 border rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Style *</label>
                    <select name="style" required class="w-full border rounded px-3 py-2">
                        <option value="rounded" {{ $badge->style === 'rounded' ? 'selected' : '' }}>Rounded</option>
                        <option value="square" {{ $badge->style === 'square' ? 'selected' : '' }}>Square</option>
                        <option value="pill" {{ $badge->style === 'pill' ? 'selected' : '' }}>Pill</option>
                        <option value="custom" {{ $badge->style === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Font Size (px) *</label>
                    <input type="number" name="font_size" value="{{ old('font_size', $badge->font_size) }}" min="8" max="24" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Padding X (px) *</label>
                    <input type="number" name="padding_x" value="{{ old('padding_x', $badge->padding_x) }}" min="0" max="32" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Padding Y (px) *</label>
                    <input type="number" name="padding_y" value="{{ old('padding_y', $badge->padding_y) }}" min="0" max="32" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Border Radius (px) *</label>
                    <input type="number" name="border_radius" value="{{ old('border_radius', $badge->border_radius) }}" min="0" max="50" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                    <input type="number" name="priority" value="{{ old('priority', $badge->priority) }}" min="0" max="100" required class="w-full border rounded px-3 py-2">
                    <p class="text-xs text-gray-500 mt-1">Higher priority badges are shown first</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon Class</label>
                    <input type="text" name="icon" value="{{ old('icon', $badge->icon) }}" class="w-full border rounded px-3 py-2" placeholder="e.g., fa fa-star">
                </div>
            </div>

            <div class="space-y-4">
                <label class="flex items-center">
                    <input type="checkbox" name="show_icon" value="1" {{ old('show_icon', $badge->show_icon) ? 'checked' : '' }} class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Show Icon</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="animated" value="1" id="animated" {{ old('animated', $badge->animated) ? 'checked' : '' }} class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Animated</span>
                </label>

                <div id="animation-options" class="{{ $badge->animated ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Animation Type</label>
                    <select name="animation_type" class="w-full border rounded px-3 py-2">
                        <option value="pulse" {{ $badge->animation_type === 'pulse' ? 'selected' : '' }}>Pulse</option>
                        <option value="bounce" {{ $badge->animation_type === 'bounce' ? 'selected' : '' }}>Bounce</option>
                        <option value="flash" {{ $badge->animation_type === 'flash' ? 'selected' : '' }}>Flash</option>
                        <option value="shake" {{ $badge->animation_type === 'shake' ? 'selected' : '' }}>Shake</option>
                    </select>
                </div>

                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $badge->is_active) ? 'checked' : '' }} class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="auto_assign" value="1" {{ old('auto_assign', $badge->auto_assign) ? 'checked' : '' }} class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Auto-Assign Based on Rules</span>
                </label>
            </div>

            <div class="border-t pt-4">
                <h3 class="text-lg font-semibold mb-4">Description</h3>
                <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description', $badge->description) }}</textarea>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Update Badge
                </button>
                <a href="{{ route('admin.products.badges.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('animated').addEventListener('change', function() {
    const animationOptions = document.getElementById('animation-options');
    if (this.checked) {
        animationOptions.classList.remove('hidden');
    } else {
        animationOptions.classList.add('hidden');
    }
});
</script>
@endpush
@endsection

