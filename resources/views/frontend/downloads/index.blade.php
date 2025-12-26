@extends('frontend.layout')

@section('title', __('frontend.downloads.title'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">{{ __('frontend.downloads.title') }}</h1>

        @if($downloads->isEmpty())
            <div class="bg-gray-100 rounded-lg p-8 text-center">
                <p class="text-gray-600 mb-4">{{ __('frontend.downloads.empty') }}</p>
                <a href="{{ route('frontend.products.index') }}" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                    {{ __('frontend.downloads.browse_products') }}
                </a>
            </div>
        @else
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('frontend.downloads.product') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('frontend.downloads.order') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('frontend.downloads.file_size') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('frontend.downloads.downloads') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('frontend.downloads.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('frontend.downloads.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($downloads as $download)
                            @php
                                $digitalProduct = $download->digitalProduct;
                                $product = $digitalProduct?->product;
                                $isAvailable = $download->isAvailable();
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($product)
                                            <div class="flex-shrink-0 h-10 w-10">
                                                @if($product->thumbnail)
                                                    <img class="h-10 w-10 rounded-full object-cover" src="{{ $product->thumbnail->getUrl() }}" alt="{{ $product->translateAttribute('name') }}">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $product->translateAttribute('name') }}</div>
                                                @if($digitalProduct->version)
                                                    <div class="text-sm text-gray-500">v{{ $digitalProduct->version }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $download->order->reference }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $digitalProduct->getFormattedFileSize() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $download->downloads_count }}
                                    @if($digitalProduct->download_limit)
                                        / {{ $digitalProduct->download_limit }}
                                    @else
                                        / ∞
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($isAvailable)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Available
                                        </span>
                                    @elseif($download->isExpired())
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Expired
                                        </span>
                                    @elseif($download->isLimitReached())
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Limit Reached
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Unavailable
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @if($isAvailable)
                                        <a href="{{ $download->getDownloadUrl() }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                            Download
                                        </a>
                                    @endif
                                    <button onclick="resendEmail({{ $download->id }})" class="text-gray-600 hover:text-gray-900">
                                        Resend Email
                                    </button>
                                    @if($download->license_key)
                                        <button onclick="showLicenseKey('{{ $download->license_key }}')" class="text-indigo-600 hover:text-indigo-900 ml-4">
                                            View License
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $downloads->links() }}
            </div>
        @endif
    </div>
</div>

<!-- License Key Modal -->
<div id="licenseModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">License Key</h3>
            <div class="bg-gray-100 p-4 rounded-md mb-4">
                <code id="licenseKeyText" class="text-lg font-mono"></code>
            </div>
            <button onclick="copyLicenseKey()" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Copy License Key
            </button>
            <button onclick="closeLicenseModal()" class="mt-2 w-full bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function resendEmail(downloadId) {
    fetch(`/downloads/${downloadId}/resend-email`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Download instructions email has been sent.');
        } else {
            alert('Failed to send email. Please try again.');
        }
    });
}

function showLicenseKey(licenseKey) {
    document.getElementById('licenseKeyText').textContent = licenseKey;
    document.getElementById('licenseModal').classList.remove('hidden');
}

function closeLicenseModal() {
    document.getElementById('licenseModal').classList.add('hidden');
}

function copyLicenseKey() {
    const licenseKey = document.getElementById('licenseKeyText').textContent;
    navigator.clipboard.writeText(licenseKey).then(() => {
        alert('License key copied to clipboard!');
    });
}
</script>
@endsection

