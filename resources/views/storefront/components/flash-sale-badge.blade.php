@props(['product'])

@php
    $flashSale = \App\Models\ProductSchedule::where('product_id', $product->id)
        ->where('type', 'flash_sale')
        ->where('is_active', true)
        ->where('scheduled_at', '<=', now())
        ->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })
        ->first();
@endphp

@if($flashSale)
    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
        <span class="mr-2">⚡</span>
        Flash Sale
        @if($flashSale->expires_at)
            <span class="ml-2 text-xs">Ends {{ $flashSale->expires_at->diffForHumans() }}</span>
        @endif
    </div>
@endif

