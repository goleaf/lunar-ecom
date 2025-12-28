@php
    /** @var \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $navCategories */
@endphp

<header class="bg-slate-200">
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

    {{-- Main header row --}}
    <div class="border-b border-slate-300/70 bg-slate-100">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
                <div class="flex items-center justify-between gap-3">
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

                        @auth
                            <a href="{{ route('frontend.addresses.index') }}" class="hidden lg:inline text-sm font-semibold text-slate-700 hover:text-slate-900">
                                {{ __('frontend.nav.addresses') }}
                            </a>
                            <a href="{{ route('frontend.downloads.index') }}" class="hidden lg:inline text-sm font-semibold text-slate-700 hover:text-slate-900">
                                {{ __('frontend.nav.downloads') }}
                            </a>
                        @endauth

                        @include('frontend.components.cart-widget')
                    </div>
                </div>

                <div class="flex-1">
                    <div class="flex w-full items-stretch">
                        {{-- Categories dropdown button (marketplace-style) --}}
                        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                            <button
                                type="button"
                                class="h-11 inline-flex items-center gap-2 rounded-l-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                @click="open = !open"
                                aria-haspopup="true"
                                :aria-expanded="open ? 'true' : 'false'"
                            >
                                <span class="hidden sm:inline">{{ __('frontend.categories') }}</span>
                                <span class="sm:hidden">Cat</span>
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-show="open"
                                x-transition.origin.top.left
                                x-cloak
                                @click.outside="open = false"
                                class="absolute left-0 mt-2 w-72 rounded-2xl bg-white shadow-xl ring-1 ring-black/10 overflow-hidden z-50"
                            >
                                <div class="p-2">
                                    @forelse($navCategories as $category)
                                        <a
                                            href="{{ route('categories.show', $category->getFullPath()) }}"
                                            class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
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
    <nav class="bg-white border-b border-slate-200">
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
</header>

