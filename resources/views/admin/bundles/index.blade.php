@extends('admin.layout')

@section('title', 'Bundle Management')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Product Bundles</h1>
        <a href="{{ route('admin.bundles.create') }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Create Bundle
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.bundles.index') }}" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="border rounded px-3 py-2">
                    <option value="">All</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">
                    <input type="checkbox" name="featured" value="1" {{ request('featured') ? 'checked' : '' }}>
                    Featured Only
                </label>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Filter</button>
        </form>
    </div>

    {{-- Bundles Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pricing</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($bundles as $bundle)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium">{{ $bundle->name }}</div>
                            @if($bundle->is_featured)
                                <span class="text-xs text-yellow-600">Featured</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $bundle->sku ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            {{ $bundle->items->count() }} items
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php
                                $currency = \Lunar\Facades\Currency::getDefault();
                                $price = $bundle->calculatePrice($currency);
                            @endphp
                            {{ $currency->formatter($price) }}
                            @if($bundle->pricing_type === 'percentage')
                                <span class="text-xs text-gray-500">({{ $bundle->discount_amount }}% off)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($bundle->inventory_type === 'unlimited')
                                <span class="text-gray-500">Unlimited</span>
                            @else
                                {{ $bundle->getAvailableStock() ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $bundle->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $bundle->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.bundles.edit', $bundle) }}" class="text-blue-600 hover:underline mr-3">
                                Edit
                            </a>
                            <a href="{{ route('frontend.bundles.show', $bundle) }}" class="text-green-600 hover:underline mr-3" target="_blank">
                                View
                            </a>
                            <form method="POST" action="{{ route('admin.bundles.destroy', $bundle) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No bundles found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $bundles->links() }}
    </div>
</div>
@endsection


