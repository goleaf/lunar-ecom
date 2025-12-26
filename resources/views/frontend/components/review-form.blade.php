@props(['product'])

<div class="bg-gray-50 p-6 rounded-lg">
    <h3 class="text-xl font-bold mb-4">Write a Review</h3>
    
    <form id="review-form" action="{{ route('frontend.reviews.store', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        
        {{-- Rating --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rating *</label>
            <div class="flex gap-2" id="rating-stars">
                @for($i = 1; $i <= 5; $i++)
                    <button type="button" onclick="setRating({{ $i }})" class="rating-star text-3xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="{{ $i }}">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </button>
                @endfor
            </div>
            <input type="hidden" name="rating" id="rating-input" required>
            @error('rating')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Review Title *</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required minlength="10" maxlength="255" class="w-full border rounded px-3 py-2">
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Content --}}
        <div>
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Your Review *</label>
            <textarea name="content" id="content" rows="6" required minlength="10" maxlength="5000" class="w-full border rounded px-3 py-2">{{ old('content') }}</textarea>
            <p class="mt-1 text-sm text-gray-500">Minimum 10 characters, maximum 5000 characters</p>
            @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Pros --}}
        <div>
            <label for="pros" class="block text-sm font-medium text-gray-700 mb-2">Pros (optional)</label>
            <div id="pros-container" class="space-y-2">
                <div class="flex gap-2">
                    <input type="text" name="pros[]" class="flex-1 border rounded px-3 py-2" placeholder="Enter a pro">
                    <button type="button" onclick="addProField()" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">+</button>
                </div>
            </div>
        </div>

        {{-- Cons --}}
        <div>
            <label for="cons" class="block text-sm font-medium text-gray-700 mb-2">Cons (optional)</label>
            <div id="cons-container" class="space-y-2">
                <div class="flex gap-2">
                    <input type="text" name="cons[]" class="flex-1 border rounded px-3 py-2" placeholder="Enter a con">
                    <button type="button" onclick="addConField()" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">+</button>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div>
            <label for="images" class="block text-sm font-medium text-gray-700 mb-2">Upload Images (optional, max 5)</label>
            <input type="file" name="images[]" id="images" multiple accept="image/jpeg,image/png,image/webp" class="w-full border rounded px-3 py-2">
            <p class="mt-1 text-sm text-gray-500">You can upload up to 5 images (JPEG, PNG, or WebP, max 2MB each)</p>
            @error('images')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Recommended --}}
        <div class="flex items-center">
            <input type="checkbox" name="recommended" id="recommended" value="1" checked class="h-4 w-4 text-blue-600 rounded">
            <label for="recommended" class="ml-2 text-sm text-gray-700">I recommend this product</label>
        </div>

        {{-- Order ID (for verified purchase) --}}
        @php
            $customer = auth()->user()?->customer ?? null;
            $customerOrders = $customer 
                ? \Lunar\Models\Order::where('customer_id', $customer->id)
                    ->whereNotNull('placed_at')
                    ->with('lines.purchasable')
                    ->get()
                    ->filter(function($order) use ($product) {
                        return $order->lines->contains(function($line) use ($product) {
                            if ($line->purchasable_type === \Lunar\Models\ProductVariant::class && $line->purchasable) {
                                return $line->purchasable->product_id === $product->id;
                            }
                            return false;
                        });
                    })
                : collect();
        @endphp
        
        @if($customerOrders->count() > 0)
            <div>
                <label for="order_id" class="block text-sm font-medium text-gray-700 mb-2">Select Order (for verified purchase badge)</label>
                <select name="order_id" id="order_id" class="w-full border rounded px-3 py-2">
                    <option value="">No order selected</option>
                    @foreach($customerOrders as $order)
                        <option value="{{ $order->id }}">
                            Order #{{ $order->reference ?? $order->id }} - {{ $order->placed_at->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Submit Review
            </button>
            <a href="{{ route('frontend.reviews.guidelines') }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm self-center">
                Review Guidelines
            </a>
        </div>
    </form>
</div>

<script>
let selectedRating = 0;

function setRating(rating) {
    selectedRating = rating;
    document.getElementById('rating-input').value = rating;
    
    const stars = document.querySelectorAll('.rating-star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-300');
        }
    });
}

function addProField() {
    const container = document.getElementById('pros-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" name="pros[]" class="flex-1 border rounded px-3 py-2" placeholder="Enter a pro">
        <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-red-200 rounded hover:bg-red-300">-</button>
    `;
    container.appendChild(div);
}

function addConField() {
    const container = document.getElementById('cons-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" name="cons[]" class="flex-1 border rounded px-3 py-2" placeholder="Enter a con">
        <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-red-200 rounded hover:bg-red-300">-</button>
    `;
    container.appendChild(div);
}

// Validate form before submit
document.getElementById('review-form').addEventListener('submit', function(e) {
    if (!selectedRating) {
        e.preventDefault();
        alert('Please select a rating');
        return false;
    }
});
</script>


