@extends('frontend.layout')

@section('title', 'Unsubscribed')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white shadow rounded-lg p-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900">You are unsubscribed</h1>
        <p class="text-gray-600 mt-4">You will no longer receive notifications for {{ $product->translateAttribute('name') }}.</p>
        <div class="mt-6">
            <a href="{{ route('frontend.products.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Browse products</a>
        </div>
    </div>
</div>
@endsection

