@props([
    'variant',
    'currency' => null,
    'customerGroup' => null,
    'region' => null,
])

@php
    $service = app(\App\Services\MatrixPricingService::class);
    $tiers = $service->getTieredPricing($variant, $currency, $customerGroup, $region);
@endphp

<div class="pricing-calculator" x-data="{
    quantity: 1,
    price: null,
    savings: null,
    calculatePrice() {
        if (this.quantity < 1) {
            this.quantity = 1;
        }
        
        fetch('{{ route('api.pricing.calculate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                variant_id: {{ $variant->id }},
                quantity: this.quantity,
                @if($customerGroup)
                customer_group: '{{ is_string($customerGroup) ? $customerGroup : $customerGroup->handle }}',
                @endif
                @if($region)
                region: '{{ $region }}',
                @endif
            })
        })
        .then(response => response.json())
        .then(data => {
            this.price = data.formatted_price;
            this.savings = data.you_save;
        })
        .catch(error => console.error('Error:', error));
    }
}" x-init="calculatePrice()">
    <div class="mb-4">
        <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Quantity
        </label>
        <div class="flex items-center space-x-4">
            <button 
                @click="quantity--; calculatePrice()" 
                class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
                :disabled="quantity <= 1"
            >
                −
            </button>
            <input 
                type="number" 
                id="quantity"
                x-model.number="quantity" 
                @input="calculatePrice()"
                min="1"
                class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-center dark:bg-gray-800 dark:text-gray-100"
            >
            <button 
                @click="quantity++; calculatePrice()" 
                class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600"
            >
                +
            </button>
        </div>
    </div>

    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Price</div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="price || 'Calculating...'"></div>
        <div class="text-sm text-green-600 dark:text-green-400 mt-1" x-show="savings" x-text="savings"></div>
    </div>

    @if($tiers->count() > 1)
        <div class="mt-4">
            <x-tier-pricing-table 
                :variant="$variant" 
                :currency="$currency"
                :customerGroup="$customerGroup"
                :region="$region"
                :currentQuantity="1"
                x-bind:currentQuantity="quantity"
            />
        </div>
    @endif
</div>

