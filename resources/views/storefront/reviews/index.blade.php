@extends('storefront.layout')

@section('title', 'Reviews for ' . $product->translateAttribute('name'))

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('storefront.products.show', $product->urls->first()?->slug ?? $product->id) }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            ← Back to Product
        </a>
        <h1 class="text-3xl font-bold">Reviews for {{ $product->translateAttribute('name') }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Filters Sidebar --}}
        <aside class="lg:col-span-1">
            <div class="bg-gray-50 p-4 rounded-lg sticky top-4">
                <h3 class="font-semibold mb-3">Filter Reviews</h3>
                <form method="GET" action="{{ route('storefront.reviews.index', $product) }}" class="space-y-4">
                    {{-- Sort Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="filter" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                            <option value="most_helpful" {{ request('filter') == 'most_helpful' ? 'selected' : '' }}>Most Helpful</option>
                            <option value="most_recent" {{ request('filter') == 'most_recent' ? 'selected' : '' }}>Most Recent</option>
                            <option value="highest_rating" {{ request('filter') == 'highest_rating' ? 'selected' : '' }}>Highest Rating</option>
                            <option value="lowest_rating" {{ request('filter') == 'lowest_rating' ? 'selected' : '' }}>Lowest Rating</option>
                            <option value="verified_only" {{ request('filter') == 'verified_only' ? 'selected' : '' }}>Verified Purchase Only</option>
                        </select>
                    </div>

                    {{-- Rating Filter --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                        <select name="rating" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                            <option value="">All Ratings</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                                    {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                        Apply Filters
                    </button>
                </form>
            </div>
        </aside>

        {{-- Reviews List --}}
        <div class="lg:col-span-3">
            <div id="reviews-container" class="space-y-6">
                @if(isset($reviews) && $reviews->count() > 0)
                    @foreach($reviews as $review)
                        @include('storefront.components.review-item', ['review' => $review])
                    @endforeach

                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $reviews->links() }}
                    </div>
                @else
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <p class="text-gray-600">No reviews found matching your filters.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

