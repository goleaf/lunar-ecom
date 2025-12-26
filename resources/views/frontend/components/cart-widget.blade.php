@php
    use Lunar\Facades\CartSession;
    $cart = CartSession::current();
    $itemCount = $cart ? $cart->lines->sum('quantity') : 0;
@endphp

<a 
    href="{{ route('storefront.cart.index') }}" 
    class="relative inline-flex items-center text-gray-700 hover:text-gray-900 transition-colors"
    id="cart-widget"
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
    <span class="ml-2 hidden sm:inline">{{ __('storefront.nav.cart') ?? 'Cart' }}</span>
</a>

@push('scripts')
<script>
    // Update cart count via AJAX
    function updateCartCount() {
        fetch('{{ route('storefront.cart.summary') }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            const countElement = document.getElementById('cart-count');
            const itemCount = data.cart?.item_count || 0;
            
            if (itemCount > 0) {
                if (!countElement) {
                    const cartWidget = document.getElementById('cart-widget');
                    const badge = document.createElement('span');
                    badge.id = 'cart-count';
                    badge.className = 'absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center';
                    cartWidget.appendChild(badge);
                }
                const badge = document.getElementById('cart-count');
                badge.textContent = itemCount > 99 ? '99+' : itemCount;
                badge.style.display = 'flex';
            } else {
                if (countElement) {
                    countElement.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
    }

    // Update cart count on page load
    document.addEventListener('DOMContentLoaded', updateCartCount);

    // Listen for cart updates (can be triggered by other scripts)
    document.addEventListener('cartUpdated', updateCartCount);
</script>
@endpush

