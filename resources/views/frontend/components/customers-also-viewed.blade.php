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
    @endpush
@endif


