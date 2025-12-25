@php
    use App\Lunar\Brands\BrandHelper;
    $logoMedia = $brand->getFirstMedia('logo');
    $logoUrl = $logoMedia ? $logoMedia->getUrl('thumb') : null;
    $productCount = BrandHelper::getProductCount($brand);
@endphp

<div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
    <a href="{{ route('storefront.brands.show', $brand->id) }}" class="block">
        <div class="p-4">
            @if($logoUrl)
                <div class="h-24 flex items-center justify-center mb-3">
                    <img src="{{ $logoUrl }}" 
                         alt="{{ $brand->name }}" 
                         class="max-h-24 max-w-full object-contain"
                         loading="lazy">
                </div>
            @else
                <div class="h-24 bg-gray-100 flex items-center justify-center mb-3 rounded">
                    <span class="text-gray-400 text-sm font-semibold">{{ substr($brand->name, 0, 2) }}</span>
                </div>
            @endif
            <h3 class="text-center font-semibold text-gray-900 mb-1">{{ $brand->name }}</h3>
            @if($productCount > 0)
                <p class="text-center text-sm text-gray-500">{{ $productCount }} {{ $productCount === 1 ? 'product' : 'products' }}</p>
            @endif
        </div>
    </a>
</div>

