@extends('frontend.layout')

@section('title', $query ? "Search: {$query}" : 'Search')

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold mb-2">Search Results</h1>
        @if($query)
            <p class="text-gray-600">
                Found <strong>{{ $products->total() ?? 0 }}</strong> result(s) for: <strong>"{{ $query }}"</strong>
            </p>
        @else
            <p class="text-gray-600">Enter a search term to find products.</p>
        @endif
    </div>

    @if($query)
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Filters Sidebar --}}
            @if(isset($facets) && !empty($facets))
                <aside class="lg:col-span-1">
                    <div class="bg-white p-4 rounded-lg shadow sticky top-4">
                        <h2 class="text-lg font-semibold mb-4">{{ __('frontend.filters') }}</h2>
                        
                        <form method="GET" action="{{ route('frontend.search.index') }}">
                            <input type="hidden" name="q" value="{{ $query }}">

                            {{-- Category Facets --}}
                            @if(isset($facets['categories']) && $facets['categories']->count() > 0)
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2">{{ __('frontend.categories') }}</label>
                                    <div class="space-y-1 max-h-48 overflow-y-auto">
                                        @foreach($facets['categories'] as $category)
                                            <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                <input type="checkbox" 
                                                       name="category_id[]" 
                                                       value="{{ $category['id'] }}"
                                                       {{ in_array($category['id'], request('category_id', [])) ? 'checked' : '' }}
                                                       class="rounded">
                                                <span class="text-sm flex-1">{{ $category['name'] }}</span>
                                                <span class="text-xs text-gray-500">({{ $category['count'] }})</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Brand Facets --}}
                            @if(isset($facets['brands']) && $facets['brands']->count() > 0)
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2">{{ __('frontend.brand') }}</label>
                                    <div class="space-y-1 max-h-48 overflow-y-auto">
                                        @foreach($facets['brands'] as $brand)
                                            <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                <input type="checkbox" 
                                                       name="brand_id[]" 
                                                       value="{{ $brand['id'] }}"
                                                       {{ in_array($brand['id'], request('brand_id', [])) ? 'checked' : '' }}
                                                       class="rounded">
                                                <span class="text-sm flex-1">{{ $brand['name'] }}</span>
                                                <span class="text-xs text-gray-500">({{ $brand['count'] }})</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Price Range --}}
                            @if(isset($facets['price_ranges']))
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2">{{ __('frontend.price_range') }}</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" 
                                               name="price_min" 
                                               value="{{ request('price_min') }}" 
                                               placeholder="Min" 
                                               min="0" 
                                               step="0.01"
                                               class="border rounded px-3 py-1 w-24 text-sm">
                                        <span>-</span>
                                        <input type="number" 
                                               name="price_max" 
                                               value="{{ request('price_max') }}" 
                                               placeholder="Max" 
                                               min="0" 
                                               step="0.01"
                                               class="border rounded px-3 py-1 w-24 text-sm">
                                    </div>
                                </div>
                            @endif

                            {{-- Sort --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">{{ __('frontend.sort_by') }}</label>
                                <select name="sort" class="border rounded px-3 py-1 w-full text-sm">
                                    <option value="relevance" {{ request('sort') == 'relevance' ? 'selected' : '' }}>Relevance</option>
                                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>{{ __('frontend.sort.price_asc') }}</option>
                                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>{{ __('frontend.sort.price_desc') }}</option>
                                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('frontend.sort.newest') }}</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-2 text-sm">
                                {{ __('frontend.apply_filters') }}
                            </button>
                            @if(request()->hasAny(['category_id', 'brand_id', 'price_min', 'price_max', 'sort']))
                                <a href="{{ route('frontend.search.index', ['q' => $query]) }}" class="block text-center text-blue-600 hover:text-blue-800 text-sm">
                                    {{ __('frontend.clear_filters') }}
                                </a>
                            @endif
                        </form>
                    </div>
                </aside>
            @endif

            {{-- Results --}}
            <div class="{{ isset($facets) && !empty($facets) ? 'lg:col-span-3' : 'lg:col-span-4' }}">
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($products as $product)
                            <div x-data="{ trackClick: function() {
                                fetch('{{ route('frontend.search.track-click') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        query: '{{ $query }}',
                                        product_id: {{ $product->id }}
                                    })
                                });
                            }}">
                                <a href="{{ route('frontend.products.show', $product->urls->first()->slug ?? $product->id) }}" @click="trackClick()">
                                    @include('frontend.products._product-card', ['product' => $product])
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <p class="text-gray-600 mb-4">No products found for "{{ $query }}".</p>
                        <div class="text-sm text-gray-500">
                            <p class="mb-2">Try:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Checking your spelling</li>
                                <li>Using different keywords</li>
                                <li>Removing filters</li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Empty Search State --}}
        <div class="text-center py-12">
            <p class="text-gray-600 mb-4">Enter a search term above to find products.</p>
            
            @php
                $popularSearches = app(\App\Services\SearchService::class)->popularSearches(5);
            @endphp
            @if($popularSearches->count() > 0)
                <div class="mt-6">
                    <p class="text-sm text-gray-500 mb-2">Popular Searches:</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach($popularSearches as $search)
                            <a href="{{ route('frontend.search.index', ['q' => $search->search_term]) }}" 
                               class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                                {{ $search->search_term }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection



