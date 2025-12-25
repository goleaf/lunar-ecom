@props([
    'variant',
    'currency' => null,
    'customerGroup' => null,
    'region' => null,
    'showSavings' => true,
    'highlightCurrent' => true,
    'currentQuantity' => 1,
])

@php
    $service = app(\App\Services\MatrixPricingService::class);
    $tiers = $service->getTieredPricing($variant, $currency, $customerGroup, $region);
    $basePrice = $tiers->firstWhere('savings_percent', 0)?->price ?? $tiers->first()?->price ?? 0;
@endphp

@if($tiers->count() > 1)
    <div class="tier-pricing-table" x-data="{ currentQty: {{ $currentQuantity }} }">
        <h3 class="text-lg font-semibold mb-4">Volume Discounts</h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Quantity
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Price
                        </th>
                        @if($showSavings)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Savings
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($tiers as $tier)
                        @php
                            $isCurrentTier = $highlightCurrent && 
                                            $currentQuantity >= $tier['min_quantity'] && 
                                            ($tier['max_quantity'] === null || $currentQuantity <= $tier['max_quantity']);
                            $quantityRange = $tier['max_quantity'] 
                                ? ($tier['min_quantity'] === $tier['max_quantity'] 
                                    ? $tier['min_quantity'] 
                                    : "{$tier['min_quantity']}-{$tier['max_quantity']}")
                                : "{$tier['min_quantity']}+";
                        @endphp
                        <tr class="{{ $isCurrentTier ? 'bg-blue-50 dark:bg-blue-900/20 font-semibold' : '' }} hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $quantityRange }} {{ $quantityRange === '1' ? 'unit' : 'units' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $tier['formatted_price'] }}
                            </td>
                            @if($showSavings)
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if($tier['savings_percent'] > 0)
                                        <span class="text-green-600 dark:text-green-400 font-semibold">
                                            Save {{ $tier['savings_percent'] }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($showSavings && $tiers->where('savings_percent', '>', 0)->isNotEmpty())
            <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p class="text-sm text-green-800 dark:text-green-200">
                    <strong>💡 Tip:</strong> Order more to save! 
                    @php
                        $bestTier = $tiers->where('savings_percent', '>', 0)->sortByDesc('savings_percent')->first();
                    @endphp
                    @if($bestTier)
                        Save up to {{ $bestTier['savings_percent'] }}% when ordering {{ $bestTier['min_quantity'] }}+ units.
                    @endif
                </p>
            </div>
        @endif
    </div>
@endif

