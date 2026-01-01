@extends('frontend.layout')

@section('title', $bundle->name)

@section('content')
<div class="px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            {{-- Bundle Image --}}
            <div>
                @if($bundle->image)
                    <img src="{{ asset($bundle->image) }}" alt="{{ $bundle->name }}" class="w-full rounded-lg">
                @elseif($bundle->product && $bundle->product->getFirstMedia('images'))
                    <img src="{{ $bundle->product->getFirstMedia('images')->getUrl('large') }}" alt="{{ $bundle->name }}" class="w-full rounded-lg">
                @else
                    <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="text-gray-400">{{ __('frontend.product.no_image') }}</span>
                    </div>
                @endif
            </div>

            {{-- Bundle Details --}}
            <div>
                <h1 class="text-3xl font-bold mb-4">{{ $bundle->name }}</h1>
                
                @if($bundle->description)
                    <div class="prose mb-6">
                        {!! $bundle->description !!}
                    </div>
                @endif

                {{-- Pricing --}}
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    @if($bundle->show_individual_prices)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-1">{{ __('frontend.bundles.individual_total') }}</p>
                            <p class="text-xl line-through text-gray-500">{{ (new \Lunar\DataTypes\Price($individualTotal, $currency))->formatted() }}</p>
                        </div>
                    @endif

                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-1">{{ __('frontend.bundles.bundle_price') }}</p>
                        <p class="text-4xl font-bold text-blue-600">{{ (new \Lunar\DataTypes\Price($bundlePrice, $currency))->formatted() }}</p>
                    </div>

                    @if($savings > 0 && $bundle->show_savings)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-1">{{ __('frontend.bundles.you_save') }}</p>
                            <p class="text-2xl font-semibold text-green-600">{{ (new \Lunar\DataTypes\Price($savings, $currency))->formatted() }}</p>
                        </div>
                    @endif

                    @if($availableStock !== null)
                        <div class="mb-4">
                            @if($availableStock > 0)
                                <p class="text-sm text-green-600">{{ __('frontend.bundles.in_stock', ['quantity' => $availableStock]) }}</p>
                            @else
                                <p class="text-sm text-red-600">{{ __('frontend.bundles.out_of_stock') }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Add to Cart Form --}}
                    <form id="addToCartForm" method="POST" action="{{ route('frontend.bundles.add-to-cart', $bundle) }}" class="space-y-4" data-cart-url="{{ route('frontend.cart.index') }}">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('frontend.product.quantity') }}</label>
                            <input type="number" 
                                   name="quantity" 
                                   id="quantity"
                                   value="1" 
                                   min="{{ $bundle->min_quantity }}"
                                   @if($bundle->max_quantity) max="{{ $bundle->max_quantity }}" @endif
                                   class="border rounded px-3 py-2 w-24">
                        </div>

                        @if($bundle->allow_customization)
                            <div id="customizationOptions" class="space-y-3">
                                <p class="font-medium">{{ __('frontend.bundles.customize_items') }}</p>
                                @foreach($bundle->items as $item)
                                    <div class="border rounded p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="font-medium">
                                                {{ $item->product->translateAttribute('name') }}
                                                @if($item->is_required)
                                                    <span class="text-red-600">*</span>
                                                @endif
                                            </label>
                                            <span class="text-sm text-gray-600">
                                                {{ (new \Lunar\DataTypes\Price($item->getPrice($currency), $currency))->formatted() }} each
                                            </span>
                                        </div>
                                        <input type="number" 
                                               name="selected_items[{{ $item->id }}]"
                                               value="{{ $item->is_default ? $item->quantity : ($item->is_required ? $item->min_quantity : 0) }}"
                                               min="{{ $item->is_required ? $item->min_quantity : 0 }}"
                                               @if($item->max_quantity) max="{{ $item->max_quantity }}" @endif
                                               @if($item->is_required) required @endif
                                               class="border rounded px-3 py-1 w-24">
                                        @if($item->notes)
                                            <p class="text-xs text-gray-500 mt-1">{{ $item->notes }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" 
                                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold"
                                @if($availableStock !== null && $availableStock <= 0) disabled @endif>
                            {{ __('frontend.product.add_to_cart') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bundle Items --}}
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">{{ __('frontend.bundles.bundle_contents') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($bundle->items as $item)
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            @if($item->product->getFirstMedia('images'))
                                <img src="{{ $item->product->getFirstMedia('images')->getUrl('thumb') }}" 
                                     alt="{{ $item->product->translateAttribute('name') }}"
                                     class="w-20 h-20 object-cover rounded">
                            @endif
                            <div class="flex-1">
                                <h3 class="font-semibold mb-1">
                                    <a href="{{ route('frontend.products.show', $item->product) }}" class="hover:text-blue-600">
                                        {{ $item->product->translateAttribute('name') }}
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    {{ __('frontend.bundles.quantity') }}: {{ $item->quantity }}
                                    @if($item->is_required)
                                        <span class="text-red-600">({{ __('frontend.bundles.required') }})</span>
                                    @else
                                        <span class="text-gray-500">({{ __('frontend.bundles.optional') }})</span>
                                    @endif
                                </p>
                                <p class="text-sm font-medium text-blue-600">
                                    {{ (new \Lunar\DataTypes\Price($item->getPrice($currency), $currency))->formatted() }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
@endpush
@endsection


