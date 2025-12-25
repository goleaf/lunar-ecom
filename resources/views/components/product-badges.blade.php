@php
    use App\Helpers\BadgeHelper;
    
    $badges = BadgeHelper::toArray($product, $context ?? null);
    $wrapperClass = $attributes->get('class', 'product-badges');
    $position = $attributes->get('position', 'relative');
@endphp

@if(!empty($badges))
    <div class="{{ $wrapperClass }}" style="position: {{ $position }};">
        @foreach($badges as $badge)
            <span 
                class="product-badge {{ $badge['classes'] }} badge-position-{{ $badge['position'] }}"
                style="{{ $badge['styles'] }}"
                data-badge-id="{{ $badge['id'] }}"
                data-badge-type="{{ $badge['type'] }}"
            >
                @if($badge['show_icon'] && $badge['icon'])
                    <i class="{{ $badge['icon'] }}"></i>
                @endif
                {{ $badge['label'] }}
            </span>
        @endforeach
    </div>
@endif

