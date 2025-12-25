<?php

return [
    'stock' => [
        'subject' => ':product is Back in Stock!',
        'greeting' => 'Hello :name,',
        'line1' => 'Great news! :product is now back in stock and available for purchase.',
        'action' => 'View Product',
        'price' => 'Price: :price',
        'stock_available' => ':quantity units available',
        'line2' => 'Don\'t miss out - this product may sell out quickly!',
        'salutation' => 'Best regards,<br>The :store_name Team',
        'store_name' => config('app.name', 'Store'),
        'unsubscribe' => 'If you no longer wish to receive these notifications, you can [unsubscribe here](:url).',
    ],
];

