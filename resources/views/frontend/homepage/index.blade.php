@extends('frontend.layout')

@section('title', config('app.name') . ' - ' . __('frontend.home'))

@section('meta')
    <meta name="description" content="{{ __('frontend.homepage.meta_description', ['store' => config('app.name')]) }}">
@endsection

@section('content')
<div class="homepage">
    <!-- Hero Section -->
    @if($featuredCollections->count() > 0)
        <section class="hero-section relative h-screen min-h-[600px] max-h-[800px] overflow-hidden">
            @foreach($featuredCollections->take(3) as $index => $collection)
                @php
                    $heroImage = $collection->getFirstMedia('hero') 
                        ?? $collection->getFirstMedia('images')
                        ?? $collection->products->first()?->getFirstMedia('images');
                @endphp
                
                @if($heroImage)
                    <div class="hero-slide {{ $index === 0 ? 'active' : '' }}" data-slide-index="{{ $index }}">
                        <div class="absolute inset-0">
                            @include('frontend.components.responsive-image', [
                                'media' => $heroImage,
                                'model' => $collection,
                                'collectionName' => 'hero',
                                'conversion' => 'hero',
                                'sizeType' => 'hero',
                                'alt' => $collection->translateAttribute('name'),
                                'class' => 'w-full h-full object-cover',
                                'loading' => $index === 0 ? 'eager' : 'lazy',
                            ])
                            <div class="absolute inset-0 bg-black bg-opacity-40"></div>
                        </div>
                        <div class="relative z-10 h-full flex items-center justify-center text-center text-white px-4">
                            <div class="max-w-4xl">
                                <h1 class="text-4xl md:text-6xl font-bold mb-4 animate-fade-in">
                                    {{ $collection->translateAttribute('name') }}
                                </h1>
                                @if($collection->translateAttribute('description'))
                                    <p class="text-xl md:text-2xl mb-8 animate-fade-in-delay">
                                        {{ Str::limit($collection->translateAttribute('description'), 150) }}
                                    </p>
                                @endif
                                <a href="{{ route('frontend.collections.show', $collection->urls->first()->slug ?? $collection->id) }}" 
                                   class="inline-block bg-white text-gray-900 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors animate-fade-in-delay-2">
                                    {{ __('frontend.homepage.explore_collection') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            <!-- Hero Navigation -->
            @if($featuredCollections->count() > 1)
                <div class="hero-navigation absolute bottom-8 left-1/2 transform -translate-x-1/2 z-20 flex gap-2">
                    @foreach($featuredCollections->take(3) as $index => $collection)
                        <button class="hero-dot {{ $index === 0 ? 'active' : '' }}" 
                                data-slide="{{ $index }}"
                                aria-label="Go to slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif

            <!-- Hero Controls -->
            @if($featuredCollections->count() > 1)
                <button class="hero-prev absolute left-4 top-1/2 transform -translate-y-1/2 z-20 bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-3 rounded-full transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button class="hero-next absolute right-4 top-1/2 transform -translate-y-1/2 z-20 bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-3 rounded-full transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            @endif
        </section>
    @endif

    <!-- Promotional Banners -->
    @if(!empty($promotionalBanners))
        <section class="promotional-banners py-8 px-4">
            <div class="container mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($promotionalBanners as $banner)
                        @if($banner['is_active'] && $banner['position'] === 'top')
                            <div class="promotional-banner relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                                <a href="{{ $banner['link'] }}" class="block">
                                    <div class="relative h-64 md:h-80">
                                        <img src="{{ $banner['image'] }}" 
                                             alt="{{ $banner['title'] }}"
                                             class="w-full h-full object-cover"
                                             loading="lazy">
                                        <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent"></div>
                                        <div class="absolute inset-0 flex items-center justify-start p-8 text-white">
                                            <div>
                                                <p class="text-sm uppercase tracking-wide mb-2">{{ $banner['subtitle'] }}</p>
                                                <h2 class="text-3xl md:text-4xl font-bold mb-2">{{ $banner['title'] }}</h2>
                                                @if(isset($banner['description']))
                                                    <p class="text-lg mb-4">{{ $banner['description'] }}</p>
                                                @endif
                                                <span class="inline-block bg-white text-gray-900 px-6 py-2 rounded font-semibold hover:bg-gray-100 transition-colors">
                                                    {{ $banner['link_text'] ?? __('frontend.common.shop_now') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Featured Collections -->
    @if($featuredCollections->count() > 0)
        <section class="featured-collections py-12 px-4 bg-gray-50">
            <div class="container mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold text-center mb-8">{{ __('frontend.homepage.featured_collections') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($featuredCollections as $collection)
                        <x-frontend.collection-card :collection="$collection" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Bestsellers Section -->
    @if($bestsellers && $bestsellers->products->count() > 0)
        <section class="bestsellers py-12 px-4">
            <div class="container mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl md:text-4xl font-bold">{{ __('frontend.homepage.bestsellers') }}</h2>
                    @if($bestsellers->urls->first())
                        <a href="{{ route('frontend.collections.show', $bestsellers->urls->first()->slug) }}" 
                           class="text-blue-600 hover:text-blue-800 font-semibold">
                            {{ __('frontend.common.view_all') }} →
                        </a>
                    @endif
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($bestsellers->products->take(8) as $product)
                        <x-frontend.product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Middle Promotional Banner -->
    @if($promotionalBanners->isNotEmpty())
        @foreach($promotionalBanners as $banner)
            @if($banner['is_active'] && $banner['position'] === 'middle')
                <section class="promotional-banner-full py-12 px-4">
                    <div class="container mx-auto">
                        <div class="relative overflow-hidden rounded-lg shadow-lg">
                            <a href="{{ $banner['link'] }}" class="block">
                                <div class="relative h-64 md:h-96">
                                    <img src="{{ $banner['image'] }}" 
                                         alt="{{ $banner['title'] }}"
                                         class="w-full h-full object-cover"
                                         loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-black/40"></div>
                                    <div class="absolute inset-0 flex items-center justify-center text-center text-white p-8">
                                        <div class="max-w-2xl">
                                            <p class="text-sm uppercase tracking-wide mb-2">{{ $banner['subtitle'] }}</p>
                                            <h2 class="text-4xl md:text-5xl font-bold mb-4">{{ $banner['title'] }}</h2>
                                            @if(isset($banner['description']))
                                                <p class="text-xl mb-6">{{ $banner['description'] }}</p>
                                            @endif
                                            <span class="inline-block bg-white text-gray-900 px-8 py-3 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors">
                                                {{ $banner['link_text'] ?? __('frontend.common.shop_now') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>
            @endif
        @endforeach
    @endif

    <!-- New Arrivals Section -->
    @if($newArrivals && $newArrivals->products->count() > 0)
        <section class="new-arrivals py-12 px-4 bg-gray-50">
            <div class="container mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl md:text-4xl font-bold">{{ __('frontend.homepage.new_arrivals') }}</h2>
                    @if($newArrivals->urls->first())
                        <a href="{{ route('frontend.collections.show', $newArrivals->urls->first()->slug) }}" 
                           class="text-blue-600 hover:text-blue-800 font-semibold">
                            {{ __('frontend.common.view_all') }} →
                        </a>
                    @endif
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($newArrivals->products->take(8) as $product)
                        <x-frontend.product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
@endpush

@push('scripts')
@endpush


