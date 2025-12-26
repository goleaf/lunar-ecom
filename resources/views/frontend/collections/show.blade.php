@extends('storefront.layout')

@section('title', $collection->translateAttribute('name'))

@section('meta')
    <meta name="description" content="{{ Str::limit($collection->translateAttribute('description') ?? '', 160) }}">
    <meta property="og:title" content="{{ $collection->translateAttribute('name') }}">
    <meta property="og:description" content="{{ Str::limit($collection->translateAttribute('description') ?? '', 160) }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    <link rel="canonical" href="{{ request()->url() }}">
@endsection

@section('content')
<div class="collection-page">
    <!-- Collection Header -->
    <div class="bg-white shadow-sm mb-6">
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-4xl font-bold mb-4">{{ $collection->translateAttribute('name') }}</h1>
            @if($collection->translateAttribute('description'))
                <p class="text-gray-600 text-lg">{{ $collection->translateAttribute('description') }}</p>
            @endif
            <p class="text-gray-500 mt-2">
                {{ $products->total() }} {{ $products->total() === 1 ? 'product' : 'products' }}
            </p>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Filters Sidebar -->
            <aside class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Filters</h2>
                        <button onclick="clearAllFilters()" class="text-sm text-blue-600 hover:text-blue-800">
                            Clear All
                        </button>
                    </div>

                    <form id="filter-form" class="space-y-6">
                        <!-- Price Range -->
                        <div class="filter-section">
                            <h3 class="font-semibold mb-3">Price Range</h3>
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <input type="number" 
                                           id="min_price" 
                                           name="min_price" 
                                           placeholder="Min" 
                                           step="0.01"
                                           class="w-full border rounded px-3 py-2 text-sm">
                                    <input type="number" 
                                           id="max_price" 
                                           name="max_price" 
                                           placeholder="Max" 
                                           step="0.01"
                                           class="w-full border rounded px-3 py-2 text-sm">
                                </div>
                                <div id="price-range-display" class="text-sm text-gray-600"></div>
                            </div>
                        </div>

                        <!-- Availability -->
                        <div class="filter-section">
                            <h3 class="font-semibold mb-3">Availability</h3>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="availability" value="in_stock" class="mr-2">
                                    <span class="text-sm">In Stock</span>
                                    <span class="ml-auto text-sm text-gray-500" id="availability-in_stock-count"></span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="availability" value="low_stock" class="mr-2">
                                    <span class="text-sm">Low Stock</span>
                                    <span class="ml-auto text-sm text-gray-500" id="availability-low_stock-count"></span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="availability" value="out_of_stock" class="mr-2">
                                    <span class="text-sm">Out of Stock</span>
                                    <span class="ml-auto text-sm text-gray-500" id="availability-out_of_stock-count"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Brands -->
                        <div class="filter-section" id="brands-filter" style="display: none;">
                            <h3 class="font-semibold mb-3">Brands</h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto" id="brands-list">
                                <!-- Populated via AJAX -->
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="filter-section" id="categories-filter" style="display: none;">
                            <h3 class="font-semibold mb-3">Categories</h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto" id="categories-list">
                                <!-- Populated via AJAX -->
                            </div>
                        </div>

                        <!-- Attributes -->
                        <div id="attributes-filter">
                            <!-- Populated via AJAX -->
                        </div>

                        <!-- Rating -->
                        <div class="filter-section">
                            <h3 class="font-semibold mb-3">Minimum Rating</h3>
                            <div class="space-y-2">
                                @for($i = 5; $i >= 1; $i--)
                                    <label class="flex items-center">
                                        <input type="radio" name="min_rating" value="{{ $i }}" class="mr-2">
                                        <span class="text-sm">
                                            @for($j = 0; $j < $i; $j++)⭐@endfor
                                            & Up
                                        </span>
                                    </label>
                                @endfor
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="filter-section">
                            <h3 class="font-semibold mb-3">Search</h3>
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search products..." 
                                   class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="lg:col-span-3">
                <!-- Sort and View Options -->
                <div class="bg-white rounded-lg shadow p-4 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">Sort by:</span>
                        <select id="sort-by" class="border rounded px-3 py-2 text-sm">
                            <option value="default">Default</option>
                            <option value="newest">Newest</option>
                            <option value="oldest">Oldest</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="popularity">Popularity</option>
                            <option value="rating">Rating</option>
                            <option value="name_asc">Name: A-Z</option>
                            <option value="name_desc">Name: Z-A</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600" id="results-count">
                            Showing {{ $products->count() }} of {{ $products->total() }} products
                        </span>
                    </div>
                </div>

                <!-- Active Filters -->
                <div id="active-filters" class="mb-4 flex flex-wrap gap-2">
                    <!-- Populated via JavaScript -->
                </div>

                <!-- Loading Indicator -->
                <div id="loading-indicator" class="hidden text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600">Loading products...</p>
                </div>

                <!-- Products Grid -->
                <div id="products-container">
                    @include('storefront.collections._product-grid', ['products' => $products, 'collection' => $collection])
                </div>

                <!-- Pagination -->
                <div id="pagination-container" class="mt-6">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/collection-filters.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/collection-filters.js') }}"></script>
<script>
    // Initialize with collection data
    window.collectionFilters = {
        collectionId: {{ $collection->id }},
        baseUrl: '{{ route('storefront.collections.filter', $collection->id) }}',
        filterOptions: @json($filterOptions ?? []),
    };
</script>
@endpush
