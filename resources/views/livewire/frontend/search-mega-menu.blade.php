@php
    use Illuminate\Support\Str;

    $trimmed = trim($query ?? '');
    $queryLen = mb_strlen($trimmed);
    $viewAllIndex = max(count($flatKeys ?? []) - 1, 0);
@endphp

<div class="relative" wire:click.outside="close">
    <form wire:submit.prevent="submit">
        <div class="relative">
            <input
                type="text"
                inputmode="search"
                autocomplete="off"
                placeholder="{{ __('frontend.nav.search_placeholder') }}"
                class="w-full border rounded px-4 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                wire:model.live.debounce.250ms="query"
                wire:focus="openDropdown"
                wire:keydown.escape="close"
                wire:keydown.arrow-down.prevent="next"
                wire:keydown.arrow-up.prevent="prev"
                wire:keydown.enter.prevent="goActive"
            />

            <button
                type="submit"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                aria-label="{{ __('frontend.nav.search_placeholder') }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
        </div>
    </form>

    @if($open)
        <div class="absolute z-50 mt-2 right-0 w-[46rem] max-w-[calc(100vw-2rem)] bg-white border rounded-xl shadow-lg overflow-hidden">
            <div class="relative">
                <div wire:loading.flex wire:target="query" class="absolute inset-0 bg-white/70 backdrop-blur-sm items-center justify-center z-10">
                    <div class="text-sm text-gray-600">Searching…</div>
                </div>

                @if($queryLen < 2)
                    <div class="p-3">
                        @if(count($history) > 0)
                            <div class="mb-3">
                                <div class="text-xs font-semibold text-gray-500 px-2 mb-2">Recent</div>
                                <div class="space-y-1">
                                    @foreach($history as $term)
                                        <button
                                            type="button"
                                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-50 text-sm text-gray-900"
                                            wire:click="selectHistory(@js($term))"
                                        >
                                            {{ $term }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(count($popular) > 0)
                            <div>
                                <div class="text-xs font-semibold text-gray-500 px-2 mb-2">Popular</div>
                                <div class="flex flex-wrap gap-2 px-2">
                                    @foreach($popular as $term)
                                        <button
                                            type="button"
                                            class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-sm text-gray-800"
                                            wire:click="selectPopular(@js($term))"
                                        >
                                            {{ $term }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(count($history) === 0 && count($popular) === 0)
                            <div class="p-6 text-center text-sm text-gray-500">
                                Start typing to search products, brands, and categories.
                            </div>
                        @endif
                    </div>
                @else
                    @php
                        $categories = $groups['categories'] ?? [];
                        $brands = $groups['brands'] ?? [];
                        $products = $groups['products'] ?? [];
                        $hasAny = count($categories) + count($brands) + count($products) > 0;
                    @endphp

                    <div class="grid grid-cols-12 divide-x">
                        <div class="col-span-4 p-3">
                            <div class="text-xs font-semibold text-gray-500 px-2 mb-2">Categories</div>
                            @if(count($categories) > 0)
                                <div class="space-y-1">
                                    @foreach($categories as $item)
                                        <a
                                            href="{{ $item['url'] }}"
                                            class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50 {{ $activeIndex === $item['index'] ? 'bg-blue-50 ring-1 ring-blue-200' : '' }}"
                                        >
                                            <div class="h-10 w-10 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center shrink-0">
                                                @if(!empty($item['image_url']))
                                                    <img src="{{ $item['image_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                                @else
                                                    <span class="text-xs font-semibold text-gray-500">
                                                        {{ Str::upper(Str::substr($item['title'] ?? '', 0, 2)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">{{ $item['title'] }}</div>
                                                @if(!empty($item['subtitle']))
                                                    <div class="text-xs text-gray-500 truncate">{{ $item['subtitle'] }}</div>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="px-2 py-3 text-sm text-gray-500">No category matches.</div>
                            @endif
                        </div>

                        <div class="col-span-4 p-3">
                            <div class="text-xs font-semibold text-gray-500 px-2 mb-2">Brands</div>
                            @if(count($brands) > 0)
                                <div class="space-y-1">
                                    @foreach($brands as $item)
                                        <a
                                            href="{{ $item['url'] }}"
                                            class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50 {{ $activeIndex === $item['index'] ? 'bg-blue-50 ring-1 ring-blue-200' : '' }}"
                                        >
                                            <div class="h-10 w-10 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center shrink-0">
                                                @if(!empty($item['image_url']))
                                                    <img src="{{ $item['image_url'] }}" alt="" class="h-full w-full object-contain" loading="lazy">
                                                @else
                                                    <span class="text-xs font-semibold text-gray-500">
                                                        {{ Str::upper(Str::substr($item['title'] ?? '', 0, 2)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">{{ $item['title'] }}</div>
                                                @if(!empty($item['subtitle']))
                                                    <div class="text-xs text-gray-500 truncate">{{ $item['subtitle'] }}</div>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="px-2 py-3 text-sm text-gray-500">No brand matches.</div>
                            @endif
                        </div>

                        <div class="col-span-4 p-3">
                            <div class="text-xs font-semibold text-gray-500 px-2 mb-2">Products</div>
                            @if(count($products) > 0)
                                <div class="space-y-1">
                                    @foreach($products as $item)
                                        <a
                                            href="{{ $item['url'] }}"
                                            class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50 {{ $activeIndex === $item['index'] ? 'bg-blue-50 ring-1 ring-blue-200' : '' }}"
                                        >
                                            <div class="h-10 w-10 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center shrink-0">
                                                @if(!empty($item['image_url']))
                                                    <img src="{{ $item['image_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                                @else
                                                    <span class="text-xs font-semibold text-gray-500">
                                                        {{ Str::upper(Str::substr($item['title'] ?? '', 0, 2)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">{{ $item['title'] }}</div>
                                                @if(!empty($item['subtitle']))
                                                    <div class="text-xs text-gray-500 truncate">{{ $item['subtitle'] }}</div>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="px-2 py-3 text-sm text-gray-500">No product matches.</div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 px-3 py-2 bg-gray-50 border-t">
                        <button
                            type="button"
                            class="text-sm text-blue-700 hover:text-blue-900 font-medium {{ $activeIndex === $viewAllIndex ? 'underline' : '' }}"
                            wire:click="submit"
                        >
                            View all results for “{{ $trimmed }}”
                        </button>
                        <div class="text-xs text-gray-500 whitespace-nowrap">
                            ↑ ↓ to navigate • Enter to open • Esc to close
                        </div>
                    </div>

                    @if(!$hasAny)
                        <div class="p-4 text-center text-sm text-gray-500 border-t">
                            No matches found for “{{ $trimmed }}”.
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>



