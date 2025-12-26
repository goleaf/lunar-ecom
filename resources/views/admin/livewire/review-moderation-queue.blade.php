<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Review Moderation Queue</h2>
        <div class="flex gap-2">
            <button wire:click="$set('status', 'pending')" 
                    class="px-4 py-2 rounded {{ $status === 'pending' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Pending ({{ \App\Models\Review::pending()->count() }})
            </button>
            <button wire:click="$set('status', 'approved')" 
                    class="px-4 py-2 rounded {{ $status === 'approved' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Approved ({{ \App\Models\Review::approved()->count() }})
            </button>
            <button wire:click="$set('status', 'reported')" 
                    class="px-4 py-2 rounded {{ $status === 'reported' ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Reported ({{ \App\Models\Review::reported()->count() }})
            </button>
        </div>
    </div>

    @if(count($selectedReviews) > 0)
        <div class="mb-4 p-4 bg-blue-50 rounded">
            <div class="flex justify-between items-center">
                <span>{{ count($selectedReviews) }} review(s) selected</span>
                <div class="flex gap-2">
                    <button wire:click="bulkApprove" class="px-4 py-2 bg-green-500 text-white rounded">
                        Approve Selected
                    </button>
                    <button wire:click="bulkReject" class="px-4 py-2 bg-red-500 text-white rounded">
                        Reject Selected
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="space-y-4">
        @foreach($reviews as $review)
            <div class="border rounded-lg p-4 bg-white">
                <div class="flex items-start gap-4">
                    <input type="checkbox" 
                           wire:model="selectedReviews" 
                           value="{{ $review->id }}"
                           class="mt-2">
                    
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="text-yellow-500">
                                @for($i = 1; $i <= 5; $i++)
                                    <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                @endfor
                            </div>
                            <h3 class="font-semibold">{{ $review->title }}</h3>
                            @if($review->is_verified_purchase)
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Verified Purchase</span>
                            @endif
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-2">{{ $review->content }}</p>
                        
                        <div class="text-xs text-gray-500 mb-2">
                            <span>{{ $review->customer->name ?? 'Anonymous' }}</span>
                            <span> • </span>
                            <span>{{ $review->created_at->format('M d, Y') }}</span>
                            <span> • </span>
                            <a href="{{ route('storefront.products.show', $review->product->id) }}" class="text-blue-600">
                                {{ $review->product->translateAttribute('name') }}
                            </a>
                        </div>

                        @if($review->pros)
                            <div class="mt-2">
                                <strong class="text-green-700">Pros:</strong>
                                <ul class="list-disc list-inside text-sm">
                                    @foreach($review->pros as $pro)
                                        <li>{{ $pro }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($review->cons)
                            <div class="mt-2">
                                <strong class="text-red-700">Cons:</strong>
                                <ul class="list-disc list-inside text-sm">
                                    @foreach($review->cons as $con)
                                        <li>{{ $con }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($review->media->count() > 0)
                            <div class="mt-2 flex gap-2">
                                @foreach($review->media as $media)
                                    <img src="{{ $media->getUrl() }}" alt="Review image" class="w-20 h-20 object-cover rounded">
                                @endforeach
                            </div>
                        @endif

                        @if($review->admin_response)
                            <div class="mt-2 p-2 bg-blue-50 rounded">
                                <strong>Admin Response:</strong>
                                <p class="text-sm">{{ $review->admin_response }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2">
                        @if($status === 'pending')
                            <button wire:click="approveReview({{ $review->id }})" 
                                    class="px-4 py-2 bg-green-500 text-white rounded text-sm">
                                Approve
                            </button>
                            <button wire:click="rejectReview({{ $review->id }})" 
                                    class="px-4 py-2 bg-red-500 text-white rounded text-sm">
                                Reject
                            </button>
                        @endif
                        <button wire:click="openResponseModal({{ $review->id }})" 
                                class="px-4 py-2 bg-blue-500 text-white rounded text-sm">
                            Add Response
                        </button>
                    </div>
                </div>
            </div>
        @endforeach

        @if($reviews->isEmpty())
            <div class="text-center py-8 text-gray-500">
                No reviews found for this status.
            </div>
        @endif
    </div>

    <!-- Admin Response Modal -->
    @if($showResponseModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">Add Admin Response</h3>
                
                <form wire:submit.prevent="saveAdminResponse">
                    <textarea wire:model="adminResponse" 
                              class="w-full border rounded p-2" 
                              rows="5"
                              placeholder="Enter your response..."></textarea>
                    @error('adminResponse') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" 
                                wire:click="$set('showResponseModal', false)"
                                class="px-4 py-2 bg-gray-200 rounded">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white rounded">
                            Save Response
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>


