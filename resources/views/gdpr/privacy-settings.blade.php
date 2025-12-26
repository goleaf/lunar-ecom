@extends('frontend.layout')

@section('title', 'Privacy Settings')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Privacy Settings</h1>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Cookie Preferences</h2>
        
        <form action="{{ route('gdpr.privacy-settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- Necessary Cookies -->
                <div class="flex items-start justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900 mb-1">Necessary Cookies</h3>
                        <p class="text-sm text-gray-600">Required for the website to function properly. These cannot be disabled.</p>
                    </div>
                    <span class="ml-4 text-sm text-gray-500">Always Active</span>
                </div>

                <!-- Analytics Cookies -->
                <div class="flex items-start justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900 mb-1">Analytics Cookies</h3>
                        <p class="text-sm text-gray-600">Help us understand how visitors interact with our website by collecting and reporting information anonymously.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="analytics" value="1" 
                               {{ $consent && $consent->analytics ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Marketing Cookies -->
                <div class="flex items-start justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900 mb-1">Marketing Cookies</h3>
                        <p class="text-sm text-gray-600">Used to deliver personalized advertisements and track campaign effectiveness.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="marketing" value="1" 
                               {{ $consent && $consent->marketing ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Preference Cookies -->
                <div class="flex items-start justify-between p-4 border border-gray-200 rounded-lg">
                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900 mb-1">Preference Cookies</h3>
                        <p class="text-sm text-gray-600">Remember your preferences and settings for a better experience.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="preferences" value="1" 
                               {{ $consent && $consent->preferences ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>

    <!-- GDPR Rights Section -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Your GDPR Rights</h2>
        
        <div class="space-y-4">
            <div class="p-4 border border-gray-200 rounded-lg">
                <h3 class="font-medium text-gray-900 mb-2">Right to Access</h3>
                <p class="text-sm text-gray-600 mb-3">Request a copy of all personal data we hold about you.</p>
                <a href="{{ route('gdpr.request.create', ['type' => 'export']) }}" 
                   class="inline-block px-4 py-2 text-sm font-medium text-blue-600 border border-blue-600 rounded-md hover:bg-blue-50">
                    Request Data Export
                </a>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg">
                <h3 class="font-medium text-gray-900 mb-2">Right to Erasure</h3>
                <p class="text-sm text-gray-600 mb-3">Request deletion of your personal data from our systems.</p>
                <a href="{{ route('gdpr.request.create', ['type' => 'deletion']) }}" 
                   class="inline-block px-4 py-2 text-sm font-medium text-red-600 border border-red-600 rounded-md hover:bg-red-50">
                    Request Data Deletion
                </a>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg">
                <h3 class="font-medium text-gray-900 mb-2">Right to Anonymization</h3>
                <p class="text-sm text-gray-600 mb-3">Request anonymization of your personal data while preserving order history.</p>
                <a href="{{ route('gdpr.request.create', ['type' => 'anonymization']) }}" 
                   class="inline-block px-4 py-2 text-sm font-medium text-orange-600 border border-orange-600 rounded-md hover:bg-orange-50">
                    Request Anonymization
                </a>
            </div>
        </div>
    </div>

    <!-- Consent History -->
    @if($consentHistory->count() > 0)
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Consent History</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purpose</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($consentHistory as $tracking)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ ucfirst($tracking->consent_type) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $tracking->purpose }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($tracking->consented)
                                <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">Consented</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">Withdrawn</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $tracking->consented_at?->format('Y-m-d H:i') ?? $tracking->withdrawn_at?->format('Y-m-d H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection


