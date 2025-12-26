@props(['product', 'limit' => 5])

@php
    $service = app(\App\Services\RecommendationService::class);
    $recommendedProducts = $service->getFrequentlyBoughtTogether($product, $limit);
@endphp

@if($recommendedProducts->count() > 0)
    <div class="frequently-bought-together mt-8 p-6 bg-gray-50 rounded-lg" 
         data-source-product-id="{{ $product->id }}">
        <h3 class="text-xl font-semibold mb-4">{{ __('frontend.recommendations.frequently_bought_together') }}</h3>
        
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($recommendedProducts as $recommendedProduct)
                <div class="recommended-product flex items-center gap-4 p-4 bg-white rounded border hover:shadow-md transition-shadow"
                     data-product-id="{{ $recommendedProduct->id }}"
                     data-recommendation-type="frequently_bought_together">
                    <a href="{{ route('frontend.products.show', $recommendedProduct->id) }}" 
                       class="flex items-center gap-4 w-full"
                       onclick="trackRecommendationClick({{ $product->id }}, {{ $recommendedProduct->id }}, 'frequently_bought_together', 'product_page')">
                        @php
                            $thumbnail = $recommendedProduct->getFirstMedia('images');
                        @endphp
                        @if($thumbnail)
                            <img src="{{ $thumbnail->getUrl('thumb') }}" 
                                 alt="{{ $recommendedProduct->translateAttribute('name') }}"
                                 class="w-20 h-20 object-cover rounded">
                        @endif
                        <div class="flex-1">
                            <h4 class="font-medium text-sm">{{ $recommendedProduct->translateAttribute('name') }}</h4>
                            @php
                                $variant = $recommendedProduct->variants->first();
                                $price = null;
                                if ($variant) {
                                    $pricing = \Lunar\Facades\Pricing::for($variant)->get();
                                    if ($pricing->matched?->price) {
                                        $price = $pricing->matched->price;
                                    }
                                }
                            @endphp
                            @if($price)
                                <p class="text-lg font-semibold text-blue-600 mt-1">
                                    {{ \Lunar\Facades\Currency::getDefault()->formatter($price->value) }}
                                </p>
                            @endif
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    @endpush
@endif


