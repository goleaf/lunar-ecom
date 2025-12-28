<div class="bg-white rounded-lg shadow overflow-hidden relative">
    @php
        $firstMedia = $product->getFirstMedia('images');
    @endphp

    @if($firstMedia)
        <div class="relative">
            @include('frontend.components.responsive-image', [
                'media' => $firstMedia,
                'model' => $product,
                'collectionName' => 'images',
                'conversion' => 'thumb',
                'sizeType' => 'product_card',
                'alt' => $product->translateAttribute('name'),
                'class' => 'w-full h-48 object-cover',
                'loading' => 'lazy'
            ])
            <x-frontend.product-badges :product="$product" :limit="3" />
        </div>
    @else
        <div class="relative w-full h-48 bg-gray-200 flex items-center justify-center">
            <span class="text-gray-400">{{ __('frontend.product.no_image') }}</span>
            <x-frontend.product-badges :product="$product" :limit="3" />
        </div>
    @endif
    <div class="p-4">
        <div class="flex items-start justify-between mb-2">
            <h3 class="text-lg font-semibold flex-1">
                <a href="{{ route('frontend.products.show', $product->urls->first()?->slug ?? $product->id) }}" class="text-gray-900 hover:text-gray-600">
                    {{ $product->translateAttribute('name') }}
                </a>
            </h3>
            @if($product->is_digital)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 ml-2" title="Digital Product">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Digital
                </span>
            @endif
        </div>
        @php
            // Get pricing using the Pricing facade
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
        <div>
            <a href="{{ route('frontend.products.show', $product->urls->first()?->slug ?? $product->id) }}" class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                {{ __('frontend.product.view_details') }}
            </a>
        </div>
    </div>
</div>


