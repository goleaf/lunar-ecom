@extends('storefront.layout')

@section('title', $metaTags['title'] ?? $brand->name)

@section('meta')
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    @if($metaTags['og:image'])
        <meta property="og:image" content="{{ $metaTags['og:image'] }}">
    @endif
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <link rel="canonical" href="{{ $metaTags['canonical'] }}">

    @php
        $brandStructuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Brand',
            'name' => $brand->name,
        ];

        if ($logoUrl) {
            $brandStructuredData['logo'] = $logoUrl;
        }

        if ($websiteUrl) {
            $brandStructuredData['url'] = $websiteUrl;
        }

        if ($description) {
            $brandStructuredData['description'] = trim(strip_tags($description));
        }
    @endphp
    <script type="application/ld+json">
        {!! json_encode($brandStructuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('content')
<div class="px-4 py-6">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="{{ route('storefront.homepage') }}" class="hover:text-gray-700">Home</a>
        <span class="mx-2">/</span>
        <a href="{{ route('storefront.brands.index') }}" class="hover:text-gray-700">Brands</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">{{ $brand->name }}</span>
    </nav>

    @php
        $initials = collect(preg_split('/\s+/', trim($brand->name)) ?: [])
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $initials = $initials !== ''
            ? substr($initials, 0, 3)
            : strtoupper(substr($brand->name, 0, 2));
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 mb-8">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-10">
            <div class="flex-shrink-0">
                <div class="h-32 w-32 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}"
                             alt="{{ $brand->name }}"
                             class="h-24 w-24 object-contain">
                    @else
                        <span class="text-2xl font-semibold text-gray-400">{{ $initials }}</span>
                    @endif
                </div>
            </div>
            <div class="flex-1">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $brand->name }}</h1>
                        @if($description)
                            <p class="text-gray-600 mt-3 leading-relaxed max-w-3xl">{{ $description }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @if($websiteUrl)
                            <a href="{{ $websiteUrl }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                                Visit Website
                            </a>
                        @endif
                        <a href="{{ route('storefront.products.index', ['brand_id' => $brand->id]) }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:border-gray-400 hover:text-gray-900">
                            View All Products
                        </a>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-6 text-sm text-gray-500">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-900 font-semibold">{{ number_format($productCount) }}</span>
                        <span>{{ $productCount === 1 ? 'product' : 'products' }}</span>
                    </div>
                    @if($websiteUrl)
                        <div class="flex items-center gap-2">
                            <span class="text-gray-900 font-semibold">Website</span>
                            <span class="truncate max-w-[220px]">{{ parse_url($websiteUrl, PHP_URL_HOST) ?: $websiteUrl }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-900">Products</h2>
        <span class="text-sm text-gray-500">Showing {{ $products->count() }} of {{ number_format($productCount) }}</span>
    </div>

    @if($products->count() > 0)
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($products as $product)
                @include('storefront.products._product-card', ['product' => $product])
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <p class="text-gray-600">No products available for this brand.</p>
            <a href="{{ route('storefront.products.index') }}" class="text-blue-600 hover:text-blue-800 mt-4 inline-block">
                Browse All Products
            </a>
        </div>
    @endif

    <div class="mt-8">
        <a href="{{ route('storefront.brands.index') }}" class="text-blue-600 hover:text-blue-800">
            Back to Brand Directory
        </a>
    </div>
</div>
@endsection
