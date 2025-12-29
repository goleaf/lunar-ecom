<?php

return [
    'discount_stacking_strategies' => [
        'best_of' => 'Mejor opción (elige el mayor descuento)',
        'priority_first' => 'Prioridad primero',
        'cumulative' => 'Acumulativa',
        'exclusive_override' => 'Exclusiva (sustituye a las demás)',
    ],

    'discount_stacking_modes' => [
        'stackable' => 'Acumulable',
        'non_stackable' => 'No acumulable',
        'exclusive' => 'Exclusiva',
    ],

    'discount_types' => [
        'item_level' => 'Descuento por artículo',
        'cart_level' => 'Descuento de carrito',
        'shipping' => 'Descuento de envío',
        'payment_method' => 'Descuento por método de pago',
        'customer_loyalty' => 'Descuento por fidelidad',
        'coupon_based' => 'Descuento por cupón',
        'automatic_promotion' => 'Promoción automática',
    ],

    'collection_types' => [
        'standard' => 'Estándar',
        'cross_sell' => 'Venta cruzada',
        'up_sell' => 'Venta adicional',
        'related' => 'Productos relacionados',
        'bundle' => 'Paquete',
    ],
];

