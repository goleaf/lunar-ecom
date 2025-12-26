@props(['collection'])

@php
    $collectionImage = $collection->getFirstMedia('images') 
        ?? $collection->getFirstMedia('hero')
        ?? $collection->products->first()?->getFirstMedia('images');
    $productCount = $collection->products()->count();
    $url = $collection->urls->first()->slug ?? $collection->id;
@endphp

<div class="collection-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
    <a href="{{ route('frontend.collections.show', $url) }}" class="block">
        <div class="relative h-64 overflow-hidden">
            @if($collectionImage)
                @include('frontend.components.responsive-image', [
                    'media' => $collectionImage,
                    'model' => $collection,
                    'collectionName' => 'images',
                    'conversion' => 'collection_card',
                    'sizeType' => 'collection_card',
                    'alt' => $collection->translateAttribute('name'),
                    'class' => 'w-full h-full object-cover transition-transform duration-300 hover:scale-105',
                    'loading' => 'lazy',
                ])
            @else
                <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                    <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            @endif
            <div class="absolute top-4 right-4">
                @if($collection->collection_type === 'bestsellers')
                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold">Hot</span>
                @elseif($collection->collection_type === 'new_arrivals')
                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold">New</span>
                @elseif($collection->collection_type === 'featured')
                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-semibold">Featured</span>
                @endif
            </div>
        </div>
        <div class="p-6">
            <h3 class="text-xl font-semibold mb-2 text-gray-900 hover:text-blue-600 transition-colors">
                {{ $collection->translateAttribute('name') }}
            </h3>
            @if($collection->translateAttribute('description'))
                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                    {{ $collection->translateAttribute('description') }}
                </p>
            @endif
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    {{ $productCount }} {{ $productCount === 1 ? 'product' : 'products' }}
                </span>
                <span class="text-blue-600 font-semibold text-sm hover:text-blue-800">
                    Explore →
                </span>
            </div>
        </div>
    </a>
</div>



