@props(['product', 'limit' => 5])

@include('frontend.components.frequently-bought-together', [
    'product' => $product,
    'limit' => $limit,
])

