@extends('frontend.layout')

@section('title', __('frontend.categories'))

@section('meta')
    <meta name="description" content="Browse all product categories">
    <meta property="og:title" content="Categories">
    <meta property="og:description" content="Browse all product categories">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/categories') }}">
    <link rel="canonical" href="{{ url('/categories') }}">
@endsection

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Categories</h1>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($categories as $category)
            <a href="{{ route('categories.show', $category->getFullPath()) }}" 
               class="block bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                @if($category->getImageUrl('thumb'))
                    <div class="w-full h-48 overflow-hidden">
                        <img src="{{ $category->getImageUrl('thumb') }}" 
                             alt="{{ $category->getName() }}" 
                             class="w-full h-full object-cover">
                    </div>
                @endif
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2 text-gray-900">
                        {{ $category->getName() }}
                    </h3>
                    @if($category->getDescription())
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ Str::limit($category->getDescription(), 100) }}</p>
                    @endif
                    @if($category->product_count > 0)
                        <p class="text-sm text-gray-500 mb-2">{{ $category->product_count }} {{ __('frontend.products') }}</p>
                    @endif
                    @if($category->getChildren()->count() > 0)
                        <p class="text-sm text-gray-500">{{ $category->getChildren()->count() }} {{ __('frontend.subcategories') }}</p>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-600">No categories found.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection


