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
    
    {{-- Brand Structured Data --}}
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Brand",
            "name": "{{ $brand->name }}",
            @if($logoUrl)
            "logo": "{{ $logoUrl }}",
            @endif
            @if($websiteUrl)
            "url": "{{ $websiteUrl }}",
            @endif
            @if($description)
            "description": "{{ strip_tags($description) }}"
            @endif
        }
    </script>
@endsection

@section('content')
<div class="px-4 py-6">
    {{-- Brand Header --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
            @if($logoUrl)
                <div class="flex-shrink-0">
                    <img src="{{ $logoUrl }}" 
                         alt="{{ $brand->name }}" 
                         class="h-32 w-32 object-contain bg-gray-50 rounded p-4">
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2">{{ $brand->name }}</h1>
                @if($description)
                    <p class="text-gray-600 mb-4">{{ $description }}</p>
                @endif
                @if($websiteUrl)
                    <a href="{{ $websiteUrl }}" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="text-blue-600 hover:text-blue-800 text-sm">
                        Visit Website →
                    </a>
                @endif
                @if($productCount > 0)
                    <p class="text-sm text-gray-500 mt-2">{{ $productCount }} {{ $productCount === 1 ? 'product' : 'products' }} available</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Products --}}
    @if($products->count() > 0)
        <h2 class="text-2xl font-bold mb-4">Products by {{ $brand->name }}</h2>
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
                Browse All Products →
            </a>
        </div>
    @endif

    {{-- Back to Brands --}}
    <div class="mt-6">
        <a href="{{ route('storefront.brands.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Back to Brand Directory
        </a>
    </div>
</div>
@endsection

