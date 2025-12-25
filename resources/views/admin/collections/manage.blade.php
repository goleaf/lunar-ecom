@extends('admin.layout')

@section('title', 'Manage Collection - ' . $collection->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Manage Collection</h1>
        <p class="text-gray-600 mt-2">{{ $collection->name }}</p>
    </div>

    <!-- Collection Settings -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-semibold mb-4">Collection Settings</h2>
        
        <form id="collection-settings-form" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Collection Type *</label>
                    <select name="collection_type" id="collection-type" required class="w-full border rounded px-3 py-2">
                        <option value="manual" {{ $collection->collection_type === 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="bestsellers" {{ $collection->collection_type === 'bestsellers' ? 'selected' : '' }}>Bestsellers</option>
                        <option value="new_arrivals" {{ $collection->collection_type === 'new_arrivals' ? 'selected' : '' }}>New Arrivals</option>
                        <option value="featured" {{ $collection->collection_type === 'featured' ? 'selected' : '' }}>Featured</option>
                        <option value="seasonal" {{ $collection->collection_type === 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                        <option value="custom" {{ $collection->collection_type === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By *</label>
                    <select name="sort_by" required class="w-full border rounded px-3 py-2">
                        <option value="created_at" {{ $collection->sort_by === 'created_at' ? 'selected' : '' }}>Date Created</option>
                        <option value="price" {{ $collection->sort_by === 'price' ? 'selected' : '' }}>Price</option>
                        <option value="name" {{ $collection->sort_by === 'name' ? 'selected' : '' }}>Name</option>
                        <option value="popularity" {{ $collection->sort_by === 'popularity' ? 'selected' : '' }}>Popularity</option>
                        <option value="sales_count" {{ $collection->sort_by === 'sales_count' ? 'selected' : '' }}>Sales Count</option>
                        <option value="rating" {{ $collection->sort_by === 'rating' ? 'selected' : '' }}>Rating</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort Direction *</label>
                    <select name="sort_direction" required class="w-full border rounded px-3 py-2">
                        <option value="asc" {{ $collection->sort_direction === 'asc' ? 'selected' : '' }}>Ascending</option>
                        <option value="desc" {{ $collection->sort_direction === 'desc' ? 'selected' : '' }}>Descending</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Products</label>
                    <input type="number" name="max_products" value="{{ $collection->max_products }}" min="1" class="w-full border rounded px-3 py-2" placeholder="Unlimited if empty">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Display Style *</label>
                    <select name="display_style" required class="w-full border rounded px-3 py-2">
                        <option value="grid" {{ $collection->display_style === 'grid' ? 'selected' : '' }}>Grid</option>
                        <option value="list" {{ $collection->display_style === 'list' ? 'selected' : '' }}>List</option>
                        <option value="carousel" {{ $collection->display_style === 'carousel' ? 'selected' : '' }}>Carousel</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Products Per Row *</label>
                    <input type="number" name="products_per_row" value="{{ $collection->products_per_row ?? 4 }}" min="1" max="6" required class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" name="auto_assign" value="1" {{ $collection->auto_assign ? 'checked' : '' }} id="auto-assign" class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Auto-Assign Products Based on Rules</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="show_on_homepage" value="1" {{ $collection->show_on_homepage ? 'checked' : '' }} class="rounded">
                    <span class="ml-2 text-sm text-gray-700">Show on Homepage</span>
                </label>
            </div>

            <div id="auto-assign-options" class="{{ $collection->auto_assign ? '' : 'hidden' }} border-t pt-4 mt-4">
                <h3 class="font-semibold mb-2">Auto-Assignment Rules</h3>
                <p class="text-sm text-gray-600 mb-4">
                    For collection types like "Bestsellers" and "New Arrivals", rules are automatically applied.
                    For "Custom" type, you can define custom rules.
                </p>
                <div id="assignment-rules-editor">
                    <!-- Rules editor will be populated here -->
                </div>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Save Settings
            </button>
        </form>
    </div>

    <!-- Products in Collection -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-semibold">Products in Collection</h2>
            <div class="flex gap-2">
                <button onclick="processAutoAssignment()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                    Process Auto-Assignment
                </button>
                <button onclick="showAddProductModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                    Add Product
                </button>
            </div>
        </div>

        @if($products->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="products-list">
                        @foreach($products as $product)
                            <tr data-product-id="{{ $product->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($product->getFirstMedia('images'))
                                            <img src="{{ $product->getFirstMedia('images')->getUrl('thumb') }}" 
                                                 alt="{{ $product->translateAttribute('name') }}"
                                                 class="w-12 h-12 object-cover rounded mr-3">
                                        @endif
                                        <div>
                                            <div class="font-medium">{{ $product->translateAttribute('name') }}</div>
                                            <div class="text-sm text-gray-500">{{ $product->sku ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($product->pivot->is_auto_assigned)
                                        <span class="text-blue-600">Auto</span>
                                    @else
                                        <span class="text-gray-600">Manual</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <input type="number" 
                                           value="{{ $product->pivot->position }}" 
                                           onchange="updatePosition({{ $product->id }}, this.value)"
                                           class="w-20 border rounded px-2 py-1 text-sm">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($product->pivot->expires_at)
                                        {{ $product->pivot->expires_at->format('M j, Y') }}
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="removeProduct({{ $product->id }})" class="text-red-600 hover:underline">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $products->links() }}
            </div>
        @else
            <p class="text-gray-600">No products in this collection.</p>
        @endif
    </div>
</div>

<!-- Add Product Modal -->
<div id="add-product-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Add Product to Collection</h3>
            <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600">×</button>
        </div>
        <div class="max-h-96 overflow-y-auto">
            @foreach($availableProducts as $product)
                <div class="flex items-center justify-between p-2 hover:bg-gray-50">
                    <div class="flex items-center">
                        @if($product->getFirstMedia('images'))
                            <img src="{{ $product->getFirstMedia('images')->getUrl('thumb') }}" 
                                 alt="{{ $product->translateAttribute('name') }}"
                                 class="w-10 h-10 object-cover rounded mr-3">
                        @endif
                        <span>{{ $product->translateAttribute('name') }}</span>
                    </div>
                    <button onclick="addProduct({{ $product->id }})" class="text-blue-600 hover:underline text-sm">
                        Add
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
// Show/hide auto-assign options
document.getElementById('auto-assign').addEventListener('change', function() {
    const options = document.getElementById('auto-assign-options');
    if (this.checked) {
        options.classList.remove('hidden');
    } else {
        options.classList.add('hidden');
    }
});

// Handle collection settings form
document.getElementById('collection-settings-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('{{ route('admin.collections.update-settings', $collection->id) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Collection settings updated successfully');
            location.reload();
        } else {
            alert('Failed to update settings: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to update settings');
    }
});

function showAddProductModal() {
    document.getElementById('add-product-modal').classList.remove('hidden');
}

function closeAddProductModal() {
    document.getElementById('add-product-modal').classList.add('hidden');
}

async function addProduct(productId) {
    try {
        const response = await fetch('{{ route('admin.collections.add-product', $collection->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ product_id: productId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to add product');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add product');
    }
}

async function removeProduct(productId) {
    if (!confirm('Are you sure you want to remove this product from the collection?')) return;
    
    try {
        const response = await fetch(`/admin/collections/{{ $collection->id }}/products/${productId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to remove product');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to remove product');
    }
}

async function updatePosition(productId, position) {
    // Debounce position updates
    clearTimeout(window.positionUpdateTimeout);
    window.positionUpdateTimeout = setTimeout(async () => {
        try {
            const response = await fetch('{{ route('admin.collections.reorder', $collection->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_ids: Array.from(document.querySelectorAll('#products-list tr')).map(tr => parseInt(tr.dataset.productId))
                })
            });
            
            const result = await response.json();
            if (!result.success) {
                console.error('Failed to update position');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }, 500);
}

async function processAutoAssignment() {
    if (!confirm('This will process automatic assignment for this collection. Continue?')) return;
    
    try {
        const response = await fetch('{{ route('admin.collections.process-auto-assignment', $collection->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`Assigned ${result.assigned} products to collection`);
            location.reload();
        } else {
            alert('Failed to process auto-assignment: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to process auto-assignment');
    }
}
</script>
@endpush
@endsection

