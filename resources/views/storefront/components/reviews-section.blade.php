@props(['product'])

@php
    $reviews = $product->approvedReviews()
        ->with(['customer', 'media'])
        ->mostHelpful()
        ->take(5)
        ->get();
    
    $aggregateRatings = [
        'average_rating' => $product->average_rating ?? 0,
        'total_reviews' => $product->total_reviews ?? 0,
        'rating_distribution' => $product->getRatingDistribution(),
    ];
@endphp

<div class="mt-12 border-t pt-8">
    <h2 class="text-2xl font-bold mb-6">Customer Reviews</h2>
    
    @if($aggregateRatings['total_reviews'] > 0)
        {{-- Rating Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="text-center">
                <div class="text-5xl font-bold text-gray-900 mb-2">
                    {{ number_format($aggregateRatings['average_rating'], 1) }}
                </div>
                <div class="flex justify-center mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-6 h-6 {{ $i <= round($aggregateRatings['average_rating']) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    @endfor
                </div>
                <p class="text-gray-600">Based on {{ $aggregateRatings['total_reviews'] }} {{ Str::plural('review', $aggregateRatings['total_reviews']) }}</p>
            </div>
            
            <div class="md:col-span-2">
                {{-- Rating Distribution --}}
                <div class="space-y-2">
                    @for($rating = 5; $rating >= 1; $rating--)
                        @php
                            $percentage = $aggregateRatings['rating_distribution'][$rating] ?? 0;
                            $count = match($rating) {
                                5 => $product->rating_5_count ?? 0,
                                4 => $product->rating_4_count ?? 0,
                                3 => $product->rating_3_count ?? 0,
                                2 => $product->rating_2_count ?? 0,
                                1 => $product->rating_1_count ?? 0,
                            };
                        @endphp
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-gray-700 w-8">{{ $rating }}</span>
                            <svg class="w-4 h-4 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-yellow-400 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm text-gray-600 w-12 text-right">{{ $count }}</span>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endif

    {{-- Reviews List --}}
    <div id="reviews-container" class="space-y-6">
        @if($reviews->count() > 0)
            @foreach($reviews as $review)
                @include('storefront.components.review-item', ['review' => $review])
            @endforeach
            
            @if($aggregateRatings['total_reviews'] > 5)
                <div class="text-center mt-6">
                    <a href="{{ route('storefront.reviews.index', $product) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        View all {{ $aggregateRatings['total_reviews'] }} reviews →
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-12 bg-gray-50 rounded-lg">
                <p class="text-gray-600 mb-4">No reviews yet. Be the first to review this product!</p>
                @auth
                    <a href="#write-review" class="text-blue-600 hover:text-blue-800 font-medium">Write a Review</a>
                @else
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">Login to Write a Review</a>
                @endauth
            </div>
        @endif
    </div>

    {{-- Write Review Section --}}
    @auth
        <div id="write-review" class="mt-12 border-t pt-8">
            @include('storefront.components.review-form', ['product' => $product])
        </div>
    @endauth
</div>

