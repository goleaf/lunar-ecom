@extends('storefront.layout')

@section('title', $collection->translateAttribute('name'))

@section('content')
<div class="px-4 py-6">
    @if(isset($breadcrumb) && $breadcrumb->count() > 0)
        <nav class="mb-4 text-sm">
            <ol class="flex items-center space-x-2">
                @foreach($breadcrumb as $crumb)
                    <li>
                        <span class="text-gray-500">{{ $crumb }}</span>
                        @if(!$loop->last)
                            <span class="mx-2 text-gray-400">/</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <h1 class="text-3xl font-bold mb-2">{{ $collection->translateAttribute('name') }}</h1>
    @if($collection->translateAttribute('description'))
        <p class="text-gray-600 mb-6">{{ $collection->translateAttribute('description') }}</p>
    @endif

    @if($collection->children->count() > 0)
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-4">Sub-collections</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($collection->children as $child)
                    <a href="{{ route('storefront.collections.show', $child->urls->first()?->slug ?? $child->id) }}" 
                       class="block p-4 border rounded hover:bg-gray-50">
                        <h3 class="font-semibold">{{ $child->translateAttribute('name') }}</h3>
                    </a>
                @endforeach
            </div>
        </div>
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

