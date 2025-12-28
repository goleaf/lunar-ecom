@php
    /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $navCategories */
@endphp

<header
    class="bg-slate-200"
    x-data="{
        mobileOpen: false,
        catOpen: false,
        catActive: 0,
        scrolled: false,
        openMobile() { this.mobileOpen = true; },
        closeMobile() { this.mobileOpen = false; },
        toggleCatDesktop() {
            this.catOpen = !this.catOpen;
            if (this.catOpen) this.catActive = 0;
        },
        closeCat() { this.catOpen = false; },
        initSticky() {
            const onScroll = () => { this.scrolled = (window.scrollY || 0) > 10; };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        },
    }"
    x-init="initSticky()"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', mobileOpen)"
    @keydown.escape.window="closeMobile(); closeCat()"
>
    {{-- Top announcement strip --}}
    <div class="bg-slate-900 text-slate-100">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3">
            <p class="text-xs sm:text-sm">
                Free shipping worldwide (limited time)
            </p>
            <a
                href="{{ route('frontend.homepage', ['newsletter' => 1]) }}"
                class="text-xs sm:text-sm font-semibold underline-offset-2 hover:underline"
            >
                Subscribe &amp; Save
            </a>
        </div>
    </div>

    {{-- Mobile off-canvas menu --}}
    <div
        x-show="mobileOpen"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 z-[70] lg:hidden"
        aria-hidden="true"
    >
        <div class="absolute inset-0 bg-black/45" @click="closeMobile()"></div>
        <div
            x-show="mobileOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-4 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-4 opacity-0"
            class="relative h-full w-[86vw] max-w-sm bg-white shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-label="Menu"
        >
            <div class="flex items-center justify-between px-5 py-4 border-b">
                <div class="text-sm font-semibold text-slate-900">Menu</div>
                <button
                    type="button"
                    class="rounded-full p-2 text-slate-600 hover:bg-slate-100"
                    @click="closeMobile()"
                    aria-label="Close menu"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="h-full overflow-y-auto px-5 py-4 space-y-6">
                <div class="flex items-center gap-3">
                    <livewire:frontend.language-selector />
                    <livewire:frontend.currency-selector />
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('frontend.products.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        {{ __('frontend.nav.products') }}
                    </a>
                    <a href="{{ route('frontend.collections.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        {{ __('frontend.nav.collections') }}
                    </a>
                    <a href="{{ route('frontend.bundles.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        {{ __('frontend.nav.bundles') }}
                    </a>
                    <a href="{{ route('frontend.brands.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        {{ __('frontend.nav.brands') }}
                    </a>
                    <a href="{{ route('categories.index') }}" class="col-span-2 rounded-xl border border-slate-200 bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        {{ __('frontend.categories') }}
                    </a>
                </div>

                @auth
                    <div class="space-y-2">
                        <a href="{{ route('frontend.addresses.index') }}" class="block text-sm font-semibold text-slate-700 hover:text-slate-900">
                            {{ __('frontend.nav.addresses') }}
                        </a>
                        <a href="{{ route('frontend.downloads.index') }}" class="block text-sm font-semibold text-slate-700 hover:text-slate-900">
                            {{ __('frontend.nav.downloads') }}
                        </a>
                    </div>
                @endauth

                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                        {{ __('frontend.categories') }}
                    </div>
                    <div class="mt-3 space-y-2">
                        @forelse($navCategories as $category)
                            @php
                                $children = $category->children ?? collect();
                            @endphp

                            @if($children->isNotEmpty())
                                <details class="rounded-xl border border-slate-200 bg-white">
                                    <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between gap-3 text-sm font-semibold text-slate-800">
                                        <span class="truncate">{{ $category->getName() }}</span>
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </summary>
                                    <div class="px-4 pb-3 space-y-1">
                                        <a href="{{ route('categories.show', $category->getFullPath()) }}" class="block text-sm font-semibold text-slate-700 hover:text-slate-900">
                                            {{ __('frontend.common.view_all') }} →
                                        </a>
                                        @foreach($children->take(12) as $child)
                                            <a
                                                href="{{ route('categories.show', $child->getFullPath()) }}"
                                                class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-900"
                                            >
                                                {{ $child->getName() }}
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a
                                    href="{{ route('categories.show', $category->getFullPath()) }}"
                                    class="block rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                >
                                    {{ $category->getName() }}
                                </a>
                            @endif
                        @empty
                            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
                                No categories yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky top-0 z-50" :class="scrolled ? 'shadow-lg' : ''">
        {{-- Main header row --}}
        <div class="border-b" :class="scrolled ? 'bg-white/90 backdrop-blur-md border-slate-200' : 'border-slate-300/70 bg-slate-100'">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" :class="scrolled ? 'py-3' : 'py-4'">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
                <div class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="lg:hidden inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white p-2 text-slate-700 hover:bg-slate-50"
                        @click="openMobile()"
                        aria-label="Open menu"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <a href="{{ route('frontend.homepage') }}" class="inline-flex items-center gap-2 text-lg font-bold text-slate-900">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white">
                            {{ mb_substr(config('app.name', 'Store'), 0, 1) }}
                        </span>
                        <span class="hidden sm:inline">{{ config('app.name', 'Store') }}</span>
                    </a>
                    <div class="flex items-center gap-3">
                        <div class="hidden lg:block">
                            <livewire:frontend.language-selector />
                        </div>
                        <div class="hidden lg:block">
                            <livewire:frontend.currency-selector />
                        </div>

                        {{-- Account links --}}
                        @auth
                            <div class="hidden lg:block relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900"
                                    @click="open = !open"
                                    aria-haspopup="true"
                                    :aria-expanded="open ? 'true' : 'false'"
                                >
                                    My account
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div
                                    x-show="open"
                                    x-transition.origin.top.right
                                    x-cloak
                                    @click.outside="open = false"
                                    class="absolute right-0 mt-2 w-56 rounded-2xl bg-white shadow-xl ring-1 ring-black/10 overflow-hidden z-50"
                                >
                                    <div class="p-2">
                                        <a href="{{ route('frontend.addresses.index') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900">
                                            {{ __('frontend.nav.addresses') }}
                                        </a>
                                        <a href="{{ route('frontend.downloads.index') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900">
                                            {{ __('frontend.nav.downloads') }}
                                        </a>
                                        @if (Route::has('dashboard'))
                                            <a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900">
                                                Dashboard
                                            </a>
                                        @endif
                                    </div>
                                    @if (Route::has('logout'))
                                        <div class="border-t bg-slate-50 p-2">
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="w-full text-left rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white hover:text-slate-900">
                                                    Log out
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            @if (Route::has('login'))
                                <div class="hidden lg:flex items-center gap-2">
                                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                                        Log in
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                                            Sign up
                                        </a>
                                    @endif
                                </div>
                            @endif
                        @endauth

                        @include('frontend.components.cart-widget')
                    </div>
                </div>

                <div class="flex-1">
                    <div class="flex w-full items-stretch">
                        {{-- Categories dropdown button (marketplace-style) --}}
                        <div
                            class="relative"
                        >
                            <button
                                type="button"
                                class="h-11 inline-flex items-center gap-2 rounded-l-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                @click="window.matchMedia('(min-width: 1024px)').matches ? toggleCatDesktop() : openMobile()"
                                aria-haspopup="true"
                                :aria-expanded="catOpen ? 'true' : 'false'"
                            >
                                <span class="hidden sm:inline">All categories</span>
                                <span class="sm:hidden">All</span>
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-show="catOpen"
                                x-transition.origin.top.left
                                x-cloak
                                @click.outside="closeCat()"
                                class="absolute left-0 mt-2 w-[52rem] max-w-[calc(100vw-2rem)] rounded-2xl bg-white shadow-xl ring-1 ring-black/10 overflow-hidden z-50"
                            >
                                <div class="grid grid-cols-12 min-h-[22rem]">
                                    <div class="col-span-5 sm:col-span-4 border-r bg-slate-50/70">
                                        <div class="p-2">
                                            @forelse($navCategories as $index => $category)
                                                <a
                                                    href="{{ route('categories.show', $category->getFullPath()) }}"
                                                    @mouseenter="catActive = {{ $index }}"
                                                    @focus="catActive = {{ $index }}"
                                                    class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-white/80"
                                                    :class="catActive === {{ $index }} ? 'bg-white shadow ring-1 ring-black/5' : ''"
                                                >
                                                    <span class="truncate">{{ $category->getName() }}</span>
                                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            @empty
                                                <div class="px-3 py-4 text-sm text-slate-500">
                                                    No categories yet.
                                                </div>
                                            @endforelse
                                        </div>
                                        <div class="border-t bg-white px-3 py-3">
                                            <a
                                                href="{{ route('categories.index') }}"
                                                class="text-sm font-semibold text-slate-700 hover:text-slate-900"
                                            >
                                                {{ __('frontend.common.view_all') }} →
                                            </a>
                                        </div>
                                    </div>

                                    <div class="col-span-7 sm:col-span-8 p-4">
                                        @foreach($navCategories as $index => $category)
                                            @php
                                                $children = $category->children ?? collect();
                                            @endphp

                                            <div x-show="catActive === {{ $index }}" x-transition.opacity>
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="text-sm font-semibold text-slate-900">
                                                        {{ $category->getName() }}
                                                    </div>
                                                    <a
                                                        href="{{ route('categories.show', $category->getFullPath()) }}"
                                                        class="text-sm font-semibold text-slate-700 hover:text-slate-900"
                                                    >
                                                        {{ __('frontend.common.view_all') }} →
                                                    </a>
                                                </div>

                                                <div class="mt-4 grid grid-cols-2 gap-2">
                                                    @forelse($children->take(12) as $child)
                                                        <a
                                                            href="{{ route('categories.show', $child->getFullPath()) }}"
                                                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-slate-300 hover:text-slate-900 hover:bg-slate-50"
                                                        >
                                                            {{ $child->getName() }}
                                                        </a>
                                                    @empty
                                                        <div class="col-span-2 rounded-xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-500">
                                                            No subcategories yet.
                                                        </div>
                                                    @endforelse
                                                </div>

                                                <div class="mt-4 rounded-2xl bg-slate-900 px-5 py-4 text-white">
                                                    <div class="text-xs uppercase tracking-[0.3em] text-white/70">
                                                        Explore
                                                    </div>
                                                    <div class="mt-2 text-lg font-semibold">
                                                        Browse {{ $category->getName() }}
                                                    </div>
                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        <a
                                                            href="{{ route('categories.show', $category->getFullPath()) }}"
                                                            class="inline-flex items-center justify-center rounded-full bg-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white hover:bg-white/20"
                                                        >
                                                            Shop now
                                                        </a>
                                                        <a
                                                            href="{{ route('frontend.products.index') }}"
                                                            class="inline-flex items-center justify-center rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white hover:bg-white/15"
                                                        >
                                                            All products
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Search mega menu --}}
                        <div class="flex-1">
                            <livewire:frontend.search-mega-menu />
                        </div>
                    </div>
                </div>

                {{-- Actions moved into the logo row for unique cart widget ID --}}
            </div>
        </div>
        </div>

        {{-- Category menu row --}}
        <nav class="hidden lg:block border-b border-slate-200" :class="scrolled ? 'bg-white/90 backdrop-blur-md' : 'bg-white'">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6 overflow-x-auto py-3">
                <a href="{{ route('frontend.products.index') }}" class="whitespace-nowrap text-sm font-semibold text-slate-700 hover:text-slate-900">
                    {{ __('frontend.nav.products') }}
                </a>
                <a href="{{ route('frontend.collections.index') }}" class="whitespace-nowrap text-sm font-semibold text-slate-700 hover:text-slate-900">
                    {{ __('frontend.nav.collections') }}
                </a>
                <a href="{{ route('frontend.bundles.index') }}" class="whitespace-nowrap text-sm font-semibold text-slate-700 hover:text-slate-900">
                    {{ __('frontend.nav.bundles') }}
                </a>
                <a href="{{ route('frontend.brands.index') }}" class="whitespace-nowrap text-sm font-semibold text-slate-700 hover:text-slate-900">
                    {{ __('frontend.nav.brands') }}
                </a>

                @foreach($navCategories->take(6) as $category)
                    @php
                        $children = $category->children ?? collect();
                    @endphp

                    @if($children->isNotEmpty())
                        <div class="relative group">
                            <a
                                href="{{ route('categories.show', $category->getFullPath()) }}"
                                class="whitespace-nowrap text-sm font-semibold text-slate-700 hover:text-slate-900 inline-flex items-center gap-1"
                            >
                                {{ $category->getName() }}
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </a>

                            <div class="absolute left-0 top-full mt-2 hidden group-hover:block z-50">
                                <div class="w-80 rounded-2xl bg-white shadow-xl ring-1 ring-black/10 overflow-hidden">
                                    <div class="p-3">
                                        <div class="grid grid-cols-2 gap-1">
                                            @foreach($children->take(12) as $child)
                                                <a
                                                    href="{{ route('categories.show', $child->getFullPath()) }}"
                                                    class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900"
                                                >
                                                    {{ $child->getName() }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="border-t bg-slate-50 px-3 py-2">
                                        <a
                                            href="{{ route('categories.show', $category->getFullPath()) }}"
                                            class="text-sm font-semibold text-slate-700 hover:text-slate-900"
                                        >
                                            {{ __('frontend.common.view_all') }} →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <a
                            href="{{ route('categories.show', $category->getFullPath()) }}"
                            class="whitespace-nowrap text-sm font-semibold text-slate-700 hover:text-slate-900"
                        >
                            {{ $category->getName() }}
                        </a>
                    @endif
                @endforeach
                </div>
            </div>
        </nav>
    </div>
</header>

