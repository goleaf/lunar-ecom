@extends('admin.layout')

@section('title', 'Inventory Levels')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Inventory levels</h2>
            <p class="text-sm text-slate-600">Track quantity, availability, and warehouse status.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-lg font-semibold">Quick adjust</h3>
        <form id="inventory-adjust-form" class="grid grid-cols-1 md:grid-cols-4 gap-4" data-url="{{ route('admin.inventory.adjust') }}">
            @csrf
            <div>
                <label class="block text-xs text-slate-600 mb-1">Variant ID</label>
                <input type="number" name="product_variant_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Warehouse</label>
                <select name="warehouse_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
                    <option value="">Select</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Quantity delta</label>
                <input type="number" name="quantity" class="w-full rounded border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-xs text-slate-600 mb-1">Note</label>
                <input type="text" name="note" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-4 flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded">Adjust inventory</button>
                <span id="inventory-adjust-message" class="text-sm text-slate-600"></span>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Product</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">SKU</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Warehouse</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">On hand</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Available</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Reserved</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($inventoryLevels as $level)
                    @php
                        $productName = $level->productVariant && $level->productVariant->product
                            ? $level->productVariant->product->translateAttribute('name')
                            : 'Unknown product';
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $productName }}</div>
                            <div class="text-xs text-slate-500">Variant #{{ $level->product_variant_id }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $level->productVariant?->sku ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $level->warehouse?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ $level->quantity }}</td>
                        <td class="px-4 py-3">{{ $level->available_quantity }}</td>
                        <td class="px-4 py-3">{{ $level->reserved_quantity }}</td>
                        <td class="px-4 py-3">{{ ucfirst($level->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">No inventory levels found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $inventoryLevels->links() }}
    </div>
</div>

@push('scripts')
@endpush
@endsection
