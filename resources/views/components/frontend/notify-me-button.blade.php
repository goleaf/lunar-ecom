@props(['product', 'variant' => null])

@include('storefront.components.notify-me-button', [
    'product' => $product,
    'variant' => $variant,
])
