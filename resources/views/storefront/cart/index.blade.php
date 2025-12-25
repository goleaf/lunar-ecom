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

            <!-- Cart Totals - Always show all transparency fields -->
            <div class="space-y-3 mb-4" id="cart-totals">
                <!-- Subtotal (pre-discount) - Always shown -->
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">Subtotal (pre-discount):</span>
                    <span class="text-lg font-bold" id="cart-subtotal-pre-discount">
                        {{ $cartBreakdown['subtotal_pre_discount']['formatted'] }}
                    </span>
                </div>
                
                <!-- Total Discounts - Always shown -->
                <div class="flex justify-between items-center {{ $cartBreakdown['total_discounts']['value'] > 0 ? 'text-green-600' : '' }}" id="discount-row">
                    <span class="text-lg font-semibold">Total Discounts:</span>
                    <span class="text-lg font-bold" id="cart-discount">
                        @if($cartBreakdown['total_discounts']['value'] > 0)
                            -{{ $cartBreakdown['total_discounts']['formatted'] }}
                        @else
                            {{ $cartBreakdown['total_discounts']['formatted'] }}
                        @endif
                    </span>
                </div>
                
                <!-- Discount Breakdown (if discounts applied) -->
                @if(!empty($cartBreakdown['discount_breakdown']))
                    <div class="ml-4 space-y-1 text-sm text-gray-600">
                        @foreach($cartBreakdown['discount_breakdown'] as $discount)
                            <div class="flex justify-between">
                                <span>{{ $discount['name'] }}</span>
                                <span class="text-green-600">-{{ $discount['amount']['formatted'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Subtotal (after discount) -->
                <div class="flex justify-between items-center pt-2 border-t">
                    <span class="text-md font-medium">Subtotal (after discount):</span>
                    <span class="text-md font-semibold" id="cart-subtotal-discounted">
                        {{ $cartBreakdown['subtotal_discounted']['formatted'] }}
                    </span>
                </div>
                
                <!-- Shipping Cost - Always shown -->
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">{{ __('storefront.cart.shipping') ?? 'Shipping' }}:</span>
                    <span class="text-lg font-bold" id="cart-shipping">
                        {{ $cartBreakdown['shipping_total']['formatted'] }}
                    </span>
                </div>
                
                <!-- Shipping Breakdown (if shipping applied) -->
                @if(!empty($cartBreakdown['shipping_breakdown']))
                    <div class="ml-4 space-y-1 text-sm text-gray-600">
                        @foreach($cartBreakdown['shipping_breakdown'] as $shipping)
                            <div class="flex justify-between">
                                <span>{{ $shipping['name'] }}</span>
                                <span>{{ $shipping['amount']['formatted'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Tax Breakdown - Always shown -->
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold">{{ __('storefront.cart.tax') ?? 'Tax' }}:</span>
                    <span class="text-lg font-bold" id="cart-tax">
                        {{ $cartBreakdown['tax_total']['formatted'] }}
                    </span>
                </div>
                
                <!-- Tax Breakdown Details -->
                @if(!empty($cartBreakdown['tax_breakdown']))
                    <div class="ml-4 space-y-1 text-sm text-gray-600">
                        @foreach($cartBreakdown['tax_breakdown'] as $tax)
                            <div class="flex justify-between">
                                <span>{{ $tax['name'] }} ({{ number_format($tax['rate'], 2) }}%)</span>
                                <span>{{ $tax['amount']['formatted'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <!-- Grand Total - Always shown -->
            <div class="flex justify-between items-center pt-4 border-t">
                <span class="text-xl font-bold">{{ __('storefront.cart.total_label') ?? 'Grand Total' }}:</span>
                <span class="text-xl font-bold" id="cart-total">
                    {{ $cartBreakdown['grand_total']['formatted'] }}
                </span>
            </div>
            
            <!-- Audit Trail of Applied Rules -->
            @if(!empty($cartBreakdown['applied_rules']))
                <div class="mt-4 p-3 bg-gray-50 rounded text-sm">
                    <div class="font-semibold mb-2">Applied Discount Rules:</div>
                    <div class="space-y-1">
                        @foreach($cartBreakdown['applied_rules'] as $rule)
                            <div class="flex justify-between">
                                <span>{{ $rule['rule_name'] }}</span>
                                @if($rule['coupon_code'])
                                    <span class="text-gray-500">({{ $rule['coupon_code'] }})</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
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


