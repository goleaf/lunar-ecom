<div class="px-4 py-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <div>
                @php
                    $images = $product->getMedia('images');
                    $firstMedia = $product->getFirstMedia('images');
                @endphp

                @if($images->count() > 0)
                    <div class="space-y-4 relative">
                        @if($firstMedia)
                            <div class="relative">
                                @include('frontend.components.responsive-image', [
                                    'media' => $firstMedia,
                                    'model' => $product,
                                    'collectionName' => 'images',
                                    'conversion' => 'large',
                                    'sizeType' => 'product_detail',
                                    'alt' => $product->translateAttribute('name'),
                                    'class' => 'w-full rounded main-product-image',
                                    'id' => 'main-product-image',
                                    'loading' => 'eager'
                                ])
                                <x-frontend.product-badges :product="$product" />
                            </div>
                        @endif

                        @if($images->count() > 1)
                            <div class="grid grid-cols-4 gap-2">
                                @foreach($images as $image)
                                    <img src="{{ $image->getUrl('thumb') }}"
                                         alt="{{ $product->translateAttribute('name') }} - Image {{ $loop->iteration }}"
                                         class="w-full h-24 object-cover rounded cursor-pointer hover:opacity-75 thumbnail-image"
                                         onclick="document.getElementById('main-product-image').src = '{{ $image->getUrl('large') ?? $image->getUrl() }}'"
                                         loading="lazy">
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="w-full h-96 bg-gray-200 flex items-center justify-center rounded">
                        <span class="text-gray-400">{{ __('frontend.product.no_image') }}</span>
                    </div>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <h1 class="text-3xl font-bold">{{ $product->translateAttribute('name') }}</h1>
                    @if($product->is_digital)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Digital Product
                        </span>
                    @endif
                </div>

                @if($product->is_digital && $product->digitalProduct)
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-indigo-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-indigo-900 mb-1">Instant Download</p>
                                <p class="text-sm text-indigo-700">
                                    This is a digital product. After purchase, you'll receive an email with download instructions.
                                    @if($product->digitalProduct->file_size)
                                        File size: {{ $product->digitalProduct->getFormattedFileSize() }}
                                    @endif
                                    @if($product->digitalProduct->download_limit)
                                        Download limit: {{ $product->digitalProduct->download_limit }} times
                                    @endif
                                    @if($product->digitalProduct->download_expiry_days)
                                        Valid for {{ $product->digitalProduct->download_expiry_days }} days
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($description)
                    <div class="prose mb-6">
                        <p class="text-gray-600">{{ $description }}</p>
                    </div>
                @endif

                @if($material)
                    <div class="mb-4">
                        <strong class="text-gray-700">Material:</strong>
                        <span class="text-gray-600">{{ $material }}</span>
                    </div>
                @endif

                @if($weight)
                    <div class="mb-4">
                        <strong class="text-gray-700">Weight:</strong>
                        <span class="text-gray-600">{{ $weight }} kg</span>
                    </div>
                @endif

                @if($product->variants->count() > 0)
                    @php
                        $defaultVariant = $product->variants->first();
                        $pricing = \Lunar\Facades\Pricing::for($defaultVariant)->get();
                        $price = $pricing->matched?->price;
                    @endphp
                    @if($price)
                        <div class="mb-6">
                            <p class="text-3xl font-bold text-gray-900">{{ $price->formatted }}</p>
                            @if($pricing->matched?->compare_price)
                                <p class="text-lg text-gray-500 line-through">{{ $pricing->matched->compare_price->formatted }}</p>
                            @endif
                        </div>
                    @endif

                    <form wire:submit.prevent="addToCart" class="mb-6">
                        <input type="hidden" wire:model="variantId">
                        <div class="mb-4">
                            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">{{ __('frontend.product.quantity') }}</label>
                            <input type="number" wire:model.defer="quantity" id="quantity" min="1" max="999" class="border rounded px-3 py-2 w-24">
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                            {{ __('frontend.product.add_to_cart') }}
                        </button>
                    </form>

                    @if($message)
                        <div class="mb-4 p-3 rounded {{ $messageType === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' }}">
                            {{ $message }}
                        </div>
                    @endif

                    @php
                        $variant = $product->variants->first();
                        $isOutOfStock = !$variant || $variant->stock <= 0;
                    @endphp
                    @if($isOutOfStock)
                        <div class="mt-4">
                            <x-frontend.notify-me-button :product="$product" :variant="$variant" />
                        </div>
                    @endif

                    <div class="mt-4">
                        <x-frontend.compare-button :product="$product" />
                    </div>
                @endif

                @if($product->tags->count() > 0)
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-700 mb-2">Tags:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->tags as $tag)
                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">{{ $tag->value }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($crossSell->count() > 0 || $upSell->count() > 0 || $alternate->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">Related Products</h2>

            @if($crossSell->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">You May Also Like</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($crossSell as $relatedProduct)
                            @include('frontend.products._product-card', ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($upSell->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Upgrade Options</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($upSell as $relatedProduct)
                            @include('frontend.products._product-card', ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($alternate->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Alternatives</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($alternate as $relatedProduct)
                            @include('frontend.products._product-card', ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Reviews Section --}}
    @include('frontend.components.reviews-section', ['product' => $product])

    {{-- Product Recommendations --}}
    <div class="mt-12">
        <x-frontend.product-recommendations
            :product="$product"
            type="related"
            :limit="8"
            location="product_page" />

        <x-frontend.frequently-bought-together
            :product="$product"
            :limit="5" />

        <x-frontend.customers-also-viewed
            :product="$product"
            :limit="8" />
    </div>
</div>

