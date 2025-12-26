@props(['product', 'type' => 'related', 'title' => null, 'limit' => 10, 'location' => 'product_page'])

@php
    $service = app(\App\Services\RecommendationService::class);
    
    $userId = auth()->id();
    $sessionId = session()->getId();
    
    $recommendedProducts = match($type) {
        'related' => $service->getRelatedProducts($product, $limit),
        'frequently_bought_together' => $service->getFrequentlyBoughtTogether($product, $limit),
        'customers_also_viewed' => $service->getPersonalizedRecommendations($userId, $sessionId, $limit),
        'personalized' => $service->getPersonalizedRecommendations($userId, $sessionId, $limit),
        default => $service->getRecommendations($product, 'hybrid', $location, $userId, $sessionId, $limit),
    };
    
    $displayTitle = $title ?? match($type) {
        'related' => __('frontend.recommendations.related_products'),
        'frequently_bought_together' => __('frontend.recommendations.frequently_bought_together'),
        'customers_also_viewed' => __('frontend.recommendations.customers_also_viewed'),
        'personalized' => __('frontend.recommendations.personalized_for_you'),
        default => __('frontend.recommendations.you_may_also_like'),
    };
@endphp

@if($recommendedProducts->count() > 0)
    <div class="product-recommendations mt-12" 
         data-type="{{ $type }}"
         data-location="{{ $location }}"
         data-source-product-id="{{ $product->id }}">
        <h2 class="text-2xl font-bold mb-6">{{ $displayTitle }}</h2>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($recommendedProducts as $recommendedProduct)
                <div class="recommended-product" 
                     data-product-id="{{ $recommendedProduct->id }}"
                     data-recommendation-type="{{ $type }}">
                    @include('frontend.products._product-card', ['product' => $recommendedProduct])
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Track recommendation clicks
            const recommendationsContainer = document.querySelector('.product-recommendations[data-type="{{ $type }}"]');
            if (recommendationsContainer) {
                const sourceProductId = recommendationsContainer.dataset.sourceProductId;
                const recommendationType = recommendationsContainer.dataset.type;
                const location = recommendationsContainer.dataset.location;
                
                recommendationsContainer.addEventListener('click', function(e) {
                    const productCard = e.target.closest('.recommended-product');
                    if (productCard) {
                        const recommendedProductId = productCard.dataset.productId;
                        
                        // Track click via API
                        fetch('{{ route("frontend.recommendations.track-click") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                source_product_id: sourceProductId,
                                recommended_product_id: recommendedProductId,
                                recommendation_type: recommendationType,
                                display_location: location,
                                recommendation_algorithm: 'hybrid'
                            })
                        }).catch(err => console.error('Failed to track recommendation click:', err));
                    }
                });
            }
        });
    </script>
    @endpush
@endif


