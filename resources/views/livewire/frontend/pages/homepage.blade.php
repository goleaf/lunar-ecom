<div class="homepage">
    @php
        $topBanners = $promotionalBanners
            ->filter(fn ($banner) => ($banner['is_active'] ?? false) && ($banner['position'] ?? null) === 'top')
            ->values();

        $middleBanners = $promotionalBanners
            ->filter(fn ($banner) => ($banner['is_active'] ?? false) && ($banner['position'] ?? null) === 'middle')
            ->values();
    @endphp

    {{-- Hero --}}
    @if($heroCollections->isNotEmpty())
        <section class="hero-section relative overflow-hidden">
            <div class="relative min-h-[520px] sm:min-h-[620px] lg:min-h-[720px]">
                @foreach($heroCollections as $index => $collection)
                    @php
                        $heroImage = $collection->getFirstMedia('hero') ?? $collection->getFirstMedia('images');
                        $collectionSlug = $collection->urls->first()?->slug ?? $collection->id;
                    @endphp

                    <div
                        class="hero-slide absolute inset-0 transition-opacity duration-700 ease-out {{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 pointer-events-none' }}"
                        data-slide-index="{{ $index }}"
                        aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                    >
                        <div class="absolute inset-0">
                            @if($heroImage)
                                @include('frontend.components.responsive-image', [
                                    'media' => $heroImage,
                                    'model' => $collection,
                                    'collectionName' => $heroImage->collection_name ?? 'hero',
                                    'conversion' => 'hero',
                                    'sizeType' => 'hero',
                                    'alt' => $collection->translateAttribute('name'),
                                    'class' => 'w-full h-full object-cover',
                                    'loading' => $index === 0 ? 'eager' : 'lazy',
                                ])
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-r from-gray-950/80 via-gray-950/55 to-gray-950/20"></div>
                        </div>

                        <div class="relative z-10 flex items-center h-full">
                            <div class="w-full">
                                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
                                    <div class="max-w-2xl">
                                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-white/80">
                                            {{ __('frontend.homepage.featured_collections') }}
                                        </p>

                                        <h1 class="mt-4 text-4xl sm:text-5xl lg:text-6xl font-semibold leading-tight text-white">
                                            {{ $collection->translateAttribute('name') }}
                                        </h1>

                                        @if($collection->translateAttribute('description'))
                                            <p class="mt-4 text-lg sm:text-xl text-white/90 max-w-prose">
                                                {{ Str::limit($collection->translateAttribute('description'), 170) }}
                                            </p>
                                        @endif

                                        <div class="mt-8 flex flex-wrap gap-3">
                                            <a
                                                href="{{ route('frontend.collections.show', $collectionSlug) }}"
                                                class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-sm font-semibold text-gray-900 hover:bg-white/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                                            >
                                                {{ __('frontend.homepage.explore_collection') }}
                                            </a>

                                            <a
                                                href="{{ route('frontend.products.index') }}"
                                                class="inline-flex items-center justify-center rounded-lg bg-white/10 px-6 py-3 text-sm font-semibold text-white ring-1 ring-white/30 hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                                            >
                                                {{ __('frontend.nav.products') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($heroCollections->count() > 1)
                <div class="absolute inset-x-0 bottom-6 z-20">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                        <button
                            type="button"
                            class="hero-prev inline-flex items-center justify-center rounded-full bg-white/10 p-3 text-white ring-1 ring-white/30 hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                            aria-label="Previous slide"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <div class="hero-navigation flex items-center gap-2" aria-label="Hero slides">
                            @foreach($heroCollections as $index => $collection)
                                <button
                                    type="button"
                                    class="hero-dot h-3 w-3 rounded-full border-2 border-white/80 bg-transparent opacity-60 transition hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 {{ $index === 0 ? 'bg-white opacity-100' : '' }}"
                                    data-slide="{{ $index }}"
                                    aria-label="Go to slide {{ $index + 1 }}"
                                ></button>
                            @endforeach
                        </div>

                        <button
                            type="button"
                            class="hero-next inline-flex items-center justify-center rounded-full bg-white/10 p-3 text-white ring-1 ring-white/30 hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                            aria-label="Next slide"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        </section>
    @endif

    {{-- Promotional banners (top) --}}
    @if($topBanners->isNotEmpty())
        <section class="py-10 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($topBanners as $banner)
                        <div class="rounded-xl overflow-hidden bg-white shadow-sm ring-1 ring-black/5 hover:shadow-md transition-shadow">
                            <a href="{{ $banner['link'] ?? '#' }}" class="block">
                                <div class="relative h-64 md:h-80">
                                    <img
                                        src="{{ $banner['image'] ?? asset('images/banners/default.jpg') }}"
                                        alt="{{ $banner['title'] ?? 'Promotional banner' }}"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                    <div class="absolute inset-0 bg-gradient-to-r from-gray-950/65 via-gray-950/35 to-transparent"></div>
                                    <div class="absolute inset-0 flex items-end md:items-center p-6 md:p-8 text-white">
                                        <div class="max-w-md">
                                            @if(!empty($banner['subtitle']))
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">
                                                    {{ $banner['subtitle'] }}
                                                </p>
                                            @endif

                                            @if(!empty($banner['title']))
                                                <h2 class="mt-2 text-2xl md:text-3xl font-semibold leading-tight">
                                                    {{ $banner['title'] }}
                                                </h2>
                                            @endif

                                            @if(!empty($banner['description']))
                                                <p class="mt-3 text-sm md:text-base text-white/90">
                                                    {{ $banner['description'] }}
                                                </p>
                                            @endif

                                            <div class="mt-5">
                                                <span class="inline-flex items-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-white/90">
                                                    {{ $banner['link_text'] ?? __('frontend.common.shop_now') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Featured collections --}}
    @if($featuredCollections->isNotEmpty())
        <section class="py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-4 mb-8">
                    <h2 class="text-3xl sm:text-4xl font-semibold text-gray-900">
                        {{ __('frontend.homepage.featured_collections') }}
                    </h2>

                    <a
                        href="{{ route('frontend.collections.index') }}"
                        class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                    >
                        {{ __('frontend.common.view_all') }} →
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($featuredCollections as $collection)
                        <x-frontend.collection-card :collection="$collection" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Bestsellers --}}
    @if($bestsellers && $bestsellers->products->count() > 0)
        <section class="py-14 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-4 mb-8">
                    <h2 class="text-3xl sm:text-4xl font-semibold text-gray-900">
                        {{ __('frontend.homepage.bestsellers') }}
                    </h2>

                    @if($bestsellers->urls->first())
                        <a
                            href="{{ route('frontend.collections.show', $bestsellers->urls->first()?->slug) }}"
                            class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                        >
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

    {{-- Promotional banner (middle) --}}
    @if($middleBanners->isNotEmpty())
        @foreach($middleBanners as $banner)
            <section class="py-14">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="rounded-2xl overflow-hidden bg-white shadow-sm ring-1 ring-black/5 hover:shadow-md transition-shadow">
                        <a href="{{ $banner['link'] ?? '#' }}" class="block">
                            <div class="relative h-64 md:h-96">
                                <img
                                    src="{{ $banner['image'] ?? asset('images/banners/default.jpg') }}"
                                    alt="{{ $banner['title'] ?? 'Promotional banner' }}"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                                <div class="absolute inset-0 bg-gradient-to-r from-gray-950/75 via-gray-950/45 to-gray-950/20"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-center p-8 text-white">
                                    <div class="max-w-2xl">
                                        @if(!empty($banner['subtitle']))
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">
                                                {{ $banner['subtitle'] }}
                                            </p>
                                        @endif

                                        @if(!empty($banner['title']))
                                            <h2 class="mt-2 text-3xl md:text-5xl font-semibold leading-tight">
                                                {{ $banner['title'] }}
                                            </h2>
                                        @endif

                                        @if(!empty($banner['description']))
                                            <p class="mt-4 text-base md:text-lg text-white/90">
                                                {{ $banner['description'] }}
                                            </p>
                                        @endif

                                        <div class="mt-6">
                                            <span class="inline-flex items-center rounded-lg bg-white px-7 py-3 text-sm font-semibold text-gray-900 hover:bg-white/90">
                                                {{ $banner['link_text'] ?? __('frontend.common.shop_now') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>
        @endforeach
    @endif

    {{-- New arrivals --}}
    @if($newArrivals && $newArrivals->products->count() > 0)
        <section class="py-14 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-4 mb-8">
                    <h2 class="text-3xl sm:text-4xl font-semibold text-gray-900">
                        {{ __('frontend.homepage.new_arrivals') }}
                    </h2>

                    @if($newArrivals->urls->first())
                        <a
                            href="{{ route('frontend.collections.show', $newArrivals->urls->first()?->slug) }}"
                            class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                        >
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

