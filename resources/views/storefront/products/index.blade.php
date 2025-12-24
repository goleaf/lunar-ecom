@extends('storefront.layout')

@section('title', 'Products')

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Products</h1>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                @php
                    // Get first image URL using Lunar Media - see: https://docs.lunarphp.com/1.x/reference/media
                    $imageUrl = $product->getFirstMediaUrl('images', 'thumb') 
                        ?? $product->getFirstMediaUrl('images') 
                        ?? config('lunar.media.fallback.url');
                @endphp

                @if($imageUrl)
                    <img src="{{ $imageUrl }}" 
                         alt="{{ $product->translateAttribute('name') }}" 
                         class="w-full h-48 object-cover">
                @else
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-400">No Image</span>
                    </div>
                @endif
                <div class="p-4">
                    <h3 class="text-lg font-semibold mb-2">
                        <a href="{{ route('storefront.products.show', $product->urls->first()->slug ?? $product->id) }}" class="text-gray-900 hover:text-gray-600">
                            {{ $product->translateAttribute('name') }}
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                        {{ $product->translateAttribute('description') }}
                    </p>
                    @if($product->variants->first())
                        <p class="text-xl font-bold text-gray-900">
                            {{ $product->variants->first()->price->formatted }}
                        </p>
                    @endif
                    <a href="{{ route('storefront.products.show', $product->urls->first()->slug ?? $product->id) }}" class="mt-3 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        View Details
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-600">No products found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
@endsection

