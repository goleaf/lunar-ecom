@extends('frontend.layout')

@section('title', 'Privacy Policy')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $policy->title }}</h1>
            <div class="flex items-center gap-4 text-sm text-gray-600">
                <span>Version: {{ $policy->version }}</span>
                @if($policy->effective_date)
                    <span>Effective: {{ $policy->effective_date->format('F j, Y') }}</span>
                @endif
            </div>
        </div>

        @if($policy->summary)
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-blue-900">{{ $policy->summary }}</p>
        </div>
        @endif

        <div class="prose max-w-none">
            {!! nl2br(e($policy->content)) !!}
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('gdpr.privacy-policy.index') }}" 
               class="text-blue-600 hover:underline">
                View all privacy policy versions
            </a>
        </div>
    </div>
</div>
@endsection


