@extends('storefront.layout')

@section('title', $product->translateAttribute('name'))

@section('content')
<div class="px-4 py-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <div>
                @if($product->thumbnail)
                    <img src="{{ $product->thumbnail->getUrl() }}" alt="{{ $product->translateAttribute('name') }}" class="w-full rounded">
                @else
                    <div class="w-full h-96 bg-gray-200 flex items-center justify-center rounded">
                        <span class="text-gray-400">No Image</span>
                    </div>
                @endif
            </div>
            <div>
                <h1 class="text-3xl font-bold mb-4">{{ $product->translateAttribute('name') }}</h1>
                <p class="text-gray-600 mb-6">{{ $product->translateAttribute('description') }}</p>

                @if($product->variants->count() > 0)
                    @php
                        $defaultVariant = $product->variants->first();
                        $price = $defaultVariant->price;
                    @endphp
                    <div class="mb-6">
                        <p class="text-3xl font-bold text-gray-900">{{ $price->formatted }}</p>
                    </div>

                    <form action="{{ route('storefront.cart.add') }}" method="POST" class="mb-6">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $defaultVariant->id }}">
                        <div class="mb-4">
                            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="999" class="border rounded px-3 py-2 w-24">
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                            Add to Cart
                        </button>
                    </form>
                @endif

                @if($product->tags->count() > 0)
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-700 mb-2">Tags:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->tags as $tag)
                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">{{ $tag->value }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($crossSell->count() > 0 || $upSell->count() > 0 || $alternate->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">Related Products</h2>
            
            @if($crossSell->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">You May Also Like</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($crossSell as $related)
                            @include('storefront.products._product-card', ['product' => $related])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($upSell->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Upgrade Options</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($upSell as $related)
                            @include('storefront.products._product-card', ['product' => $related])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($alternate->count() > 0)
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-4">Alternatives</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($alternate as $related)
                            @include('storefront.products._product-card', ['product' => $related])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

