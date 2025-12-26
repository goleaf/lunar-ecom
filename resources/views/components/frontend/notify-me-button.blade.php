@props(['product', 'variant' => null])

@include('frontend.components.notify-me-button', [
    'product' => $product,
    'variant' => $variant,
])

