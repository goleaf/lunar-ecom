@props(['product', 'limit' => 8])

@include('frontend.components.customers-also-viewed', [
    'product' => $product,
    'limit' => $limit,
])

