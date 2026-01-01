@extends('frontend.layout')

@section('title', $category->meta_title ?? $category->getName())

@section('meta')
    @php
        use App\Lunar\Categories\CategorySEO;
        $metaTags = CategorySEO::getMetaTags($category);
    @endphp
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta name="keywords" content="{{ $metaTags['keywords'] }}">
    <meta name="robots" content="{{ CategorySEO::getRobotsMeta($category) }}">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    @if($metaTags['og:image'])
        <meta property="og:image" content="{{ $metaTags['og:image'] }}">
    @endif
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ $metaTags['canonical'] }}">
    
    {{-- Structured Data --}}
    <script type="application/ld+json">
        {!! json_encode(CategorySEO::getStructuredData($category), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('content')
<div class="px-4 py-6">
    {{-- Breadcrumb Navigation --}}
    @if(isset($breadcrumb) && count($breadcrumb) > 0)
        <nav class="mb-4 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 flex-wrap">
                <li>
                    <a href="{{ route('frontend.homepage') }}" class="text-gray-500 hover:text-gray-700">
                        {{ __('frontend.home') }}
                    </a>
                </li>
                @foreach($breadcrumb as $crumb)
                    <li class="flex items-center">
                        <span class="mx-2 text-gray-400">/</span>
                        @if(!$loop->last)
                            <a href="{{ $crumb['url'] }}" class="text-gray-500 hover:text-gray-700">
                                {{ $crumb['name'] }}
                            </a>
                        @else
                            <span class="text-gray-900 font-medium">{{ $crumb['name'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    {{-- Category Header --}}
    <div class="mb-6">
        @if($category->getImageUrl('banner'))
            <div class="mb-4">
                <img src="{{ $category->getImageUrl('banner') }}" 
                     alt="{{ $category->getName() }}" 
                     class="w-full h-64 object-cover rounded-lg">
            </div>
        @endif

        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $category->getName() }}</h1>
                @if($category->getDescription())
                    <div class="text-gray-600 prose max-w-none">
                        {!! $category->getDescription() !!}
                    </div>
                @endif
            </div>
            @if($category->getImageUrl('thumb'))
                <img src="{{ $category->getImageUrl('thumb') }}" 
                     alt="{{ $category->getName() }}" 
                     class="w-24 h-24 object-cover rounded-lg ml-4">
            @endif
        </div>
    </div>

    {{-- Sub-categories --}}
    @if(isset($children) && $children->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ __('frontend.subcategories') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($children as $child)
                    <a href="{{ route('categories.show', $child->getFullPath()) }}" 
                       class="block p-4 border rounded-lg hover:shadow-lg transition-shadow bg-white">
                        @if($child->getImageUrl('thumb'))
                            <img src="{{ $child->getImageUrl('thumb') }}" 
                                 alt="{{ $child->getName() }}" 
                                 class="w-full h-32 object-cover rounded mb-3">
                        @endif
                        <h3 class="font-semibold text-lg mb-1">{{ $child->getName() }}</h3>
                        @if($child->product_count > 0)
                            <p class="text-sm text-gray-500">{{ $child->product_count }} {{ __('frontend.products') }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    @if(isset($filters))
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <aside class="lg:col-span-1">
                <div class="bg-gray-50 p-4 rounded-lg sticky top-4">
                    <h3 class="font-semibold mb-3">{{ __('frontend.filters') }}</h3>
                    <form method="GET" action="{{ route('categories.show', $category->getFullPath()) }}" class="space-y-4">
                {{-- Price Range --}}
                @if(isset($filters['price_range']) && $filters['price_range']['max'] > 0)
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('frontend.price_range') }}</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" 
                                   name="min_price" 
                                   value="{{ request('min_price') }}" 
                                   placeholder="Min" 
                                   min="0" 
                                   step="0.01"
                                   class="border rounded px-3 py-1 w-24">
                            <span>-</span>
                            <input type="number" 
                                   name="max_price" 
                                   value="{{ request('max_price') }}" 
                                   placeholder="Max" 
                                   min="0" 
                                   step="0.01"
                                   class="border rounded px-3 py-1 w-24">
                        </div>
                    </div>
                @endif

                {{-- Brand Filter --}}
                @if(isset($filters['brands']) && $filters['brands']->count() > 0)
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('frontend.brand') }}</label>
                        <select name="brand_id" class="border rounded px-3 py-1 w-full">
                            <option value="">{{ __('frontend.all_brands') }}</option>
                            @foreach($filters['brands'] as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Sort --}}
                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('frontend.sort_by') }}</label>
                    <select name="sort" class="border rounded px-3 py-1 w-full">
                        <option value="default" {{ request('sort') == 'default' ? 'selected' : '' }}>{{ __('frontend.sort.default') }}</option>
                        <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>{{ __('frontend.sort.price_asc') }}</option>
                        <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>{{ __('frontend.sort.price_desc') }}</option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>{{ __('frontend.sort.name_asc') }}</option>
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('frontend.sort.newest') }}</option>
                    </select>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    {{ __('frontend.apply_filters') }}
                </button>
                @if(request()->hasAny(['min_price', 'max_price', 'brand_id', 'sort']))
                    <a href="{{ route('categories.show', $category->getFullPath()) }}" class="ml-2 text-blue-600 hover:text-blue-800">
                        {{ __('frontend.clear_filters') }}
                    </a>
                        @endif

                        {{-- Attribute Filters --}}
                        @if(isset($filters['grouped_attributes']) && $filters['grouped_attributes']->count() > 0)
                            @include('frontend.components.attribute-filters', [
                                'groupedAttributes' => $filters['grouped_attributes'],
                                'activeFilters' => $activeFilters ?? [],
                                'baseUrl' => route('categories.show', $category->getFullPath())
                            ])
                        @endif
                    </form>
                </div>
            </aside>

            {{-- Products Grid --}}
            <div class="lg:col-span-3">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">
                        {{ __('frontend.products') }}
                        @if($products->total() > 0)
                            <span class="text-gray-500 text-base font-normal">({{ $products->total() }})</span>
                        @endif
                    </h2>
                </div>

                @if($products->count() > 0)
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($products as $product)
                            @include('frontend.products._product-card', ['product' => $product])
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <p class="text-gray-600">{{ __('frontend.no_products') }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection


