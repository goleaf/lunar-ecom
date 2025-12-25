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
        'unsubscribe' => 'If you no longer wish to receive these notifications, you can [unsubscribe here](:url).',
    ],
    'digital' => [
        'subject' => 'Your Digital Product is Ready for Download',
        'greeting' => 'Hello,',
        'line1' => 'Thank you for your purchase! Your digital product ":product" from order :order is now available for download.',
        'action' => 'Download Now',
        'download_limit' => 'You can download this file up to :limit times.',
        'expires_at' => 'This download link will expire on :date.',
        'line2' => 'If you have any questions, please contact our support team.',
        'salutation' => 'Best regards,<br>The :store_name Team',
    ],
];
