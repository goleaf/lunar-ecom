@props(['product', 'variant' => null])

@php
    $variant = $variant ?? $product->variants->first();
    $isOutOfStock = !$variant || $variant->stock <= 0;
@endphp

@if($isOutOfStock)
    <div x-data="{
        showForm: false,
        email: '',
        name: '',
        subscribed: false,
        loading: false,
        error: null,
        async subscribe() {
            if (!this.email) {
                this.error = '{{ __('storefront.stock_notifications.email_required') }}';
                return;
            }
            
            this.loading = true;
            this.error = null;
            
            try {
                const response = await fetch('{{ route('storefront.stock-notifications.subscribe', $product) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        email: this.email,
                        name: this.name,
                        product_variant_id: {{ $variant?->id ?? 'null' }},
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.subscribed = true;
                    this.showForm = false;
                } else {
                    this.error = data.message || '{{ __('storefront.stock_notifications.subscribe_error') }}';
                }
            } catch (error) {
                this.error = '{{ __('storefront.stock_notifications.subscribe_error') }}';
            } finally {
                this.loading = false;
            }
        }
    }">
        <div x-show="!subscribed">
            <button @click="showForm = !showForm" 
                    class="w-full bg-yellow-600 text-white px-6 py-3 rounded hover:bg-yellow-700 font-medium">
                {{ __('storefront.stock_notifications.notify_me') }}
            </button>
            
            <div x-show="showForm" 
                 x-transition
                 class="mt-4 p-4 bg-gray-50 rounded-lg border">
                <h3 class="font-semibold mb-3">{{ __('storefront.stock_notifications.subscribe_title') }}</h3>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('storefront.stock_notifications.email') }}</label>
                        <input type="email" 
                               x-model="email"
                               required
                               class="w-full border rounded px-3 py-2"
                               placeholder="{{ __('storefront.stock_notifications.email_placeholder') }}">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __('storefront.stock_notifications.name') }} ({{ __('storefront.common.optional') }})</label>
                        <input type="text" 
                               x-model="name"
                               class="w-full border rounded px-3 py-2"
                               placeholder="{{ __('storefront.stock_notifications.name_placeholder') }}">
                    </div>
                    
                    <div x-show="error" class="text-red-600 text-sm" x-text="error"></div>
                    
                    <div class="flex gap-2">
                        <button @click="subscribe()" 
                                :disabled="loading"
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
                            <span x-show="!loading">{{ __('storefront.stock_notifications.subscribe') }}</span>
                            <span x-show="loading">{{ __('storefront.common.loading') }}</span>
                        </button>
                        <button @click="showForm = false" 
                                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                            {{ __('storefront.common.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div x-show="subscribed" 
             x-transition
             class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <p class="font-medium">{{ __('storefront.stock_notifications.subscribed_success') }}</p>
            <p class="text-sm mt-1">{{ __('storefront.stock_notifications.subscribed_message') }}</p>
        </div>
    </div>
@endif

