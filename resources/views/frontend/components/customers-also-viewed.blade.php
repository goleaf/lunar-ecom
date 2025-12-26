@props(['product', 'limit' => 8])

@php
    $service = app(\App\Services\RecommendationService::class);
    $userId = auth()->id();
    $sessionId = session()->getId();
    
    // Get products from browsing history
    $viewedProducts = $service->getPersonalizedRecommendations($userId, $sessionId, $limit * 2);
    
    // Filter out current product
    $recommendedProducts = $viewedProducts->filter(function($p) use ($product) {
        return $p->id !== $product->id;
    })->take($limit);
@endphp

@if($recommendedProducts->count() > 0)
    <div class="customers-also-viewed mt-12" 
         data-source-product-id="{{ $product->id }}">
        <h2 class="text-2xl font-bold mb-6">{{ __('frontend.recommendations.customers_also_viewed') }}</h2>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($recommendedProducts as $recommendedProduct)
                <div class="recommended-product" 
                     data-product-id="{{ $recommendedProduct->id }}"
                     data-recommendation-type="customers_also_viewed">
                    @include('frontend.products._product-card', ['product' => $recommendedProduct])
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.customers-also-viewed');
            if (container) {
                const sourceProductId = container.dataset.sourceProductId;
                
                container.addEventListener('click', function(e) {
                    const productCard = e.target.closest('.recommended-product');
                    if (productCard) {
                        const recommendedProductId = productCard.dataset.productId;
                        
                        fetch('{{ route("frontend.recommendations.track-click") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                source_product_id: sourceProductId,
                                recommended_product_id: recommendedProductId,
                                recommendation_type: 'customers_also_viewed',
                                display_location: 'product_page',
                                recommendation_algorithm: 'personalized'
                            })
                        }).catch(err => console.error('Failed to track recommendation click:', err));
                    }
                });
            }
        });
    </script>
    @endpush
@endif


