@if($product->isComingSoon())
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold text-blue-900 mb-2">Coming Soon</h3>
        <p class="text-blue-700 mb-4">
            {{ $product->coming_soon_message ?? 'This product will be available soon!' }}
        </p>
        @if($product->expected_available_at)
            <p class="text-sm text-blue-600 mb-4">
                Expected availability: {{ $product->expected_available_at->format('M d, Y') }}
            </p>
        @endif

        <form id="comingSoonForm" class="space-y-3" data-url="{{ route('frontend.coming-soon.subscribe', $product->id) }}">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Get notified when available</label>
                <div class="flex gap-2">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter your email"
                    >
                    <button 
                        type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Notify Me
                    </button>
                </div>
            </div>
            <div id="comingSoonMessage" class="text-sm"></div>
        </form>
    </div>

@endif



