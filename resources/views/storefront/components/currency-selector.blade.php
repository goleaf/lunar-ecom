{{-- Currency Selector Component --}}
<div class="relative" x-data="currencySelector()" x-init="init()">
    <button 
        @click="toggleDropdown()"
        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
        type="button"
    >
        <span x-text="currentCurrency?.code || 'USD'"></span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div 
        x-show="isOpen"
        @click.away="closeDropdown()"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200"
        style="display: none;"
    >
        <div class="py-1">
            <template x-for="currency in currencies" :key="currency.code">
                <button
                    @click="switchCurrency(currency.code)"
                    :class="{
                        'bg-blue-50 text-blue-700': currency.code === currentCurrency?.code,
                        'text-gray-700 hover:bg-gray-100': currency.code !== currentCurrency?.code
                    }"
                    class="w-full text-left px-4 py-2 text-sm flex items-center justify-between"
                    type="button"
                >
                    <span>
                        <span x-text="currency.code" class="font-medium"></span>
                        <span class="text-gray-500 ml-2" x-text="currency.name"></span>
                    </span>
                    <svg 
                        x-show="currency.code === currentCurrency?.code"
                        class="w-4 h-4 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </template>
        </div>
    </div>
</div>

<script>
function currencySelector() {
    return {
        isOpen: false,
        currencies: [],
        currentCurrency: null,
        loading: false,

        async init() {
            await this.loadCurrencies();
        },

        async loadCurrencies() {
            try {
                const response = await fetch('{{ route("storefront.currency.index") }}');
                const data = await response.json();
                this.currencies = data.currencies || [];
                this.currentCurrency = data.current || this.currencies.find(c => c.is_default) || this.currencies[0];
            } catch (error) {
                console.error('Failed to load currencies:', error);
            }
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
        },

        closeDropdown() {
            this.isOpen = false;
        },

        async switchCurrency(currencyCode) {
            if (this.loading || currencyCode === this.currentCurrency?.code) {
                this.closeDropdown();
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route("storefront.currency.switch") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ currency: currencyCode })
                });

                const data = await response.json();

                if (data.success) {
                    this.currentCurrency = data.currency;
                    // Reload the page to update all prices
                    window.location.reload();
                } else {
                    alert('Failed to switch currency: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to switch currency:', error);
                alert('Failed to switch currency. Please try again.');
            } finally {
                this.loading = false;
                this.closeDropdown();
            }
        }
    }
}
</script>

