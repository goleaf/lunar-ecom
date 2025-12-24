<div class="bg-white rounded-lg shadow overflow-hidden">
    @if($product->thumbnail)
        <img src="{{ $product->thumbnail->getUrl() }}" alt="{{ $product->translateAttribute('name') }}" class="w-full h-48 object-cover">
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
        @if($product->variants->first())
            <p class="text-xl font-bold text-gray-900 mb-3">
                {{ $product->variants->first()->price->formatted }}
            </p>
        @endif
        <a href="{{ route('storefront.products.show', $product->urls->first()->slug ?? $product->id) }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
            View Details
        </a>
    </div>
</div>

