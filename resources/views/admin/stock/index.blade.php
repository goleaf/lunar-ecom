@extends('admin.layout')

@section('title', 'Stock Management')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Stock Management</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.stock.statistics') }}" class="px-4 py-2 bg-blue-500 text-white rounded">
                Statistics
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.stock.index') }}" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Warehouse</label>
                <select name="warehouse_id" class="border rounded px-3 py-2">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="border rounded px-3 py-2">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                    <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Filter</button>
        </form>
    </div>

    {{-- Low Stock Alerts --}}
    @if($lowStockAlerts->count() > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold mb-2">Low Stock Alerts ({{ $lowStockAlerts->count() }})</h2>
            <div class="space-y-2">
                @foreach($lowStockAlerts->take(5) as $alert)
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium">{{ $alert->productVariant->product->translateAttribute('name') }}</span>
                            <span class="text-sm text-gray-600">
                                - {{ $alert->current_quantity }} / {{ $alert->reorder_point }} ({{ $alert->warehouse->name }})
                            </span>
                        </div>
                        <a href="{{ route('admin.stock.show', $alert->productVariant) }}" class="text-blue-600 hover:underline">
                            View Details
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Inventory Levels Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warehouse</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reserved</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($inventoryLevels as $level)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $level->productVariant->product->translateAttribute('name') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $level->productVariant->sku }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $level->warehouse->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium">{{ $level->available_quantity }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $level->reserved_quantity }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $level->quantity }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'in_stock' => 'bg-green-100 text-green-800',
                                    'low_stock' => 'bg-yellow-100 text-yellow-800',
                                    'out_of_stock' => 'bg-red-100 text-red-800',
                                ];
                                $color = $statusColors[$level->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $color }}">
                                {{ ucfirst(str_replace('_', ' ', $level->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.stock.show', $level->productVariant) }}" class="text-blue-600 hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No inventory levels found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $inventoryLevels->links() }}
    </div>
</div>
@endsection

