@php
    use Lunar\Facades\CartSession;
    $cart = CartSession::current();
    $itemCount = $cart ? $cart->lines->sum('quantity') : 0;
@endphp

<a 
    href="{{ route('frontend.cart.index') }}" 
    class="relative inline-flex items-center text-gray-700 hover:text-gray-900 transition-colors"
    id="cart-widget"
    data-summary-url="{{ route('frontend.cart.summary') }}"
>
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
    </svg>
    @if($itemCount > 0)
        <span 
            class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"
            id="cart-count"
        >
            {{ $itemCount > 99 ? '99+' : $itemCount }}
        </span>
    @endif
    <span class="ml-2 hidden sm:inline">{{ __('frontend.nav.cart') ?? 'Cart' }}</span>
</a>
