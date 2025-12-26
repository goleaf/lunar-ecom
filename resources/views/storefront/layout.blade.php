<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lunar E-commerce Store')</title>
    
    {{-- SEO Meta Tags --}}
    @hasSection('meta')
        @yield('meta')
    @else
        @php
            $defaultMeta = \App\Services\SEOService::getDefaultMetaTags(
                'Lunar E-commerce Store',
                'Shop the best products at Lunar Store',
                null,
                request()->url()
            );
        @endphp
        <meta name="description" content="{{ $defaultMeta['description'] }}">
        <meta property="og:title" content="{{ $defaultMeta['og:title'] }}">
        <meta property="og:description" content="{{ $defaultMeta['og:description'] }}">
        <meta property="og:type" content="{{ $defaultMeta['og:type'] }}">
        <meta property="og:url" content="{{ $defaultMeta['og:url'] }}">
        <link rel="canonical" href="{{ $defaultMeta['canonical'] }}">
    @endif
    
    {{-- Organization and Website Structured Data --}}
    <script type="application/ld+json">
        {!! json_encode(\App\Services\SEOService::generateOrganizationStructuredData(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode(\App\Services\SEOService::generateWebsiteStructuredData(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @livewireStyles
</head>
<body class="bg-gray-50">
    <div id="app" class="min-h-screen bg-gray-50">
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <a href="{{ route('storefront.homepage') }}" class="flex items-center px-2 py-2 text-xl font-bold text-gray-900">
                            Lunar Store
                        </a>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('storefront.products.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('storefront.nav.products') }}
                            </a>
                            <a href="{{ route('storefront.bundles.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('storefront.nav.bundles') }}
                            </a>
                            <a href="{{ route('storefront.collections.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('storefront.nav.collections') }}
                            </a>
                            <a href="{{ route('categories.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('storefront.categories') }}
                            </a>
                            <a href="{{ route('storefront.brands.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                Brands
                            </a>
                            <a href="{{ route('storefront.bundles.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('storefront.nav.bundles') }}
                            </a>
                            <a href="{{ route('storefront.comparison.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900 relative">
                                {{ __('storefront.nav.comparison') }}
                                @php
                                    $comparisonService = app(\App\Services\ComparisonService::class);
                                    $comparisonCount = $comparisonService->getComparisonCount();
                                @endphp
                                @if($comparisonCount > 0)
                                    <span id="comparison-count" class="ml-1 bg-blue-600 text-white text-xs rounded-full px-2 py-0.5">
                                        {{ $comparisonCount }}
                                    </span>
                                @else
                                    <span id="comparison-count" class="hidden ml-1 bg-blue-600 text-white text-xs rounded-full px-2 py-0.5"></span>
                                @endif
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="mr-4 w-64">
                            @include('storefront.components.search-autocomplete')
                        </div>
                        @include('storefront.components.language-selector')
                        @include('storefront.components.currency-selector')
                        @auth
                            <a href="{{ route('storefront.addresses.index') }}" class="text-gray-700 hover:text-gray-900">
                                Addresses
                            </a>
                            <a href="{{ route('storefront.downloads.index') }}" class="text-gray-700 hover:text-gray-900">
                                {{ __('storefront.nav.downloads') }}
                            </a>
                        @endauth
                        @include('storefront.components.cart-widget')
                    </div>
                </div>
            </div>
        </nav>

        {{-- Comparison Bar --}}
        @include('storefront.components.comparison-bar')

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="bg-white border-t mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-center text-gray-600">&copy; {{ date('Y') }} Lunar Store. All rights reserved.</p>
                    <div class="flex gap-4 text-sm">
                        <a href="{{ route('gdpr.privacy-policy.show') }}" class="text-gray-600 hover:text-gray-900">Privacy Policy</a>
                        @auth
                            <a href="{{ route('gdpr.privacy-settings.index') }}" class="text-gray-600 hover:text-gray-900">Privacy Settings</a>
                        @endauth
                    </div>
                </div>
            </div>
        </footer>

        @include('gdpr.cookie-consent-banner')

        @livewireScripts
        @stack('scripts')
    </div>
</body>
</html>
