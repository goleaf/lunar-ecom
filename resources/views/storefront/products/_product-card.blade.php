<div class="bg-white rounded-lg shadow overflow-hidden">
    @php
        $firstMedia = $product->getFirstMedia('images');
    @endphp

    @if($firstMedia)
        @include('storefront.components.responsive-image', [
            'media' => $firstMedia,
            'model' => $product,
            'collectionName' => 'images',
            'conversion' => 'thumb',
            'sizeType' => 'product_card',
            'alt' => $product->translateAttribute('name'),
            'class' => 'w-full h-48 object-cover',
            'loading' => 'lazy'
        ])
    @else
        <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
            <span class="text-gray-400">{{ __('storefront.product.no_image') }}</span>
        </div>
    @endif
    <div class="p-4">
        <h3 class="text-lg font-semibold mb-2">
            <a href="{{ route('storefront.products.show', $product->urls->first()->slug ?? $product->id) }}" class="text-gray-900 hover:text-gray-600">
                {{ $product->translateAttribute('name') }}
            </a>
        </h3>
        @php
            // Get pricing using Lunar Pricing facade
            // See: https://docs.lunarphp.com/1.x/reference/products#fetching-the-price
            $variant = $product->variants->first();
            if ($variant) {
                $pricing = \Lunar\Facades\Pricing::for($variant)->get();
                $price = $pricing->matched?->price;
            }
        @endphp
        @if(isset($price) && $price)
            <p class="text-xl font-bold text-gray-900 mb-3">
                {{ $price->formatted }}
            </p>
        @endif
        <div class="flex gap-2">
            <a href="{{ route('storefront.products.show', $product->urls->first()->slug ?? $product->id) }}" class="flex-1 text-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                {{ __('storefront.product.view_details') }}
            </a>
            <x-storefront.compare-button :product="$product" />
        </div>
    </div>
</div>

