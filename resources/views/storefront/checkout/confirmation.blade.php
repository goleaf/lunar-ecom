@extends('storefront.layout')

@section('title', 'Order Confirmation')

@section('content')
<div class="px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow p-8 text-center mb-6">
            <h1 class="text-3xl font-bold text-green-600 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600 mb-4">Thank you for your order. Your order has been successfully placed.</p>
            <p class="text-lg font-semibold">Order #{{ $order->reference }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Order Details</h2>
            <div class="space-y-4">
                <div>
                    <strong>Order Reference:</strong> {{ $order->reference }}
                </div>
                <div>
                    <strong>Total:</strong> {{ $order->total->formatted }}
                </div>
                <div>
                    <strong>Status:</strong> {{ $order->status }}
                </div>
                @if($order->placed_at)
                    <div>
                        <strong>Placed At:</strong> {{ $order->placed_at->format('F j, Y g:i A') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Order Items</h2>
            <div class="space-y-4">
                @foreach($order->lines as $line)
                    <div class="flex justify-between items-center border-b pb-4">
                        <div>
                            <p class="font-semibold">{{ $line->purchasable->product->translateAttribute('name') ?? 'Product' }}</p>
                            <p class="text-sm text-gray-600">Quantity: {{ $line->quantity }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ $line->total->formatted }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('storefront.home') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
@endsection


