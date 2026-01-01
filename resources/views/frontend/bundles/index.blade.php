@extends('frontend.layout')

@section('title', __('frontend.bundles.title'))

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">{{ __('frontend.bundles.title') }}</h1>

    @if($bundles->count() > 0)
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($bundles as $bundle)
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                    @if($bundle->image)
                        <img src="{{ asset($bundle->image) }}" alt="{{ $bundle->name }}" class="w-full h-48 object-cover">
                    @elseif($bundle->product && $bundle->product->getFirstMedia('images'))
                        <img src="{{ $bundle->product->getFirstMedia('images')->getUrl('medium') }}" alt="{{ $bundle->name }}" class="w-full h-48 object-cover">
                    @endif
                    
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-2">
                            <a href="{{ route('frontend.bundles.show', $bundle) }}" class="hover:text-blue-600">
                                {{ $bundle->name }}
                            </a>
                        </h3>
                        
                        @if($bundle->description)
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $bundle->description }}</p>
                        @endif

                        @php
                            $currency = \Lunar\Models\Currency::getDefault();
                            $customerGroupId = \Lunar\Facades\StorefrontSession::getCustomerGroups()->first()?->id;
                            $individualTotal = $bundle->calculateIndividualTotal($currency, $customerGroupId);
                            $bundlePrice = $bundle->calculatePrice($currency, $customerGroupId);
                            $savings = $bundle->calculateSavings($currency, $customerGroupId);
                        @endphp

                        <div class="mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold text-blue-600">
                                    {{ (new \Lunar\DataTypes\Price($bundlePrice, $currency))->formatted() }}
                                </span>
                                @if($savings > 0 && $bundle->show_savings)
                                    <span class="text-sm text-gray-500 line-through">
                                        {{ (new \Lunar\DataTypes\Price($individualTotal, $currency))->formatted() }}
                                    </span>
                                    <span class="text-sm bg-green-100 text-green-800 px-2 py-1 rounded">
                                        Save {{ (new \Lunar\DataTypes\Price($savings, $currency))->formatted() }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-gray-600 mb-3">
                            <p>{{ $bundle->items->count() }} {{ __('frontend.bundles.items') }}</p>
                        </div>

                        <a href="{{ route('frontend.bundles.show', $bundle) }}" 
                           class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            {{ __('frontend.bundles.view_bundle') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <p class="text-gray-600">{{ __('frontend.bundles.no_bundles') }}</p>
        </div>
    @endif
</div>
@endsection


