@php
    use Lunar\Facades\CartSession;
    $cart = CartSession::current();
    $itemCount = $cart ? $cart->lines->sum('quantity') : 0;
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        loading: false,
        error: false,
        summary: null,
        lastLoadedAt: 0,
        init() {
            document.addEventListener('cartUpdated', () => {
                this.summary = null;
                this.lastLoadedAt = 0;
            });
        },
        async load() {
            const widget = this.$refs.widget;
            const url = widget?.dataset?.summaryUrl;
            if (!url) return;

            if (this.loading) return;
            if (Date.now() - this.lastLoadedAt < 8000) return;

            this.loading = true;
            this.error = false;
            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();
                this.summary = data?.cart || null;
                this.lastLoadedAt = Date.now();
            } catch (_) {
                this.error = true;
            } finally {
                this.loading = false;
            }
        },
    }"
    x-init="init()"
    @mouseenter="if (window.matchMedia('(min-width: 1024px)').matches) { open = true; load(); }"
    @mouseleave="open = false"
    @keydown.escape.window="open = false"
>
    <a
        href="{{ route('frontend.cart.index') }}"
        class="relative inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors"
        id="cart-widget"
        x-ref="widget"
        data-summary-url="{{ route('frontend.cart.summary') }}"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        @if($itemCount > 0)
            <span
                class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"
                id="cart-count"
            >
                {{ $itemCount > 99 ? '99+' : $itemCount }}
            </span>
        @endif
        <span class="hidden sm:inline">{{ __('frontend.nav.cart') ?? 'Cart' }}</span>
    </a>

    <div
        x-show="open"
        x-transition.origin.top.right
        x-cloak
        class="absolute right-0 mt-2 w-80 rounded-2xl bg-white shadow-xl ring-1 ring-black/10 overflow-hidden z-50"
    >
        <div class="p-4">
            <div class="text-sm font-semibold text-slate-900">Cart</div>

            <template x-if="loading">
                <div class="mt-3 space-y-2">
                    <div class="h-4 w-24 rounded bg-slate-100"></div>
                    <div class="h-4 w-40 rounded bg-slate-100"></div>
                </div>
            </template>

            <template x-if="!loading && error">
                <div class="mt-3 text-sm text-slate-500">
                    Unable to load cart summary.
                </div>
            </template>

            <template x-if="!loading && !error && summary && summary.has_items">
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">Items</span>
                        <span class="font-semibold text-slate-900" x-text="summary.item_count"></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">Total</span>
                        <span class="font-semibold text-slate-900" x-text="summary.grand_total?.formatted || summary.total"></span>
                    </div>
                </div>
            </template>

            <template x-if="!loading && !error && summary && !summary.has_items">
                <div class="mt-3 text-sm text-slate-500">
                    Your cart is empty.
                </div>
            </template>
        </div>

        <div class="border-t bg-slate-50 p-3 flex gap-2">
            <a
                href="{{ route('frontend.cart.index') }}"
                class="flex-1 inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-200 hover:bg-slate-100"
            >
                View cart
            </a>
            <a
                href="{{ route('frontend.checkout.index') }}"
                class="flex-1 inline-flex items-center justify-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Checkout
            </a>
        </div>
    </div>
</div>
