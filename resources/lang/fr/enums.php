<?php

return [
    'discount_stacking_strategies' => [
        'best_of' => 'Meilleur choix (remise maximale)',
        'priority_first' => 'Priorité d’abord',
        'cumulative' => 'Cumulatif',
        'exclusive_override' => 'Exclusif (remplace les autres)',
    ],

    'discount_stacking_modes' => [
        'stackable' => 'Cumulable',
        'non_stackable' => 'Non cumulable',
        'exclusive' => 'Exclusif',
    ],

    'discount_types' => [
        'item_level' => 'Remise par article',
        'cart_level' => 'Remise sur le panier',
        'shipping' => 'Remise sur la livraison',
        'payment_method' => 'Remise selon le moyen de paiement',
        'customer_loyalty' => 'Remise fidélité',
        'coupon_based' => 'Remise via coupon',
        'automatic_promotion' => 'Promotion automatique',
    ],

    'collection_types' => [
        'standard' => 'Standard',
        'cross_sell' => 'Vente croisée',
        'up_sell' => 'Montée en gamme',
        'related' => 'Produits associés',
        'bundle' => 'Pack',
    ],
];

