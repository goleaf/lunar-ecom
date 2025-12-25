@props(['review'])

<div class="border-b pb-6 last:border-b-0">
    <div class="flex items-start justify-between mb-2">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <div class="flex">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    @endfor
                </div>
                <h3 class="font-semibold text-lg">{{ $review->title }}</h3>
            </div>
            
            <div class="flex items-center gap-4 text-sm text-gray-600 mb-3">
                <span class="font-medium">
                    {{ $review->customer ? $review->customer->first_name . ' ' . $review->customer->last_name : 'Anonymous' }}
                </span>
                @if($review->is_verified_purchase)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Verified Purchase
                    </span>
                @endif
                <span>{{ $review->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    <p class="text-gray-700 mb-4 whitespace-pre-wrap">{{ $review->content }}</p>

    {{-- Pros and Cons --}}
    @if(!empty($review->pros) || !empty($review->cons))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            @if(!empty($review->pros))
                <div>
                    <h4 class="font-semibold text-green-700 mb-2">Pros</h4>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        @foreach($review->pros as $pro)
                            <li>{{ $pro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @if(!empty($review->cons))
                <div>
                    <h4 class="font-semibold text-red-700 mb-2">Cons</h4>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        @foreach($review->cons as $con)
                            <li>{{ $con }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- Review Images --}}
    @if($review->hasMedia('images'))
        <div class="grid grid-cols-4 gap-2 mb-4">
            @foreach($review->getMedia('images') as $image)
                <a href="{{ $image->getUrl() }}" target="_blank" class="block">
                    <img src="{{ $image->getUrl('thumb') }}" alt="Review image" class="w-full h-24 object-cover rounded cursor-pointer hover:opacity-75">
                </a>
            @endforeach
        </div>
    @endif

    {{-- Admin Response --}}
    @if($review->admin_response)
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-blue-800">Response from {{ $review->responder->name ?? 'Admin' }}</p>
                    <p class="mt-1 text-sm text-blue-700 whitespace-pre-wrap">{{ $review->admin_response }}</p>
                    @if($review->responded_at)
                        <p class="mt-1 text-xs text-blue-600">{{ $review->responded_at->diffForHumans() }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Helpful Votes --}}
    <div class="flex items-center gap-4 text-sm text-gray-600">
        <span>Was this review helpful?</span>
        <button onclick="markHelpful({{ $review->id }}, true)" class="text-blue-600 hover:text-blue-800 font-medium">
            Yes ({{ $review->helpful_count }})
        </button>
        <button onclick="markHelpful({{ $review->id }}, false)" class="text-gray-600 hover:text-gray-800 font-medium">
            No ({{ $review->not_helpful_count }})
        </button>
    </div>
</div>

<script>
function markHelpful(reviewId, isHelpful) {
    fetch(`/reviews/${reviewId}/helpful`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ is_helpful: isHelpful })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            // Reload the review section
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Unable to record your vote. Please try again.');
    });
}
</script>

