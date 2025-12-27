@extends('admin.layout')

@section('title', 'Edit Bundle')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Edit Bundle</h1>
                <p class="text-sm text-gray-600">ID: {{ $bundle->id }} · SKU: {{ $bundle->sku ?? 'N/A' }}</p>
            </div>
            <a href="{{ route('admin.bundles.index') }}" class="text-sm text-blue-600 hover:underline">Back to Bundles</a>
        </div>

        <div id="formErrors" class="hidden mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-700 text-sm"></div>

        <form id="bundleForm"
              data-mode="edit"
              data-submit-url="{{ route('admin.bundles.update', $bundle) }}"
              data-method="PUT"
              data-redirect-url="{{ route('admin.bundles.index') }}"
              data-existing='@json($bundle->load(["items","prices"]))'
              class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Product ID <span class="text-red-500">*</span></label>
                    <input type="number" name="product_id" class="w-full border rounded px-3 py-2" value="{{ $bundle->product_id }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">SKU</label>
                    <input type="text" name="sku" class="w-full border rounded px-3 py-2" value="{{ $bundle->sku }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" value="{{ $bundle->name }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Slug</label>
                    <input type="text" name="slug" class="w-full border rounded px-3 py-2" value="{{ $bundle->slug }}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ $bundle->description }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Pricing Type</label>
                    <select name="pricing_type" class="w-full border rounded px-3 py-2">
                        <option value="fixed" @selected($bundle->pricing_type === 'fixed')>Fixed</option>
                        <option value="percentage" @selected($bundle->pricing_type === 'percentage')>Percentage Discount</option>
                        <option value="dynamic" @selected($bundle->pricing_type === 'dynamic')>Dynamic</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Discount Amount</label>
                    <input type="number" name="discount_amount" class="w-full border rounded px-3 py-2" value="{{ $bundle->discount_amount }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Bundle Price (cents)</label>
                    <input type="number" name="bundle_price" class="w-full border rounded px-3 py-2" value="{{ $bundle->bundle_price }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Inventory Type</label>
                    <select name="inventory_type" class="w-full border rounded px-3 py-2">
                        <option value="component" @selected($bundle->inventory_type === 'component')>Component</option>
                        <option value="independent" @selected($bundle->inventory_type === 'independent')>Independent</option>
                        <option value="unlimited" @selected($bundle->inventory_type === 'unlimited')>Unlimited</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Stock</label>
                    <input type="number" name="stock" class="w-full border rounded px-3 py-2" value="{{ $bundle->stock }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Min Quantity</label>
                    <input type="number" name="min_quantity" class="w-full border rounded px-3 py-2" value="{{ $bundle->min_quantity ?? 1 }}" min="1">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Max Quantity</label>
                    <input type="number" name="max_quantity" class="w-full border rounded px-3 py-2" value="{{ $bundle->max_quantity }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Display Order</label>
                    <input type="number" name="display_order" class="w-full border rounded px-3 py-2" value="{{ $bundle->display_order ?? 0 }}" min="0">
                </div>
                <div class="flex items-center gap-4 md:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" class="border rounded" @checked($bundle->is_active)>
                        <span class="text-sm">Active</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_featured" class="border rounded" @checked($bundle->is_featured)>
                        <span class="text-sm">Featured</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="allow_customization" class="border rounded" @checked($bundle->allow_customization)>
                        <span class="text-sm">Allow Customization</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="show_individual_prices" class="border rounded" @checked($bundle->show_individual_prices)>
                        <span class="text-sm">Show Individual Prices</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="show_savings" class="border rounded" @checked($bundle->show_savings)>
                        <span class="text-sm">Show Savings</span>
                    </label>
                </div>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold">Bundle Items</h2>
                    <button type="button" id="addItemBtn" class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700">Add Item</button>
                </div>
                <div class="space-y-3" id="itemsContainer"></div>
            </div>

            <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold">Price Tiers (optional)</h2>
                    <button type="button" id="addPriceBtn" class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700">Add Price Tier</button>
                </div>
                <div class="space-y-3" id="pricesContainer"></div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save Changes</button>
                <a href="{{ route('admin.bundles.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@endpush
@endsection
