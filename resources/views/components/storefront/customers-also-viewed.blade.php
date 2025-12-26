@props(['product', 'limit' => 8])

@include('storefront.components.customers-also-viewed', [
    'product' => $product,
    'limit' => $limit,
])
