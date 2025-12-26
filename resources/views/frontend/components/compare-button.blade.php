@props(['product'])

@php
    $comparisonService = app(\App\Services\ComparisonService::class);
    $isInComparison = $comparisonService->isInComparison($product);
    $comparisonCount = $comparisonService->getComparisonCount();
    $isFull = $comparisonService->isFull();
@endphp

<button onclick="toggleComparison({{ $product->id }})" 
        id="compare-btn-{{ $product->id }}"
        data-add-text="{{ __('frontend.comparison.add_to_comparison') }}"
        data-remove-text="{{ __('frontend.comparison.remove_from_comparison') }}"
        data-max-items-message="{{ __('frontend.comparison.max_items_reached') }}"
        class="px-4 py-2 rounded text-sm transition-colors
        {{ $isInComparison 
            ? 'bg-green-600 text-white hover:bg-green-700' 
            : ($isFull 
                ? 'bg-gray-300 text-gray-600 cursor-not-allowed' 
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300') }}">
    <span id="compare-text-{{ $product->id }}">
        @if($isInComparison)
            {{ __('frontend.comparison.remove_from_comparison') }}
        @else
            {{ __('frontend.comparison.add_to_comparison') }}
        @endif
    </span>
</button>
