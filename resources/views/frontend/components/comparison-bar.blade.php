@php
    $comparisonService = app(\App\Services\ComparisonService::class);
    $comparisonCount = $comparisonService->getComparisonCount();
@endphp

@if($comparisonCount > 0)
    <div class="fixed bottom-0 left-0 right-0 bg-blue-600 text-white p-4 shadow-lg z-50" id="comparison-bar">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="font-semibold">
                    {{ __('storefront.comparison.items_in_comparison', ['count' => $comparisonCount]) }}
                </span>
                <a href="{{ route('storefront.comparison.index') }}" 
                   class="bg-white text-blue-600 px-4 py-2 rounded hover:bg-gray-100 font-medium">
                    {{ __('storefront.comparison.view_comparison') }}
                </a>
            </div>
            <button onclick="clearComparison()" class="text-white hover:text-gray-200 underline">
                {{ __('storefront.comparison.clear_all') }}
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
    function clearComparison() {
        if (!confirm('{{ __('storefront.comparison.confirm_clear') }}')) {
            return;
        }
        
        fetch('/comparison/clear', {
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
                document.getElementById('comparison-bar')?.remove();
                updateComparisonCount(0);
            }
        });
    }
    </script>
    @endpush
@endif

