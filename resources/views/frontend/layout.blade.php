<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? trim($__env->yieldContent('title', config('app.name', 'E-commerce Store'))) }}</title>
    
    {{-- SEO Meta Tags --}}
    @isset($pageMeta)
        {!! $pageMeta !!}
    @else
        @hasSection('meta')
            @yield('meta')
        @else
            @php
                $defaultMeta = \App\Services\SEOService::getDefaultMetaTags(
                    config('app.name', 'E-commerce Store'),
                    __('frontend.seo.default_description', ['store' => config('app.name', 'our store')]),
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
    @endisset
    
    {{-- Organization and Website Structured Data --}}
    <script type="application/ld+json">
        {!! json_encode(\App\Services\SEOService::generateOrganizationStructuredData(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode(\App\Services\SEOService::generateWebsiteStructuredData(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
    @livewireStyles
</head>
<body class="bg-gray-50" data-app="frontend">
    <div id="app" class="min-h-screen bg-gray-50">
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <a href="{{ route('frontend.homepage') }}" class="flex items-center px-2 py-2 text-xl font-bold text-gray-900">
                            {{ config('app.name', 'Store') }}
                        </a>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('frontend.products.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('frontend.nav.products') }}
                            </a>
                            <a href="{{ route('frontend.bundles.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('frontend.nav.bundles') }}
                            </a>
                            <a href="{{ route('frontend.collections.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('frontend.nav.collections') }}
                            </a>
                            <a href="{{ route('categories.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('frontend.categories') }}
                            </a>
                            <a href="{{ route('frontend.brands.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900">
                                {{ __('frontend.nav.brands') }}
                            </a>
                            <a href="{{ route('frontend.comparison.index') }}" class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-900 relative">
                                {{ __('frontend.nav.comparison') }}
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
                            @include('frontend.components.search-autocomplete')
                        </div>
                        <livewire:frontend.language-selector />
                        <livewire:frontend.currency-selector />
                        @auth
                            <a href="{{ route('frontend.addresses.index') }}" class="text-gray-700 hover:text-gray-900">
                                {{ __('frontend.nav.addresses') }}
                            </a>
                            <a href="{{ route('frontend.downloads.index') }}" class="text-gray-700 hover:text-gray-900">
                                {{ __('frontend.nav.downloads') }}
                            </a>
                        @endauth
                        @include('frontend.components.cart-widget')
                    </div>
                </div>
            </div>
        </nav>

        {{-- Comparison Bar --}}
        @include('frontend.components.comparison-bar')

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

            @if (isset($slot))
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </main>

        <footer class="bg-white border-t mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-center text-gray-600">&copy; {{ date('Y') }} {{ config('app.name', 'Store') }}. {{ __('frontend.footer.all_rights_reserved') }}</p>
                    <div class="flex gap-4 text-sm">
                        <a href="{{ route('gdpr.privacy-policy.show') }}" class="text-gray-600 hover:text-gray-900">{{ __('frontend.footer.privacy_policy') }}</a>
                        @auth
                            <a href="{{ route('gdpr.privacy-settings.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('frontend.footer.privacy_settings') }}</a>
                        @endauth
                    </div>
                </div>
            </div>
        </footer>

        <livewire:frontend.cookie-consent-banner />

        @livewireScripts
        @stack('scripts')
    </div>
</body>
</html>

