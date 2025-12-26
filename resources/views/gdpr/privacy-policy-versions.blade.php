@extends('frontend.layout')

@section('title', 'Privacy Policy Versions')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Privacy policy versions</h1>

        <div class="space-y-4">
            @forelse($policies as $policy)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $policy->title }}</h2>
                            <div class="text-sm text-gray-600">Version {{ $policy->version }}</div>
                            @if($policy->effective_date)
                                <div class="text-xs text-gray-500">Effective {{ $policy->effective_date->format('F j, Y') }}</div>
                            @endif
                        </div>
                        <a href="{{ route('gdpr.privacy-policy.version', $policy->version) }}" class="text-blue-600 hover:underline text-sm">View policy</a>
                    </div>
                    @if($policy->summary)
                        <p class="mt-3 text-sm text-gray-700">{{ $policy->summary }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-600">No privacy policy versions available.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

