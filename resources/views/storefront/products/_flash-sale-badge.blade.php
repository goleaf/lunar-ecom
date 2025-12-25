@php
    $schedulingService = app(\App\Services\ProductSchedulingService::class);
    $availability = $schedulingService->checkAvailability($product);
@endphp

@if($availability['flash_sale'] ?? false)
    <div class="bg-red-600 text-white px-4 py-3 rounded-lg mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold">🔥 Flash Sale!</h3>
                <p class="text-sm">Limited time offer - Don't miss out!</p>
            </div>
            <div data-flash-sale-end="{{ $availability['flash_sale_ends_at']->toIso8601String() }}"></div>
        </div>
        @if($availability['sale_percentage'])
            <p class="text-2xl font-bold mt-2">{{ $availability['sale_percentage'] }}% OFF</p>
        @endif
    </div>
@endif

