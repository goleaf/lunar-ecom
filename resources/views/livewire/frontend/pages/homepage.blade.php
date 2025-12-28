@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >
    <style>
        .homepage {
            --ink: #0f1b1e;
            --muted: #566368;
            --accent: #f2684b;
            --accent-deep: #d5563b;
            --surface: #f7f2eb;
            --surface-strong: #efe6db;
            --sea: #2aa6a1;
            background: radial-gradient(circle at top right, rgba(242, 104, 75, 0.12), transparent 45%),
                radial-gradient(circle at 12% 18%, rgba(42, 166, 161, 0.14), transparent 40%),
                var(--surface);
            color: var(--ink);
            font-family: 'Manrope', 'Instrument Sans', ui-sans-serif, sans-serif;
        }

        .homepage [x-cloak] {
            display: none !important;
        }

        .homepage .font-display {
            font-family: 'Fraunces', serif;
        }

        .homepage .text-muted {
            color: var(--muted);
        }

        .homepage .text-ink {
            color: var(--ink);
        }

        .homepage .bg-ink {
            background: var(--ink);
        }

        .homepage .bg-surface {
            background: var(--surface);
        }

        .homepage .bg-surface-strong {
            background: var(--surface-strong);
        }

        .homepage .surface-card {
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(255, 255, 255, 0.65);
            box-shadow: 0 18px 45px rgba(15, 27, 30, 0.12);
        }

        .homepage .glass-card {
            background: rgba(11, 18, 20, 0.58);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(14px);
        }

        .homepage .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 12px 24px rgba(242, 104, 75, 0.35);
        }

        .homepage .btn-primary:hover {
            background: var(--accent-deep);
        }

        .homepage .btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }

        .homepage .hero-overlay {
            background: linear-gradient(110deg, rgba(10, 16, 18, 0.82), rgba(10, 16, 18, 0.5) 45%, rgba(10, 16, 18, 0.1));
        }

        .homepage .eyebrow {
            letter-spacing: 0.4em;
        }

        .homepage .animate-rise {
            animation: rise 0.8s ease both;
        }

        .homepage .delay-1 {
            animation-delay: 0.1s;
        }

        .homepage .delay-2 {
            animation-delay: 0.2s;
        }

        .homepage .delay-3 {
            animation-delay: 0.3s;
        }

        .homepage .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .homepage .animate-rise,
            .homepage .animate-float {
                animation: none;
            }
        }
    </style>
@endpush

