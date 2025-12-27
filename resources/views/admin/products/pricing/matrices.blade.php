@extends('admin.layout')

@section('title', 'Pricing Matrices')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Pricing matrices</h2>
            <p class="text-sm text-slate-600">Product: {{ $product->translateAttribute('name') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.products.pricing.import.index', $product) }}" class="px-4 py-2 text-sm border border-slate-300 rounded hover:bg-slate-50">Import pricing</a>
            <a href="{{ route('admin.products.pricing.history', $product) }}" class="px-4 py-2 text-sm border border-slate-300 rounded hover:bg-slate-50">View history</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Create matrix</h3>
        <form id="matrix-create-form" class="grid grid-cols-1 md:grid-cols-4 gap-4" data-url="{{ route('admin.products.pricing.matrices.store', $product) }}" data-message-id="matrix-status">
            @csrf
            <div>
                <label class="block text-xs text-slate-600 mb-1">Matrix type</label>
                <select name="matrix_type" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                    <option value="quantity">Quantity</option>
                    <option value="customer_group">Customer group</option>
                    <option value="region">Region</option>
                    <option value="mixed">Mixed</option>
                    <option value="rule_based">Rule based</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Name</label>
                <input type="text" name="name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Priority</label>
                <input type="number" name="priority" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" min="0" max="100" value="0">
            </div>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded" checked>
                    Active
                </label>
            </div>
            <div class="md:col-span-4 flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Create matrix</button>
                <span id="matrix-status" class="text-sm text-slate-600"></span>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Matrix</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tiers</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Rules</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($matrices as $matrix)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $matrix->name ?? 'Matrix #' . $matrix->id }}</div>
                            <div class="text-xs text-slate-500">Priority {{ $matrix->priority }}</div>
                        </td>
                        <td class="px-4 py-3">{{ str_replace('_', ' ', $matrix->matrix_type) }}</td>
                        <td class="px-4 py-3">{{ $matrix->tiers?->count() ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $matrix->pricingRules?->count() ?? 0 }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $matrix->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $matrix->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No pricing matrices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
@endpush
@endsection
