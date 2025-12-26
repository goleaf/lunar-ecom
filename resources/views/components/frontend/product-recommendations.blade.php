@props(['product', 'type' => 'related', 'title' => null, 'limit' => 10, 'location' => 'product_page'])

@include('frontend.components.product-recommendations', [
    'product' => $product,
    'type' => $type,
    'title' => $title,
    'limit' => $limit,
    'location' => $location,
])

