@extends('admin.layout')

@section('title', 'Product Customizations')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-semibold">Customizations for {{ $product->translateAttribute('name') }}</h2>
        <p class="text-sm text-slate-600">Create custom fields for buyers to personalize products.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Add customization</h3>
        <form id="customization-form" class="grid grid-cols-1 md:grid-cols-4 gap-4" data-url="{{ route('admin.products.customizations.store', $product) }}">
            @csrf
            <div>
                <label class="block text-xs text-slate-600 mb-1">Type</label>
                <select name="customization_type" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                    <option value="text">Text</option>
                    <option value="image">Image</option>
                    <option value="option">Option</option>
                    <option value="color">Color</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Field name</label>
                <input type="text" name="field_name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Label</label>
                <input type="text" name="field_label" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Price modifier type</label>
                <select name="price_modifier_type" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                    <option value="fixed">Fixed</option>
                    <option value="per_character">Per character</option>
                    <option value="per_image">Per image</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Price modifier</label>
                <input type="number" step="0.01" name="price_modifier" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Display order</label>
                <input type="number" name="display_order" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" min="0">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs text-slate-600 mb-1">Description</label>
                <input type="text" name="description" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-4 flex flex-wrap gap-6">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_required" value="0">
                    <input type="checkbox" name="is_required" value="1" class="rounded">
                    Required
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded" checked>
                    Active
                </label>
            </div>
            <div class="md:col-span-4 flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Add customization</button>
                <span id="customization-message" class="text-sm text-slate-600"></span>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Label</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Required</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Active</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($customizations as $customization)
                    <tr>
                        <td class="px-4 py-3">{{ $customization->field_label }}</td>
                        <td class="px-4 py-3">{{ ucfirst($customization->customization_type) }}</td>
                        <td class="px-4 py-3">{{ $customization->is_required ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3">{{ $customization->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3">
                            <button class="text-red-600 hover:underline customization-delete" data-url="{{ route('admin.products.customizations.destroy', ['product' => $product->id, 'customization' => $customization->id]) }}">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No customizations defined.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-sm text-slate-600">
        <a href="{{ route('admin.products.customizations.examples', $product) }}" class="text-blue-600 hover:underline">Manage customization examples</a>
    </div>
</div>

@push('scripts')
@endpush
@endsection
