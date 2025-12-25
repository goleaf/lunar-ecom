@extends('storefront.layout')

@section('title', __('storefront.cart.title'))

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">{{ __('storefront.cart.title') }}</h1>

    @if($cart && $cart->lines->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('storefront.cart.product') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('storefront.cart.price') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('storefront.cart.quantity') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('storefront.cart.total') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('storefront.cart.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($cart->lines as $line)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $line->purchasable->product->translateAttribute('name') ?? 'Product' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            SKU: {{ $line->purchasable->sku }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $line->subTotal->formatted }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form action="{{ route('storefront.cart.update', $line->id) }}" method="POST" class="flex items-center cart-update-form" data-line-id="{{ $line->id }}">
                                    @csrf
                                    @method('PUT')
                                    <input 
                                        type="number" 
                                        name="quantity" 
                                        value="{{ $line->quantity }}" 
                                        min="0" 
                                        max="999" 
                                        class="border rounded px-2 py-1 w-20 mr-2 quantity-input"
                                        data-line-id="{{ $line->id }}"
                                    >
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm">{{ __('storefront.cart.update') }}</button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 line-total" data-line-id="{{ $line->id }}">
                                {{ $line->total->formatted }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form action="{{ route('storefront.cart.remove', $line->id) }}" method="POST" class="inline cart-remove-form" data-line-id="{{ $line->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">{{ __('storefront.cart.remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <!-- Discount/Coupon Code Section -->
            <div class="mb-6 pb-6 border-b">
                <h3 class="text-lg font-semibold mb-3">{{ __('storefront.cart.discount_code') ?? 'Discount Code' }}</h3>
                @if($cart->coupon_code)
                    <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded p-3 mb-3">
                        <div class="flex items-center">
                            <span class="text-green-700 font-medium">{{ $cart->coupon_code }}</span>
                            @if($cart->discountTotal && $cart->discountTotal->value > 0)
                                <span class="ml-2 text-sm text-green-600">
                                    ({{ __('storefront.cart.discount_applied') ?? 'Discount applied' }}: {{ $cart->discountTotal->formatted }})
                                </span>
                            @endif
                        </div>
                        <form action="{{ route('storefront.cart.discount.remove') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                {{ __('storefront.cart.remove_discount') ?? 'Remove' }}
                            </button>
                        </form>
                    </div>
                @else
                    <form action="{{ route('storefront.cart.discount.apply') }}" method="POST" class="flex gap-2 discount-form">
                        @csrf
                        <input 
                            type="text" 
                            name="coupon_code" 
                            placeholder="{{ __('storefront.cart.enter_coupon') ?? 'Enter coupon code' }}"
                            class="flex-1 border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        <button 
                            type="submit" 
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors"
                        >
                            {{ __('storefront.cart.apply_discount') ?? 'Apply' }}
                        </button>
                    </form>
                @endif
            </div>

            <!-- Cart Totals -->
            <div class="space-y-3 mb-4" id="cart-totals">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">{{ __('storefront.cart.subtotal') }}:</span>
                    <span class="text-lg font-bold" id="cart-subtotal">{{ $cart->subTotal->formatted }}</span>
                </div>
                @if($cart->discountTotal && $cart->discountTotal->value > 0)
                    <div class="flex justify-between items-center text-green-600" id="discount-row">
                        <span class="text-lg font-semibold">{{ __('storefront.cart.discount') ?? 'Discount' }}:</span>
                        <span class="text-lg font-bold" id="cart-discount">-{{ $cart->discountTotal->formatted }}</span>
                    </div>
                @endif
                @if($cart->shippingTotal && $cart->shippingTotal->value > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold">{{ __('storefront.cart.shipping') }}:</span>
                        <span class="text-lg font-bold" id="cart-shipping">{{ $cart->shippingTotal->formatted }}</span>
                    </div>
                @endif
                @if($cart->taxTotal && $cart->taxTotal->value > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold">{{ __('storefront.cart.tax') }}:</span>
                        <span class="text-lg font-bold" id="cart-tax">{{ $cart->taxTotal->formatted }}</span>
                    </div>
                @endif
            </div>
            <div class="flex justify-between items-center pt-4 border-t">
                <span class="text-xl font-bold">{{ __('storefront.cart.total_label') }}:</span>
                <span class="text-xl font-bold" id="cart-total">{{ $cart->total->formatted }}</span>
            </div>
            <div class="mt-6 space-y-3">
                <a href="{{ route('storefront.checkout.index') }}" class="block w-full text-center bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 transition-colors">
                    {{ __('storefront.cart.proceed_to_checkout') }}
                </a>
                <a href="{{ route('storefront.products.index') }}" class="block w-full text-center bg-gray-200 text-gray-800 px-6 py-3 rounded hover:bg-gray-300 transition-colors">
                    {{ __('storefront.cart.continue_shopping') }}
                </a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <p class="text-gray-600 mb-4">{{ __('storefront.cart.empty') }}</p>
            <a href="{{ route('storefront.products.index') }}" class="text-blue-600 hover:text-blue-800">
                {{ __('storefront.cart.continue_shopping') }} →
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle cart line updates via AJAX
    document.querySelectorAll('.cart-update-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const lineId = this.dataset.lineId;
            const url = this.action;
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated totals
                    window.location.reload();
                } else {
                    alert(data.message || 'Error updating cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });

    // Handle cart line removal via AJAX
    document.querySelectorAll('.cart-remove-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.action;
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated cart
                    window.location.reload();
                } else {
                    alert(data.message || 'Error removing item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });

    // Handle discount application via AJAX
    const discountForm = document.querySelector('.discount-form');
    if (discountForm) {
        discountForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.action;
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Invalid coupon code');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }

    // Trigger cart update event for cart widget
    document.dispatchEvent(new Event('cartUpdated'));
});
</script>
@endpush
@endsection


