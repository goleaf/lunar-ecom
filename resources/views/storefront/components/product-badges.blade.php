@props(['product', 'limit' => null])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/product-badges.css') }}">
@endpush

@php
    $badges = app(\App\Services\ProductBadgeService::class)->getProductBadges($product, $limit);
@endphp

@if($badges->count() > 0)
    <div class="product-badges">
        @foreach($badges as $badge)
            @php
                $position = $badge->pivot->position ?? $badge->position;
                $positionClasses = match($position) {
                    'top-left' => 'top-2 left-2',
                    'top-right' => 'top-2 right-2',
                    'bottom-left' => 'bottom-2 left-2',
                    'bottom-right' => 'bottom-2 right-2',
                    'center' => 'top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2',
                    default => 'top-2 left-2',
                };
            @endphp
            <div class="product-badge-wrapper {{ $positionClasses }}">
                <span class="{{ $badge->getCssClasses() }}" 
                      style="{{ $badge->getInlineStyles() }}"
                      title="{{ $badge->description ?? '' }}">
                    @if($badge->show_icon && $badge->icon)
                        <i class="{{ $badge->icon }} mr-1"></i>
                    @endif
                    {{ $badge->getDisplayLabel() }}
                </span>
            </div>
        @endforeach
    </div>
@endif
