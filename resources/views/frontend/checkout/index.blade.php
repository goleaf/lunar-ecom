@extends('frontend.layout')

@section('title', __('frontend.checkout.title'))

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Checkout</h1>

    <form action="{{ route('frontend.checkout.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @csrf

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Shipping Address</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="shipping_address_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="shipping_address[first_name]" id="shipping_address_first_name" required class="border rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label for="shipping_address_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="shipping_address[last_name]" id="shipping_address_last_name" required class="border rounded px-3 py-2 w-full">
                        </div>
                    </div>
                    <div>
                        <label for="shipping_address_line_one" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                        <input type="text" name="shipping_address[line_one]" id="shipping_address_line_one" required class="border rounded px-3 py-2 w-full">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="shipping_address_city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" name="shipping_address[city]" id="shipping_address_city" required class="border rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label for="shipping_address_postcode" class="block text-sm font-medium text-gray-700 mb-1">Postcode</label>
                            <input type="text" name="shipping_address[postcode]" id="shipping_address_postcode" required class="border rounded px-3 py-2 w-full">
                        </div>
                    </div>
                    <div>
                        <label for="shipping_address_country_id" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select name="shipping_address[country_id]" id="shipping_address_country_id" required class="border rounded px-3 py-2 w-full">
                            <option value="">Select Country</option>
                            @foreach(\Lunar\Models\Country::all() as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Billing Address</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="billing_address_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="billing_address[first_name]" id="billing_address_first_name" required class="border rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label for="billing_address_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="billing_address[last_name]" id="billing_address_last_name" required class="border rounded px-3 py-2 w-full">
                        </div>
                    </div>
                    <div>
                        <label for="billing_address_line_one" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                        <input type="text" name="billing_address[line_one]" id="billing_address_line_one" required class="border rounded px-3 py-2 w-full">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="billing_address_city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" name="billing_address[city]" id="billing_address_city" required class="border rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label for="billing_address_postcode" class="block text-sm font-medium text-gray-700 mb-1">Postcode</label>
                            <input type="text" name="billing_address[postcode]" id="billing_address_postcode" required class="border rounded px-3 py-2 w-full">
                        </div>
                    </div>
                    <div>
                        <label for="billing_address_country_id" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select name="billing_address[country_id]" id="billing_address_country_id" required class="border rounded px-3 py-2 w-full">
                            <option value="">Select Country</option>
                            @foreach(\Lunar\Models\Country::all() as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                <div class="space-y-4 mb-6">
                    @foreach($cart->lines as $line)
                        <div class="flex justify-between">
                            <span>{{ $line->purchasable->product->translateAttribute('name') ?? 'Product' }}</span>
                            <span>{{ $line->total->formatted }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="border-t pt-4 space-y-2">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span>{{ $cart->subTotal->formatted }}</span>
                    </div>
                    @if($cart->taxTotal && $cart->taxTotal->value > 0)
                        <div class="flex justify-between">
                            <span>Tax:</span>
                            <span>{{ $cart->taxTotal->formatted }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-lg pt-2 border-t">
                        <span>Total:</span>
                        <span>{{ $cart->total->formatted }}</span>
                    </div>
                </div>
                <button type="submit" class="mt-6 w-full bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    Place Order
                </button>
            </div>
        </div>
    </form>
</div>
@endsection



