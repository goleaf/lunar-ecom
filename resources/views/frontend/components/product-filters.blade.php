{{-- Product Attribute Filters Component --}}
<div
    id="product-filters-container"
    data-category-id="{{ $category->id ?? '' }}"
    data-product-type-id="{{ $productTypeId ?? '' }}"
    data-api-base-url="{{ url('/api/filters') }}"
></div>
<div id="products-container"></div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/product-filters.css') }}">
@endpush

@push('scripts')
@endpush

