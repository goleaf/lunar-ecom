@props(['product', 'limit' => 5])

@include('storefront.components.frequently-bought-together', [
    'product' => $product,
    'limit' => $limit,
])
