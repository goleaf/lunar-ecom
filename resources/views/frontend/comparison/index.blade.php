@extends('frontend.layout')

@section('title', __('frontend.comparison.title'))

@section('content')
<div class="px-4 py-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">{{ __('frontend.comparison.title') }}</h1>
            <div class="flex gap-2">
                <button onclick="clearComparison()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300" data-comparison-confirm-clear="{{ __('frontend.comparison.confirm_clear') }}">
                    {{ __('frontend.comparison.clear_all') }}
                </button>
                <a href="{{ route('frontend.products.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    {{ __('frontend.comparison.add_more') }}
                </a>
            </div>
        </div>

        @if($products->count() > 0)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase sticky left-0 bg-gray-50 z-10">
                                    {{ __('frontend.comparison.specification') }}
                                </th>
                                @foreach($products as $product)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-[200px]">
                                        <div class="flex flex-col items-center">
                                            @if($product->getFirstMedia('images'))
                                                <img src="{{ $product->getFirstMedia('images')->getUrl('thumb') }}" 
                                                     alt="{{ $product->translateAttribute('name') }}"
                                                     class="w-24 h-24 object-cover rounded mb-2">
                                            @endif
                                            <h3 class="font-semibold text-sm mb-1">{{ $product->translateAttribute('name') }}</h3>
                                            <button onclick="removeFromComparison({{ $product->id }})" 
                                                    class="text-red-600 hover:text-red-800 text-xs mt-1">
                                                {{ __('frontend.comparison.remove') }}
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            {{-- Price --}}
                            <tr class="bg-gray-50">
                                <td class="px-4 py-3 font-medium sticky left-0 bg-gray-50 z-10">
                                    {{ __('frontend.product.price') }}
                                </td>
                                @foreach($products as $product)
                                    <td class="px-4 py-3">
                                        @php
                                            $variant = $product->variants->first();
                                            $currency = \Lunar\Facades\Currency::getDefault();
                                            $price = null;
                                            if ($variant) {
                                                $pricing = \Lunar\Facades\Pricing::for($variant)->get();
                                                if ($pricing->matched?->price) {
                                                    $price = $pricing->matched->price->value;
                                                }
                                            }
                                        @endphp
                                        @if($price)
                                            <span class="text-lg font-bold text-blue-600">
                                                {{ $currency->formatter($price) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">{{ __('frontend.comparison.not_available') }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Brand --}}
                            @if($products->pluck('brand')->filter()->isNotEmpty())
                                <tr>
                                    <td class="px-4 py-3 font-medium sticky left-0 bg-white z-10">
                                        {{ __('frontend.brand') }}
                                    </td>
                                    @foreach($products as $product)
                                        <td class="px-4 py-3">
                                            {{ $product->brand?->name ?? __('frontend.comparison.not_available') }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endif

                            {{-- Rating --}}
                            <tr>
                                <td class="px-4 py-3 font-medium sticky left-0 bg-white z-10">
                                    {{ __('frontend.comparison.rating') }}
                                </td>
                                @foreach($products as $product)
                                    <td class="px-4 py-3">
                                        @if($product->average_rating > 0)
                                            <div class="flex items-center gap-1">
                                                <span class="text-yellow-500">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        {{ $i <= round($product->average_rating) ? '★' : '☆' }}
                                                    @endfor
                                                </span>
                                                <span class="text-sm text-gray-600">
                                                    ({{ $product->total_reviews }})
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">{{ __('frontend.comparison.no_ratings') }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Stock Status --}}
                            <tr>
                                <td class="px-4 py-3 font-medium sticky left-0 bg-white z-10">
                                    {{ __('frontend.comparison.availability') }}
                                </td>
                                @foreach($products as $product)
                                    <td class="px-4 py-3">
                                        @php
                                            $variant = $product->variants->first();
                                            $inStock = $variant && $variant->stock > 0;
                                        @endphp
                                        @if($inStock)
                                            <span class="text-green-600 font-medium">{{ __('frontend.comparison.in_stock') }}</span>
                                        @else
                                            <span class="text-red-600 font-medium">{{ __('frontend.comparison.out_of_stock') }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Description --}}
                            <tr>
                                <td class="px-4 py-3 font-medium sticky left-0 bg-white z-10">
                                    {{ __('frontend.comparison.description') }}
                                </td>
                                @foreach($products as $product)
                                    <td class="px-4 py-3 text-sm">
                                        {{ Str::limit(strip_tags($product->translateAttribute('description') ?? ''), 100) }}
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Specifications --}}
                            @foreach($specifications as $key => $label)
                                @if($products->pluck($key)->filter()->isNotEmpty())
                                    <tr>
                                        <td class="px-4 py-3 font-medium sticky left-0 bg-white z-10">
                                            {{ $label }}
                                        </td>
                                        @foreach($products as $product)
                                            <td class="px-4 py-3">
                                                @php
                                                    $value = $product->$key;
                                                    if ($key === 'dimensions' && $product->length && $product->width && $product->height) {
                                                        $value = "{$product->length} × {$product->width} × {$product->height} cm";
                                                    } elseif ($key === 'weight' && $value) {
                                                        $value = number_format($value / 1000, 2) . ' kg';
                                                    } elseif ($key === 'warranty_period' && $value) {
                                                        $value = $value . ' months';
                                                    }
                                                @endphp
                                                {{ $value ?? __('frontend.comparison.not_available') }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach

                            {{-- Attributes --}}
                            @foreach($attributes as $attribute)
                                <tr>
                                    <td class="px-4 py-3 font-medium sticky left-0 bg-white z-10">
                                        {{ $attribute['name'][app()->getLocale()] ?? $attribute['handle'] }}
                                    </td>
                                    @foreach($products as $product)
                                        <td class="px-4 py-3">
                                            @php
                                                $attributeValue = $product->attributeValues
                                                    ->where('attribute_id', $attribute['id'])
                                                    ->first();
                                            @endphp
                                            @if($attributeValue)
                                                {{ $attributeValue->getDisplayValue() }}
                                            @else
                                                <span class="text-gray-400">{{ __('frontend.comparison.not_available') }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            {{-- Actions --}}
                            <tr class="bg-gray-50">
                                <td class="px-4 py-3 font-medium sticky left-0 bg-gray-50 z-10">
                                    {{ __('frontend.comparison.actions') }}
                                </td>
                                @foreach($products as $product)
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ route('frontend.products.show', $product) }}" 
                                               class="text-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                                                {{ __('frontend.product.view_details') }}
                                            </a>
                                            <form method="POST" action="{{ route('frontend.cart.add') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="purchasable_type" value="Lunar\Models\ProductVariant">
                                                <input type="hidden" name="purchasable_id" value="{{ $product->variants->first()?->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                                                    {{ __('frontend.product.add_to_cart') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <p class="text-gray-600 mb-4">{{ __('frontend.comparison.empty') }}</p>
                <a href="{{ route('frontend.products.index') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    {{ __('frontend.comparison.browse_products') }}
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
@endpush
@endsection

