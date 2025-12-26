<div class="relative" x-data="{ open: false }">
    <button
        @click="open = !open"
        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
        type="button"
    >
        <span>{{ $currentCode }}</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div
        x-show="open"
        @click.away="open = false"
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
            @foreach ($currencies as $currency)
                <button
                    type="button"
                    @click="open = false"
                    wire:click="switchCurrency('{{ $currency['code'] }}')"
                    class="w-full text-left px-4 py-2 text-sm flex items-center justify-between {{ $currency['code'] === $currentCode ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                >
                    <span>
                        <span class="font-medium">{{ $currency['code'] }}</span>
                        <span class="text-gray-500 ml-2">{{ $currency['name'] }}</span>
                    </span>
                    @if ($currency['code'] === $currentCode)
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>


