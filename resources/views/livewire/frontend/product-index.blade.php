@extends('frontend.layout')

@section('title', $metaTags['title'] ?? 'Products')

@section('meta')
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <link rel="canonical" href="{{ $metaTags['canonical'] }}">
@endsection

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">{{ __('frontend.nav.products') }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Filters Sidebar --}}
        <aside class="lg:col-span-1">
            <div class="bg-white p-4 rounded-lg shadow sticky top-4">
                <h2 class="text-lg font-semibold mb-4">{{ __('frontend.filters') }}</h2>

                {{-- Brand + Sort controls (Livewire) --}}
                @if(isset($brands) && $brands->count() > 0)
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">{{ __('frontend.brand') }}</label>
                        <select wire:model.live="brandId" name="brand_id" class="border rounded px-3 py-1 w-full">
                            <option value="">{{ __('frontend.all_brands') }}</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">{{ __('frontend.sort_by') }}</label>
                    <select wire:model.live="sort" name="sort" class="border rounded px-3 py-1 w-full">
                        <option value="default">{{ __('frontend.sort.default') }}</option>
                        <option value="price_asc">{{ __('frontend.sort.price_asc') }}</option>
                        <option value="price_desc">{{ __('frontend.sort.price_desc') }}</option>
                        <option value="newest">{{ __('frontend.sort.newest') }}</option>
                    </select>
                </div>

                {{-- Attribute filters (GET form for complex filter payloads) --}}
                <form method="GET" action="{{ route('frontend.products.index') }}" id="filter-form">
                    <input type="hidden" name="brand_id" value="{{ $brandId }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">

                    @if(isset($groupedAttributes) && $groupedAttributes->count() > 0)
                        @include('frontend.components.attribute-filters', [
                            'groupedAttributes' => $groupedAttributes,
                            'activeFilters' => $activeFilters ?? [],
                            'baseUrl' => route('frontend.products.index')
                        ])
                    @endif

                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-2">
                        {{ __('frontend.apply_filters') }}
                    </button>
                    @if($brandId || !empty($activeFilters ?? []))
                        <a href="{{ route('frontend.products.index') }}" class="block text-center text-blue-600 hover:text-blue-800 text-sm">
                            {{ __('frontend.clear_filters') }}
                        </a>
                    @endif
                </form>
            </div>
        </aside>

        {{-- Products Grid --}}
        <div class="lg:col-span-3">

            {{-- Active Filters Display --}}
            @if($brandId || !empty($activeFilters ?? []))
                <div class="mb-4 p-3 bg-blue-50 rounded flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium">Active filters:</span>
                    @if($brandId)
                        @php
                            $selectedBrand = $brands->firstWhere('id', $brandId);
                        @endphp
                        @if($selectedBrand)
                            <span class="text-sm bg-white px-2 py-1 rounded">
                                Brand: {{ $selectedBrand->name }}
                                <a href="{{ route('frontend.products.index', array_merge(request()->except('brand_id'), $activeFilters ?? [])) }}" class="ml-1 text-red-600">x</a>
                            </span>
                        @endif
                    @endif
                    @if(!empty($activeFilters ?? []))
                        @foreach($activeFilters as $handle => $value)
                            @php
                                $attribute = \App\Models\Attribute::where('handle', $handle)->first();
                            @endphp
                            @if($attribute)
                                <span class="text-sm bg-white px-2 py-1 rounded">
                                    {{ \App\Lunar\Attributes\AttributeFilterHelper::getFilterDisplayName($attribute) }}:
                                    {{ is_array($value) ? implode(', ', $value) : \App\Lunar\Attributes\AttributeFilterHelper::formatFilterValue($attribute, $value) }}
                                    <a href="{{ route('frontend.products.index', array_merge(request()->except($handle), ['brand_id' => $brandId, 'sort' => $sort])) }}" class="ml-1 text-red-600">x</a>
                                </span>
                            @endif
                        @endforeach
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($products as $product)
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        @php
                            $imageUrl = $product->getFirstMediaUrl('images', 'thumb')
                                ?? $product->getFirstMediaUrl('images')
                                ?? config('lunar.media.fallback.url');
                        @endphp

                        @if($imageUrl)
                            <img src="{{ $imageUrl }}"
                                 alt="{{ $product->translateAttribute('name') }}"
                                 class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                        @endif
                        <div class="p-4">
                            <h3 class="text-lg font-semibold mb-2">
                                <a href="{{ route('frontend.products.show', $product->urls->first()->slug ?? $product->id) }}" class="text-gray-900 hover:text-gray-600">
                                    {{ $product->translateAttribute('name') }}
                                </a>
                            </h3>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                {{ $product->translateAttribute('description') }}
                            </p>
                            @php
                                $variant = $product->variants->first();
                                if ($variant) {
                                    $pricing = \Lunar\Facades\Pricing::for($variant)->get();
                                    $price = $pricing->matched?->price;
                                }
                            @endphp
                            @if(isset($price) && $price)
                                <p class="text-xl font-bold text-gray-900">
                                    {{ $price->formatted }}
                                </p>
                            @endif
                            <a href="{{ route('frontend.products.show', $product->urls->first()->slug ?? $product->id) }}" class="mt-3 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                {{ __('frontend.product.view_details') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-600">No products found.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