<div class="homepage">
    @php
        $topBanners = $promotionalBanners
            ->filter(fn ($banner) => ($banner['is_active'] ?? false) && ($banner['position'] ?? null) === 'top')
            ->values();

        $middleBanners = $promotionalBanners
            ->filter(fn ($banner) => ($banner['is_active'] ?? false) && ($banner['position'] ?? null) === 'middle')
            ->values();
    @endphp

    {{-- Newsletter modal (original implementation; inspired by common ecommerce patterns) --}}
    <div
        x-data="{
            open: false,
            email: '',
            saved: false,
            show() {
                const params = new URLSearchParams(window.location.search);
                if (params.get('newsletter') === '1') {
                    this.open = true;
                    return;
                }
                if (localStorage.getItem('newsletterDismissed') === '1') return;
                this.open = true;
            },
            dismiss() {
                this.open = false;
                localStorage.setItem('newsletterDismissed', '1');
            },
            submit() {
                if (!this.email) return;
                this.saved = true;
                // TODO: wire to real newsletter endpoint/provider.
                setTimeout(() => this.dismiss(), 900);
            },
        }"
        x-init="setTimeout(() => show(), 1200)"
        x-cloak
    >
        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-[60] flex items-center justify-center px-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 bg-black/50" @click="dismiss()"></div>
            <div class="relative w-full max-w-xl rounded-3xl surface-card p-8">
                <button
                    type="button"
                    class="absolute right-5 top-5 rounded-full p-2 text-muted hover:bg-black/5"
                    aria-label="Close"
                    @click="dismiss()"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <p class="eyebrow text-xs font-semibold uppercase text-muted">
                    {{ __('frontend.common.shop_now') }}
                </p>
                <h2 class="mt-3 font-display text-3xl text-ink">
                    Updates, early drops, and offers
                </h2>
                <p class="mt-3 text-sm text-muted">
                    Subscribe to get notified when new collections land.
                </p>

                <form class="mt-6 flex flex-col gap-3 sm:flex-row" @submit.prevent="submit()">
                    <label class="sr-only" for="newsletter-email">Email</label>
                    <input
                        id="newsletter-email"
                        type="email"
                        required
                        x-model="email"
                        placeholder="you@example.com"
                        class="w-full rounded-full border border-black/10 bg-white px-5 py-3 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-[rgba(242,104,75,0.35)]"
                    >
                    <button
                        type="submit"
                        class="btn-primary inline-flex items-center justify-center rounded-full px-7 py-3 text-sm font-semibold"
                    >
                        Subscribe
                    </button>
                </form>

                <p x-show="saved" x-transition.opacity class="mt-4 text-sm font-semibold text-ink">
                    Thanks — you’re on the list.
                </p>
            </div>
        </div>
    </div>

    <section class="relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-24 right-0 h-64 w-64 rounded-full bg-[rgba(242,104,75,0.22)] blur-3xl"></div>
            <div class="absolute bottom-0 left-6 h-72 w-72 rounded-full bg-[rgba(42,166,161,0.22)] blur-3xl"></div>
        </div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,0.38fr)_minmax(0,1.25fr)_minmax(0,0.65fr)]">
                <aside class="surface-card rounded-2xl p-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="eyebrow text-xs font-semibold uppercase text-muted">
                            {{ __('frontend.categories') }}
                        </p>
                        <a
                            href="{{ route('categories.index') }}"
                            class="text-xs font-semibold text-ink hover:text-[rgba(242,104,75,1)]"
                        >
                            {{ __('frontend.common.view_all') }} →
                        </a>
                    </div>

                    <nav class="mt-4 space-y-1">
                        @foreach($navigationCategories as $category)
                            <a
                                href="{{ route('categories.show', $category->getFullPath()) }}"
                                class="group flex items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold text-ink hover:bg-white/80"
                            >
                                <span class="truncate group-hover:text-[rgba(242,104,75,1)]">
                                    {{ $category->getName() }}
                                </span>
                                <svg class="h-4 w-4 text-muted group-hover:text-[rgba(242,104,75,1)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </nav>

                    <div class="mt-6 rounded-2xl bg-surface-strong p-4">
                        <p class="text-sm font-semibold text-ink">
                            {{ __('frontend.homepage.hero_subtitle') }}
                        </p>
                        <a
                            href="{{ route('frontend.products.index') }}"
                            class="mt-4 inline-flex items-center justify-center rounded-full bg-ink px-5 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white"
                        >
                            {{ __('frontend.common.shop_now') }}
                        </a>
                    </div>
                </aside>

                <div class="hero-section relative rounded-[28px] border border-white/70 bg-white/70 shadow-[0_24px_60px_rgba(15,27,30,0.16)] overflow-hidden">
                    @if($heroCollections->isNotEmpty())
                        <div class="relative min-h-[520px] sm:min-h-[620px]">
                            @foreach($heroCollections as $index => $collection)
                                @php
                                    $heroImage = $collection->getFirstMedia('hero') ?? $collection->getFirstMedia('images');
                                    $collectionSlug = $collection->urls->first()?->slug ?? $collection->id;
                                    $collectionCount = $collection->products_count ?? $collection->products()->count();
                                @endphp

                                <article
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

                                        <div class="absolute inset-0 hero-overlay"></div>
                                    </div>

                                    <div class="relative z-10 flex h-full">
                                        <div class="grid w-full h-full content-between px-6 sm:px-10 py-10 sm:py-12">
                                            <div class="max-w-2xl space-y-6">
                                                <p class="eyebrow text-xs font-semibold uppercase text-white/75 animate-rise delay-1">
                                                    {{ __('frontend.homepage.hero_tagline') }}
                                                </p>

                                                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl leading-tight text-white animate-rise delay-2">
                                                    {{ $collection->translateAttribute('name') }}
                                                </h1>

                                                <p class="text-base sm:text-lg text-white/85 max-w-prose animate-rise delay-3">
                                                    {{ $collection->translateAttribute('description')
                                                        ? Str::limit($collection->translateAttribute('description'), 180)
                                                        : __('frontend.homepage.hero_subtitle')
                                                    }}
                                                </p>

                                                <div class="flex flex-wrap gap-3 animate-rise delay-3">
                                                    <a
                                                        href="{{ route('frontend.collections.show', $collectionSlug) }}"
                                                        class="btn-primary inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold"
                                                    >
                                                        {{ __('frontend.homepage.explore_collection') }}
                                                    </a>
                                                    <a
                                                        href="{{ route('frontend.products.index') }}"
                                                        class="btn-outline inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold"
                                                    >
                                                        {{ __('frontend.homepage.hero_secondary') }}
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-6 text-xs uppercase text-white/70">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-white/60">{{ __('frontend.products') }}</span>
                                                    <span class="text-base font-semibold text-white">{{ $collectionCount }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-white/60">{{ __('frontend.homepage.featured_collections') }}</span>
                                                    <span class="text-base font-semibold text-white">{{ $featuredCollections->count() }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="hidden md:flex flex-col gap-4 pr-8 py-10">
                                            @if($bestsellers)
                                                <a
                                                    href="{{ route('frontend.collections.show', $bestsellers->urls->first()?->slug ?? $bestsellers->id) }}"
                                                    class="glass-card rounded-2xl p-5 text-white animate-float"
                                                >
                                                    <p class="text-xs uppercase tracking-[0.3em] text-white/70">
                                                        {{ __('frontend.homepage.bestsellers') }}
                                                    </p>
                                                    <p class="mt-3 font-display text-2xl">
                                                        {{ $bestsellers->translateAttribute('name') ?? __('frontend.homepage.bestsellers') }}
                                                    </p>
                                                    <p class="mt-2 text-sm text-white/80">
                                                        {{ __('frontend.homepage.bestsellers_subtitle') }}
                                                    </p>
                                                </a>
                                            @endif

                                            @if($newArrivals)
                                                <a
                                                    href="{{ route('frontend.collections.show', $newArrivals->urls->first()?->slug ?? $newArrivals->id) }}"
                                                    class="glass-card rounded-2xl p-5 text-white"
                                                >
                                                    <p class="text-xs uppercase tracking-[0.3em] text-white/70">
                                                        {{ __('frontend.homepage.new_arrivals') }}
                                                    </p>
                                                    <p class="mt-3 font-display text-2xl">
                                                        {{ $newArrivals->translateAttribute('name') ?? __('frontend.homepage.new_arrivals') }}
                                                    </p>
                                                    <p class="mt-2 text-sm text-white/80">
                                                        {{ __('frontend.homepage.new_arrivals_subtitle') }}
                                                    </p>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if($heroCollections->count() > 1)
                            <div class="absolute inset-x-0 bottom-6 z-20">
                                <div class="mx-auto px-6 sm:px-10 flex items-center justify-between gap-4">
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
                    @else
                        <div class="relative min-h-[520px] sm:min-h-[620px]">
                            <div class="absolute inset-0 bg-gradient-to-br from-[rgba(15,27,30,0.92)] via-[rgba(15,27,30,0.75)] to-[rgba(15,27,30,0.4)]"></div>
                            <div class="relative z-10 h-full flex items-center px-6 sm:px-10">
                                <div class="max-w-xl space-y-6">
                                    <p class="eyebrow text-xs font-semibold uppercase text-white/75">
                                        {{ __('frontend.homepage.hero_tagline') }}
                                    </p>
                                    <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl text-white">
                                        {{ __('frontend.homepage.featured_collections') }}
                                    </h1>
                                    <p class="text-base sm:text-lg text-white/85">
                                        {{ __('frontend.homepage.hero_subtitle') }}
                                    </p>
                                    <div class="flex flex-wrap gap-3">
                                        <a
                                            href="{{ route('frontend.collections.index') }}"
                                            class="btn-primary inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold"
                                        >
                                            {{ __('frontend.homepage.explore_collection') }}
                                        </a>
                                        <a
                                            href="{{ route('frontend.products.index') }}"
                                            class="btn-outline inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold"
                                        >
                                            {{ __('frontend.homepage.hero_secondary') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-6">
                    @if($topBanners->isNotEmpty())
                        <div class="grid gap-5">
                            @foreach($topBanners as $banner)
                                <a
                                    href="{{ $banner['link'] ?? '#' }}"
                                    class="group relative overflow-hidden rounded-2xl bg-gray-900 shadow-[0_18px_40px_rgba(15,27,30,0.2)]"
                                >
                                    <div class="absolute inset-0">
                                        <img
                                            src="{{ $banner['image'] ?? asset('images/banners/default.jpg') }}"
                                            alt="{{ $banner['title'] ?? 'Promotional banner' }}"
                                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                        <div class="absolute inset-0 bg-gradient-to-br from-[rgba(15,27,30,0.75)] via-[rgba(15,27,30,0.5)] to-transparent"></div>
                                    </div>
                                    <div class="relative z-10 p-6 text-white">
                                        @if(!empty($banner['subtitle']))
                                            <p class="eyebrow text-[11px] font-semibold uppercase text-white/70">
                                                {{ $banner['subtitle'] }}
                                            </p>
                                        @endif
                                        @if(!empty($banner['title']))
                                            <h2 class="mt-3 font-display text-2xl">
                                                {{ $banner['title'] }}
                                            </h2>
                                        @endif
                                        @if(!empty($banner['description']))
                                            <p class="mt-3 text-sm text-white/85">
                                                {{ $banner['description'] }}
                                            </p>
                                        @endif
                                        <span class="mt-6 inline-flex items-center rounded-full bg-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white">
                                            {{ $banner['link_text'] ?? __('frontend.common.shop_now') }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="surface-card rounded-2xl p-6">
                        <p class="eyebrow text-[11px] font-semibold uppercase text-muted">
                            {{ __('frontend.homepage.featured_collections') }}
                        </p>
                        <p class="mt-4 text-lg font-semibold text-ink">
                            {{ __('frontend.homepage.featured_subtitle') }}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <a href="#featured-collections" class="rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-ink shadow-sm">
                                {{ __('frontend.homepage.featured_collections') }}
                            </a>
                            @if($bestsellers)
                                <a href="#bestsellers" class="rounded-full bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-ink shadow-sm">
                                    {{ __('frontend.homepage.bestsellers') }}
                                </a>
                            @endif
                            @if($newArrivals)
                                <a href="#new-arrivals" class="rounded-full bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-ink shadow-sm">
                                    {{ __('frontend.homepage.new_arrivals') }}
                                </a>
                            @endif
                        </div>
                        <div class="mt-6 flex items-center justify-between text-xs uppercase text-muted">
                            <span>{{ __('frontend.nav.collections') }}</span>
                            <span class="text-base font-semibold text-ink">{{ $featuredCollections->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($newArrivals && $newArrivals->products->count() > 0)
        <section id="new-arrivals" class="py-14 bg-surface-strong">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-8">
                    <div>
                        <p class="eyebrow text-xs font-semibold uppercase text-muted">
                            {{ __('frontend.homepage.new_arrivals') }}
                        </p>
                        <h2 class="font-display text-3xl sm:text-4xl text-ink">
                            {{ __('frontend.homepage.new_arrivals') }}
                        </h2>
                        <p class="mt-3 text-muted">
                            {{ __('frontend.homepage.new_arrivals_subtitle') }}
                        </p>
                    </div>
                    <a
                        href="{{ route('frontend.collections.show', $newArrivals->urls->first()?->slug ?? $newArrivals->id) }}"
                        class="text-sm font-semibold text-ink hover:text-[rgba(242,104,75,1)]"
                    >
                        {{ __('frontend.common.view_all') }} →
                    </a>
                </div>

                <div class="relative" data-carousel>
                    <button
                        type="button"
                        class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow ring-1 ring-black/5 hover:bg-white"
                        data-carousel-prev
                        aria-label="Scroll left"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow ring-1 ring-black/5 hover:bg-white"
                        data-carousel-next
                        aria-label="Scroll right"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div class="-mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto pb-2 scroll-smooth" data-carousel-track>
                        <div class="flex gap-4 min-w-max">
                            @foreach($newArrivals->products->take(12) as $product)
                                <div class="w-72 flex-shrink-0" data-carousel-item>
                                    <x-frontend.product-card :product="$product" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if(isset($categorySpotlights) && $categorySpotlights->isNotEmpty())
        @foreach($categorySpotlights as $spotlight)
            @php
                /** @var \App\Models\Category $spotCategory */
                $spotCategory = $spotlight['category'];
                $spotProducts = $spotlight['products'];
                $spotBg = $loop->even ? 'bg-surface-strong' : '';
            @endphp

            <section class="py-14 {{ $spotBg }}">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-8">
                        <div>
                            <p class="eyebrow text-xs font-semibold uppercase text-muted">
                                {{ __('frontend.categories') }}
                            </p>
                            <h2 class="font-display text-3xl sm:text-4xl text-ink">
                                {{ $spotCategory->getName() }}
                            </h2>
                            @if($spotCategory->getDescription())
                                <p class="mt-3 text-muted">
                                    {{ Str::limit($spotCategory->getDescription(), 140) }}
                                </p>
                            @endif
                        </div>
                        <a
                            href="{{ route('categories.show', $spotCategory->getFullPath()) }}"
                            class="text-sm font-semibold text-ink hover:text-[rgba(242,104,75,1)]"
                        >
                            {{ __('frontend.common.view_all') }} →
                        </a>
                    </div>

                    <div class="relative" data-carousel>
                        <button
                            type="button"
                            class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow ring-1 ring-black/5 hover:bg-white"
                            data-carousel-prev
                            aria-label="Scroll left"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow ring-1 ring-black/5 hover:bg-white"
                            data-carousel-next
                            aria-label="Scroll right"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <div class="-mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto pb-2 scroll-smooth" data-carousel-track>
                            <div class="flex gap-4 min-w-max">
                                @foreach($spotProducts as $product)
                                    <div class="w-72 flex-shrink-0" data-carousel-item>
                                        <x-frontend.product-card :product="$product" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    @endif

    <section class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="surface-card rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[rgba(42,166,161,0.15)] text-[rgba(42,166,161,1)]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h13l4 6-4 6H3l4-6-4-6z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">
                                {{ __('frontend.homepage.value_props.shipping.title') }}
                            </p>
                            <p class="text-sm text-muted">
                                {{ __('frontend.homepage.value_props.shipping.description') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="surface-card rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[rgba(242,104,75,0.15)] text-[rgba(242,104,75,1)]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">
                                {{ __('frontend.homepage.value_props.quality.title') }}
                            </p>
                            <p class="text-sm text-muted">
                                {{ __('frontend.homepage.value_props.quality.description') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="surface-card rounded-2xl p-6">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[rgba(15,27,30,0.1)] text-ink">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">
                                {{ __('frontend.homepage.value_props.returns.title') }}
                            </p>
                            <p class="text-sm text-muted">
                                {{ __('frontend.homepage.value_props.returns.description') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($featuredCollections->isNotEmpty())
        <section id="featured-collections" class="py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-10">
                    <div>
                        <p class="eyebrow text-xs font-semibold uppercase text-muted">
                            {{ __('frontend.homepage.featured_collections') }}
                        </p>
                        <h2 class="font-display text-3xl sm:text-4xl text-ink">
                            {{ __('frontend.homepage.featured_collections') }}
                        </h2>
                        <p class="mt-3 text-muted">
                            {{ __('frontend.homepage.featured_subtitle') }}
                        </p>
                    </div>
                    <a
                        href="{{ route('frontend.collections.index') }}"
                        class="text-sm font-semibold text-ink hover:text-[rgba(242,104,75,1)]"
                    >
                        {{ __('frontend.common.view_all') }} →
                    </a>
                </div>

                <div class="grid gap-6 lg:grid-cols-12">
                    @foreach($featuredCollections as $index => $collection)
                        @php
                            $collectionImage = $collection->getFirstMedia('images')
                                ?? $collection->getFirstMedia('hero');
                            $productCount = $collection->products_count ?? $collection->products()->count();
                            $url = $collection->urls->first()?->slug ?? $collection->id;

                            $span = $index === 0 ? 'lg:col-span-7' : 'lg:col-span-5';
                        @endphp

                        <a
                            href="{{ route('frontend.collections.show', $url) }}"
                            class="group relative overflow-hidden rounded-3xl {{ $span }}"
                        >
                            <div class="absolute inset-0">
                                @if($collectionImage)
                                    @include('frontend.components.responsive-image', [
                                        'media' => $collectionImage,
                                        'model' => $collection,
                                        'collectionName' => $collectionImage->collection_name ?? 'images',
                                        'conversion' => 'collection_card',
                                        'sizeType' => 'collection_card',
                                        'alt' => $collection->translateAttribute('name'),
                                        'class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
                                        'loading' => 'lazy',
                                    ])
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-gray-200 to-gray-300"></div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-[rgba(10,16,18,0.82)] via-[rgba(10,16,18,0.4)] to-transparent"></div>
                            </div>
                            <div class="relative z-10 flex h-full flex-col justify-end p-6 sm:p-8 text-white">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-display text-2xl sm:text-3xl">
                                        {{ $collection->translateAttribute('name') }}
                                    </h3>
                                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em]">
                                        {{ $productCount }} {{ __('frontend.products') }}
                                    </span>
                                </div>
                                @if($collection->translateAttribute('description'))
                                    <p class="mt-3 text-sm sm:text-base text-white/85 max-w-xl">
                                        {{ Str::limit($collection->translateAttribute('description'), 140) }}
                                    </p>
                                @endif
                                <span class="mt-4 inline-flex items-center text-sm font-semibold">
                                    {{ __('frontend.homepage.explore_collection') }} →
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($middleBanners->isNotEmpty())
        @foreach($middleBanners as $banner)
            <section class="py-14">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] items-center">
                        <div class="relative overflow-hidden rounded-3xl min-h-[260px]">
                            <img
                                src="{{ $banner['image'] ?? asset('images/banners/default.jpg') }}"
                                alt="{{ $banner['title'] ?? 'Promotional banner' }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            >
                            <div class="absolute inset-0 bg-gradient-to-r from-[rgba(10,16,18,0.78)] via-[rgba(10,16,18,0.3)] to-transparent"></div>
                        </div>
                        <div class="surface-card rounded-3xl p-8">
                            @if(!empty($banner['subtitle']))
                                <p class="eyebrow text-xs font-semibold uppercase text-muted">
                                    {{ $banner['subtitle'] }}
                                </p>
                            @endif
                            @if(!empty($banner['title']))
                                <h2 class="mt-3 font-display text-3xl text-ink">
                                    {{ $banner['title'] }}
                                </h2>
                            @endif
                            @if(!empty($banner['description']))
                                <p class="mt-4 text-sm text-muted">
                                    {{ $banner['description'] }}
                                </p>
                            @endif
                            <a
                                href="{{ $banner['link'] ?? '#' }}"
                                class="mt-6 inline-flex items-center rounded-full bg-ink px-5 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white"
                            >
                                {{ $banner['link_text'] ?? __('frontend.common.shop_now') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    @endif

    @if($bestsellers && $bestsellers->products->count() > 0)
        <section id="bestsellers" class="py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-8">
                    <div>
                        <p class="eyebrow text-xs font-semibold uppercase text-muted">
                            {{ __('frontend.homepage.bestsellers') }}
                        </p>
                        <h2 class="font-display text-3xl sm:text-4xl text-ink">
                            {{ __('frontend.homepage.bestsellers') }}
                        </h2>
                        <p class="mt-3 text-muted">
                            {{ __('frontend.homepage.bestsellers_subtitle') }}
                        </p>
                    </div>
                    @if($bestsellers->urls->first())
                        <a
                            href="{{ route('frontend.collections.show', $bestsellers->urls->first()?->slug) }}"
                            class="text-sm font-semibold text-ink hover:text-[rgba(242,104,75,1)]"
                        >
                            {{ __('frontend.common.view_all') }} →
                        </a>
                    @endif
                </div>

                <div class="relative" data-carousel>
                    <button
                        type="button"
                        class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow ring-1 ring-black/5 hover:bg-white"
                        data-carousel-prev
                        aria-label="Scroll left"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-10 h-10 w-10 items-center justify-center rounded-full bg-white/85 text-ink shadow ring-1 ring-black/5 hover:bg-white"
                        data-carousel-next
                        aria-label="Scroll right"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div class="-mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto pb-2 scroll-smooth" data-carousel-track>
                        <div class="flex gap-4 min-w-max">
                            @foreach($bestsellers->products->take(12) as $product)
                                <div class="w-72 flex-shrink-0" data-carousel-item>
                                    <x-frontend.product-card :product="$product" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
