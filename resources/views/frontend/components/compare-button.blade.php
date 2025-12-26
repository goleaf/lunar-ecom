@props(['product'])

@php
    $comparisonService = app(\App\Services\ComparisonService::class);
    $isInComparison = $comparisonService->isInComparison($product);
    $comparisonCount = $comparisonService->getComparisonCount();
    $isFull = $comparisonService->isFull();
@endphp

<button onclick="toggleComparison({{ $product->id }})" 
        id="compare-btn-{{ $product->id }}"
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

@push('scripts')
<script>
function toggleComparison(productId) {
    const btn = document.getElementById(`compare-btn-${productId}`);
    const text = document.getElementById(`compare-text-${productId}`);
    
    if (btn.classList.contains('cursor-not-allowed')) {
        alert('{{ __('frontend.comparison.max_items_reached') }}');
        return;
    }
    
    const isInComparison = btn.classList.contains('bg-green-600');
    const url = isInComparison 
        ? `/comparison/products/${productId}/remove`
        : `/comparison/products/${productId}/add`;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state
            if (isInComparison) {
                btn.classList.remove('bg-green-600', 'text-white', 'hover:bg-green-700');
                btn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                text.textContent = '{{ __('frontend.comparison.add_to_comparison') }}';
            } else {
                btn.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                btn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700');
                text.textContent = '{{ __('frontend.comparison.remove_from_comparison') }}';
            }
            
            // Update comparison count in navigation
            updateComparisonCount(data.count);
        } else {
            alert(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating comparison');
    });
}

function updateComparisonCount(count) {
    const countElement = document.getElementById('comparison-count');
    if (countElement) {
        countElement.textContent = count;
        if (count > 0) {
            countElement.classList.remove('hidden');
        } else {
            countElement.classList.add('hidden');
        }
    }
}
</script>
@endpush


