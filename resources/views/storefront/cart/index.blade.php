@extends('storefront.layout')

@section('title', 'Shopping Cart')

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Shopping Cart</h1>

    @if($cart && $cart->lines->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                                <form action="{{ route('storefront.cart.update', $line->id) }}" method="POST" class="flex items-center">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" max="999" class="border rounded px-2 py-1 w-20 mr-2">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm">Update</button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $line->total->formatted }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form action="{{ route('storefront.cart.remove', $line->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <span class="text-lg font-semibold">Subtotal:</span>
                <span class="text-lg font-bold">{{ $cart->subTotal->formatted }}</span>
            </div>
            @if($cart->taxTotal && $cart->taxTotal->value > 0)
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-semibold">Tax:</span>
                    <span class="text-lg font-bold">{{ $cart->taxTotal->formatted }}</span>
                </div>
            @endif
            @if($cart->shippingTotal && $cart->shippingTotal->value > 0)
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-semibold">Shipping:</span>
                    <span class="text-lg font-bold">{{ $cart->shippingTotal->formatted }}</span>
                </div>
            @endif
            <div class="flex justify-between items-center pt-4 border-t">
                <span class="text-xl font-bold">Total:</span>
                <span class="text-xl font-bold">{{ $cart->total->formatted }}</span>
            </div>
            <div class="mt-6">
                <a href="{{ route('storefront.checkout.index') }}" class="block w-full text-center bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    Proceed to Checkout
                </a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <p class="text-gray-600 mb-4">Your cart is empty.</p>
            <a href="{{ route('storefront.products.index') }}" class="text-blue-600 hover:text-blue-800">
                Continue Shopping →
            </a>
        </div>
    @endif
</div>
@endsection


