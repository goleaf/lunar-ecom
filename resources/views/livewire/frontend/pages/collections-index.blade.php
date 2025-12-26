<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Collections</h1>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($collections as $collection)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2">
                        <a href="{{ route('frontend.collections.show', $collection->urls->first()->slug ?? $collection->id) }}"
                           class="text-gray-900 hover:text-gray-600">
                            {{ $collection->translateAttribute('name') }}
                        </a>
                    </h3>
                    @if($collection->translateAttribute('description'))
                        <p class="text-gray-600 mb-4">{{ $collection->translateAttribute('description') }}</p>
                    @endif
                    <a href="{{ route('frontend.collections.show', $collection->urls->first()->slug ?? $collection->id) }}"
                       class="text-blue-600 hover:text-blue-800">
                        View Collection →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-600">No collections found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $collections->links() }}
    </div>
</div>


