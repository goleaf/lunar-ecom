@extends('storefront.layout')

@section('title', $metaTags['title'] ?? $product->translateAttribute('name'))

@section('meta')
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta name="keywords" content="{{ $metaTags['keywords'] }}">
    <meta name="robots" content="{{ $robotsMeta }}">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    @if($metaTags['og:image'])
        <meta property="og:image" content="{{ $metaTags['og:image'] }}">
    @endif
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    @if(isset($metaTags['og:site_name']))
        <meta property="og:site_name" content="{{ $metaTags['og:site_name'] }}">
    @endif
    
    {{-- Twitter Card --}}
    @if(isset($metaTags['twitter:card']))
        <meta name="twitter:card" content="{{ $metaTags['twitter:card'] }}">
        <meta name="twitter:title" content="{{ $metaTags['twitter:title'] }}">
        <meta name="twitter:description" content="{{ $metaTags['twitter:description'] }}">
        @if(isset($metaTags['twitter:image']))
            <meta name="twitter:image" content="{{ $metaTags['twitter:image'] }}">
        @endif
    @endif
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ $metaTags['canonical'] }}">
    
    {{-- Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('content')
<div class="px-4 py-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <div>
                @php
                    // Get images using Lunar Media - see: https://docs.lunarphp.com/1.x/reference/media
                    $images = $product->getMedia('images');
                    $firstMedia = $product->getFirstMedia('images');
                @endphp

                @if($images->count() > 0)
                    <div class="space-y-4">
                        @if($firstMedia)
                            @include('storefront.components.responsive-image', [
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
                        <span class="text-gray-400">{{ __('storefront.product.no_image') }}</span>
                    </div>
                @endif
            </div>
            <div>
                <h1 class="text-3xl font-bold mb-4">{{ $product->translateAttribute('name') }}</h1>
                
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
                        // Get pricing using Lunar Pricing facade
                        // See: https://docs.lunarphp.com/1.x/reference/products#fetching-the-price
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

                    <form action="{{ route('storefront.cart.add') }}" method="POST" class="mb-6 add-to-cart-form" id="add-to-cart-form">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $defaultVariant->id }}">
                        <div class="mb-4">
                            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">{{ __('storefront.product.quantity') }}</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="999" class="border rounded px-3 py-2 w-24">
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 add-to-cart-btn">
                            <span class="btn-text">{{ __('storefront.product.add_to_cart') }}</span>
                            <span class="btn-loading hidden">Adding...</span>
                        </button>
                    </form>
                    <div id="add-to-cart-message" class="hidden mb-4 p-3 rounded"></div>
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
                        @foreach($crossSell as $related)
                            @include('storefront.products._product-card', ['product' => $related])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($upSell->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Upgrade Options</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($upSell as $related)
                            @include('storefront.products._product-card', ['product' => $related])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($alternate->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Alternatives</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($alternate as $related)
                            @include('storefront.products._product-card', ['product' => $related])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Reviews Section --}}
    @include('storefront.components.reviews-section', ['product' => $product])

    {{-- Product Recommendations --}}
    <div class="mt-12">
        {{-- Related Products --}}
        <x-storefront.product-recommendations 
            :product="$product" 
            type="related" 
            :limit="8" 
            location="product_page" />
        
        {{-- Frequently Bought Together --}}
        <x-storefront.frequently-bought-together 
            :product="$product" 
            :limit="5" />
        
        {{-- Customers Also Viewed --}}
        <x-storefront.customers-also-viewed 
            :product="$product" 
            :limit="8" />
    </div>
</div>

@push('scripts')
<script>
    // Track product view on page load
    document.addEventListener('DOMContentLoaded', function() {
        fetch('{{ route("storefront.recommendations.track-view", $product) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).catch(err => console.error('Failed to track product view:', err));

        // Handle add to cart via AJAX
        const addToCartForm = document.getElementById('add-to-cart-form');
        const addToCartBtn = document.querySelector('.add-to-cart-btn');
        const btnText = document.querySelector('.btn-text');
        const btnLoading = document.querySelector('.btn-loading');
        const messageDiv = document.getElementById('add-to-cart-message');

        if (addToCartForm) {
            addToCartForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');
                addToCartBtn.disabled = true;
                messageDiv.classList.add('hidden');

                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    btnText.classList.remove('hidden');
                    btnLoading.classList.add('hidden');
                    addToCartBtn.disabled = false;

                    if (data.success) {
                        // Show success message
                        messageDiv.className = 'mb-4 p-3 rounded bg-green-100 border border-green-400 text-green-700';
                        messageDiv.textContent = data.message || 'Item added to cart!';
                        messageDiv.classList.remove('hidden');

                        // Trigger cart update event
                        document.dispatchEvent(new Event('cartUpdated'));

                        // Optionally redirect to cart after a delay
                        // setTimeout(() => {
                        //     window.location.href = '{{ route("storefront.cart.index") }}';
                        // }, 1500);
                    } else {
                        // Show error message
                        messageDiv.className = 'mb-4 p-3 rounded bg-red-100 border border-red-400 text-red-700';
                        messageDiv.textContent = data.message || 'Error adding item to cart';
                        messageDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    btnText.classList.remove('hidden');
                    btnLoading.classList.add('hidden');
                    addToCartBtn.disabled = false;
                    
                    messageDiv.className = 'mb-4 p-3 rounded bg-red-100 border border-red-400 text-red-700';
                    messageDiv.textContent = 'An error occurred. Please try again.';
                    messageDiv.classList.remove('hidden');
                });
            });
        }
    });
</script>
@endpush
@endsection

