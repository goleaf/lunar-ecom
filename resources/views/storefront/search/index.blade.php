@extends('storefront.layout')

@section('title', 'Search')

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Search Results</h1>

    @if($query)
        <p class="text-gray-600 mb-6">Results for: <strong>{{ $query }}</strong></p>
    @else
        <p class="text-gray-600 mb-6">Enter a search term to find products.</p>
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            @include('storefront.products._product-card', ['product' => $product])
        @empty
            @if($query)
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-600">No products found for "{{ $query }}".</p>
                </div>
            @endif
        @endforelse
    </div>

    @if($products->count() > 0)
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection

