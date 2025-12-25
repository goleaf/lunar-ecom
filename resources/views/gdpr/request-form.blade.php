@extends('storefront.layout')

@section('title', 'GDPR Request')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">
            @if($type === 'export')
                Request Data Export
            @elseif($type === 'deletion')
                Request Data Deletion
            @elseif($type === 'anonymization')
                Request Data Anonymization
            @else
                GDPR Request
            @endif
        </h1>

        @if($type === 'export')
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-blue-900 text-sm">
                    <strong>Right to Access:</strong> You can request a copy of all personal data we hold about you. 
                    This will be provided in a machine-readable JSON format.
                </p>
            </div>
        @elseif($type === 'deletion')
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-900 text-sm">
                    <strong>Warning:</strong> This action cannot be undone. All your personal data will be permanently deleted. 
                    Note that we may need to retain certain information for legal compliance (e.g., order records).
                </p>
            </div>
        @elseif($type === 'anonymization')
            <div class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <p class="text-orange-900 text-sm">
                    <strong>Data Anonymization:</strong> Your personal data will be anonymized while preserving order history 
                    for business and legal purposes. This is a reversible process.
                </p>
            </div>
        @endif

        <form action="{{ route('gdpr.request.store') }}" method="POST" id="gdpr-request-form">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email Address
                </label>
                <input type="email" 
                       name="email" 
                       id="email"
                       value="{{ auth()->user()?->email }}"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-sm text-gray-500">
                    We'll send a verification link to this email address to confirm your request.
                </p>
            </div>

            @if($type === 'deletion')
            <div class="mb-4">
                <label class="flex items-start">
                    <input type="checkbox" 
                           name="confirm_deletion" 
                           required
                           class="mt-1 mr-2">
                    <span class="text-sm text-gray-700">
                        I understand that this action is permanent and cannot be undone. I confirm that I want to delete all my personal data.
                    </span>
                </label>
            </div>
            @endif

            <div class="flex justify-end gap-4">
                <a href="{{ route('storefront.home') }}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white 
                               @if($type === 'deletion') bg-red-600 hover:bg-red-700
                               @elseif($type === 'anonymization') bg-orange-600 hover:bg-orange-700
                               @else bg-blue-600 hover:bg-blue-700
                               @endif rounded-md">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

