@extends('admin.layout')

@section('title', 'Review Moderation')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Review Moderation</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.reviews.moderation', ['status' => 'pending']) }}" 
               class="px-4 py-2 rounded {{ $status === 'pending' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Pending ({{ \App\Models\Review::pending()->count() }})
            </a>
            <a href="{{ route('admin.reviews.moderation', ['status' => 'approved']) }}" 
               class="px-4 py-2 rounded {{ $status === 'approved' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Approved ({{ \App\Models\Review::approved()->count() }})
            </a>
            <a href="{{ route('admin.reviews.moderation', ['status' => 'reported']) }}" 
               class="px-4 py-2 rounded {{ $status === 'reported' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Reported ({{ \App\Models\Review::reported()->count() }})
            </a>
        </div>
    </div>

    {{-- Bulk Actions --}}
    <form id="bulkActionsForm" method="POST" action="">
        @csrf
        <div class="mb-4 flex items-center gap-4">
            <button type="button" 
                    onclick="selectAll()" 
                    class="px-4 py-2 bg-gray-200 rounded text-sm">
                Select All
            </button>
            <button type="button" 
                    onclick="deselectAll()" 
                    class="px-4 py-2 bg-gray-200 rounded text-sm">
                Deselect All
            </button>
            <button type="button" 
                    onclick="bulkApprove()" 
                    class="px-4 py-2 bg-green-500 text-white rounded text-sm">
                Approve Selected
            </button>
            <button type="button" 
                    onclick="bulkReject()" 
                    class="px-4 py-2 bg-red-500 text-white rounded text-sm">
                Reject Selected
            </button>
        </div>

        {{-- Reviews List --}}
        <div class="space-y-4">
            @forelse($reviews as $review)
                <div class="border rounded-lg p-4 bg-white">
                    <div class="flex items-start gap-4">
                        <input type="checkbox" 
                               name="review_ids[]" 
                               value="{{ $review->id }}"
                               class="review-checkbox mt-2">
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="text-yellow-500">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                                <h3 class="font-semibold text-lg">{{ $review->title }}</h3>
                                @if($review->is_verified_purchase)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Verified Purchase</span>
                                @endif
                                @if($review->is_approved)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Approved</span>
                                @endif
                            </div>
                            
                            <p class="text-gray-700 mb-2">{{ $review->content }}</p>
                            
                            <div class="text-sm text-gray-500 mb-2">
                                <span>{{ $review->customer->name ?? 'Anonymous' }}</span>
                                <span> • </span>
                                <span>{{ $review->created_at->format('M d, Y H:i') }}</span>
                                <span> • </span>
                                <a href="{{ route('storefront.products.show', $review->product->id) }}" 
                                   class="text-blue-600 hover:underline">
                                    {{ $review->product->translateAttribute('name') }}
                                </a>
                            </div>

                            @if($review->pros && count($review->pros) > 0)
                                <div class="mt-2">
                                    <strong class="text-green-700">Pros:</strong>
                                    <ul class="list-disc list-inside text-sm ml-4">
                                        @foreach($review->pros as $pro)
                                            <li>{{ $pro }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($review->cons && count($review->cons) > 0)
                                <div class="mt-2">
                                    <strong class="text-red-700">Cons:</strong>
                                    <ul class="list-disc list-inside text-sm ml-4">
                                        @foreach($review->cons as $con)
                                            <li>{{ $con }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($review->recommended !== null)
                                <div class="mt-2">
                                    <span class="px-2 py-1 rounded text-xs {{ $review->recommended ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $review->recommended ? 'Recommended' : 'Not Recommended' }}
                                    </span>
                                </div>
                            @endif

                            @if($review->media->count() > 0)
                                <div class="mt-3 flex gap-2 flex-wrap">
                                    @foreach($review->media as $media)
                                        <a href="{{ $media->getUrl('large') }}" target="_blank">
                                            <img src="{{ $media->getUrl('thumb') }}" 
                                                 alt="Review image" 
                                                 class="w-20 h-20 object-cover rounded border hover:opacity-75">
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if($review->admin_response)
                                <div class="mt-3 p-3 bg-blue-50 rounded border-l-4 border-blue-500">
                                    <strong class="text-blue-800">Admin Response:</strong>
                                    <p class="text-sm text-gray-700 mt-1">{{ $review->admin_response }}</p>
                                    @if($review->responded_at)
                                        <p class="text-xs text-gray-500 mt-1">
                                            Responded on {{ $review->responded_at->format('M d, Y H:i') }}
                                        </p>
                                    @endif
                                </div>
                            @endif

                            @if($review->helpful_count > 0 || $review->not_helpful_count > 0)
                                <div class="mt-2 text-sm text-gray-600">
                                    <span>Helpful: {{ $review->helpful_count }}</span>
                                    <span class="mx-2">•</span>
                                    <span>Not Helpful: {{ $review->not_helpful_count }}</span>
                                </div>
                            @endif

                            @if($review->report_count > 0)
                                <div class="mt-2">
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">
                                        Reported {{ $review->report_count }} time(s)
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2">
                            @if($status === 'pending')
                                <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="px-4 py-2 bg-green-500 text-white rounded text-sm hover:bg-green-600">
                                        Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="px-4 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                        Reject
                                    </button>
                                </form>
                            @endif
                            <button onclick="openResponseModal({{ $review->id }}, '{{ addslashes($review->admin_response ?? '') }}')" 
                                    class="px-4 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                                {{ $review->admin_response ? 'Edit Response' : 'Add Response' }}
                            </button>
                            <a href="{{ route('admin.reviews.show', $review) }}" 
                               class="px-4 py-2 bg-gray-500 text-white rounded text-sm hover:bg-gray-600 text-center">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <p class="text-gray-600">No reviews found for this status.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($reviews->hasPages())
            <div class="mt-6">
                {{ $reviews->links() }}
            </div>
        @endif
    </form>
</div>

{{-- Admin Response Modal --}}
<div id="responseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Add Admin Response</h3>
        
        <form id="responseForm" method="POST" action="">
            @csrf
            <textarea id="responseText" 
                      name="response" 
                      class="w-full border rounded p-2" 
                      rows="5"
                      placeholder="Enter your response..."></textarea>
            <div id="responseError" class="text-red-500 text-sm mt-1 hidden"></div>
            
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" 
                        onclick="closeResponseModal()"
                        class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Save Response
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.review-checkbox').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('.review-checkbox').forEach(cb => cb.checked = false);
}

function bulkApprove() {
    const form = document.getElementById('bulkActionsForm');
    const selected = Array.from(document.querySelectorAll('.review-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Please select at least one review.');
        return;
    }
    
    if (!confirm(`Approve ${selected.length} review(s)?`)) {
        return;
    }
    
    form.action = '{{ route("admin.reviews.bulk-approve") }}';
    form.method = 'POST';
    form.innerHTML += '<input type="hidden" name="review_ids[]" value="' + selected.join('"><input type="hidden" name="review_ids[]" value="') + '">';
    form.innerHTML += '@csrf';
    form.submit();
}

function bulkReject() {
    const form = document.getElementById('bulkActionsForm');
    const selected = Array.from(document.querySelectorAll('.review-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Please select at least one review.');
        return;
    }
    
    if (!confirm(`Reject ${selected.length} review(s)?`)) {
        return;
    }
    
    form.action = '{{ route("admin.reviews.bulk-reject") }}';
    form.method = 'POST';
    form.innerHTML += '<input type="hidden" name="review_ids[]" value="' + selected.join('"><input type="hidden" name="review_ids[]" value="') + '">';
    form.innerHTML += '@csrf';
    form.submit();
}

function openResponseModal(reviewId, currentResponse = '') {
    const modal = document.getElementById('responseModal');
    const form = document.getElementById('responseForm');
    const textarea = document.getElementById('responseText');
    
    form.action = '{{ route("admin.reviews.add-response", ":id") }}'.replace(':id', reviewId);
    textarea.value = currentResponse;
    modal.classList.remove('hidden');
}

function closeResponseModal() {
    const modal = document.getElementById('responseModal');
    modal.classList.add('hidden');
    document.getElementById('responseText').value = '';
    document.getElementById('responseError').classList.add('hidden');
}

// Handle form submission
document.getElementById('responseForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    });
    
    const data = await response.json();
    
    if (response.ok) {
        closeResponseModal();
        location.reload();
    } else {
        const errorDiv = document.getElementById('responseError');
        errorDiv.textContent = data.message || 'An error occurred';
        errorDiv.classList.remove('hidden');
    }
});
</script>
@endsection

