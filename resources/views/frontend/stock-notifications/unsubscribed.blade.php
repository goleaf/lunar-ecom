@extends('frontend.layout')

@section('title', __('frontend.stock_notifications.unsubscribed_title'))

@section('content')
<div class="px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-8 text-center">
            @if($success)
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-4 text-green-600">{{ __('frontend.stock_notifications.unsubscribed_title') }}</h1>
                <p class="text-gray-600 mb-6">{{ __('frontend.stock_notifications.unsubscribed_message') }}</p>
            @else
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-4 text-red-600">{{ __('frontend.stock_notifications.invalid_token_title') }}</h1>
                <p class="text-gray-600 mb-6">{{ __('frontend.stock_notifications.invalid_token_message') }}</p>
            @endif
            
            <a href="{{ route('frontend.products.index') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                {{ __('frontend.stock_notifications.back_to_products') }}
            </a>
        </div>
    </div>
</div>
@endsection


