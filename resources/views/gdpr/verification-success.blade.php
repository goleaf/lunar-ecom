@extends('frontend.layout')

@section('title', 'GDPR Request Verified')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg p-6 text-center">
        <div class="mb-4">
            <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-4">Request Verified Successfully</h1>
        
        <p class="text-gray-600 mb-6">
            Your GDPR request has been verified and is now being processed.
        </p>

        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm text-gray-600 mb-2">
                <strong>Request Type:</strong> {{ ucfirst($request->type) }}
            </p>
            <p class="text-sm text-gray-600 mb-2">
                <strong>Status:</strong> 
                <span class="px-2 py-1 text-xs font-medium rounded-full 
                    {{ $request->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $request->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}">
                    {{ ucfirst($request->status) }}
                </span>
            </p>
            <p class="text-sm text-gray-600">
                <strong>Request ID:</strong> {{ $request->id }}
            </p>
        </div>

        @if($request->type === 'export' && $request->status === 'completed')
            <a href="{{ route('gdpr.request.download', $request->verification_token) }}" 
               class="inline-block px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Download Your Data
            </a>
        @else
            <p class="text-sm text-gray-600">
                You will receive an email notification when your request is completed.
            </p>
        @endif

        <div class="mt-6">
            <a href="{{ route('frontend.home') }}" class="text-blue-600 hover:underline">
                Return to Home
            </a>
        </div>
    </div>
</div>
@endsection


