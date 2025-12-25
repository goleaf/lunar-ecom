@extends('layouts.app')

@section('title', 'Unsubscribe from Stock Notifications')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        @if($success)
            <div class="text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Successfully Unsubscribed</h1>
                <p class="text-gray-600 mb-4">You have been unsubscribed from back-in-stock notifications.</p>
                <p class="text-sm text-gray-500">You will no longer receive emails when this product is back in stock.</p>
                <a href="{{ url('/') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Return to Homepage</a>
            </div>
        @else
            <div class="text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Invalid Unsubscribe Link</h1>
                <p class="text-gray-600 mb-4">This unsubscribe link is invalid or has expired.</p>
                <a href="{{ url('/') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">Return to Homepage</a>
            </div>
        @endif
    </div>
</div>
@endsection

