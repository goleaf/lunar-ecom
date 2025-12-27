@extends('frontend.layout')

@section('title', __('frontend.checkout.confirmation_title'))

@section('content')
<div class="px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow p-8 text-center mb-6">
            <h1 class="text-3xl font-bold text-green-600 mb-2">{{ __('frontend.checkout.order_confirmed') }}</h1>
            <p class="text-gray-600 mb-4">{{ __('frontend.checkout.order_confirmed_message') }}</p>
            <p class="text-lg font-semibold">{{ __('frontend.checkout.order_number', ['reference' => $order->reference]) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">{{ __('frontend.checkout.order_details') }}</h2>
            <div class="space-y-4">
                <div>
                    <strong>{{ __('frontend.checkout.order_reference') }}:</strong> {{ $order->reference }}
                </div>
                <div>
                    <strong>{{ __('frontend.cart.total_label') }}:</strong> {{ $order->total->formatted }}
                </div>
                <div>
                    <strong>{{ __('frontend.checkout.status') }}:</strong> {{ $order->status }}
                </div>
                @if($order->placed_at)
                    <div>
                        <strong>{{ __('frontend.checkout.placed_at') }}:</strong> {{ $order->placed_at->format('F j, Y g:i A') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">{{ __('frontend.checkout.order_items') }}</h2>
            <div class="space-y-4">
                @foreach($order->lines as $line)
                    <div class="flex justify-between items-center border-b pb-4">
                        <div>
                            <p class="font-semibold">{{ $line->purchasable->product->translateAttribute('name') ?? 'Product' }}</p>
                            <p class="text-sm text-gray-600">{{ __('frontend.product.quantity') }}: {{ $line->quantity }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ $line->total->formatted }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('frontend.home') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                {{ __('frontend.cart.continue_shopping') }}
            </a>
        </div>
    </div>
</div>
@endsection



