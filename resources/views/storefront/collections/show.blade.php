@extends('storefront.layout')

@section('title', $collection->translateAttribute('name'))

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-2">{{ $collection->translateAttribute('name') }}</h1>
    @if($collection->translateAttribute('description'))
        <p class="text-gray-600 mb-6">{{ $collection->translateAttribute('description') }}</p>
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($products as $product)
            @include('storefront.products._product-card', ['product' => $product])
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-600">No products in this collection.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
@endsection

