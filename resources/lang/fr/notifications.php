<?php

return [
    'stock' => [
        'subject' => ':product est de nouveau en stock !',
        'greeting' => 'Bonjour :name,',
        'line1' => 'Bonne nouvelle ! :product est de nouveau en stock et disponible à l\'achat.',
        'action' => 'Voir le produit',
        'price' => 'Prix : :price',
        'stock_available' => ':quantity unités disponibles',
        'line2' => 'Ne tardez pas : ce produit peut rapidement être en rupture de stock !',
        'salutation' => 'Cordialement,<br>L\'équipe :store_name',
        'unsubscribe' => 'Si vous ne souhaitez plus recevoir ces notifications, vous pouvez [vous désabonner ici](:url).',
    ],
    'digital' => [
        'subject' => 'Votre produit numérique est prêt à être téléchargé',
        'greeting' => 'Bonjour,',
        'line1' => 'Merci pour votre achat ! Votre produit numérique ":product" de la commande :order est maintenant disponible au téléchargement.',
        'action' => 'Télécharger',
        'download_limit' => 'Vous pouvez télécharger ce fichier jusqu\'à :limit fois.',
        'expires_at' => 'Ce lien de téléchargement expirera le :date.',
        'line2' => 'Si vous avez des questions, contactez notre équipe d\'assistance.',
        'salutation' => 'Cordialement,<br>L\'équipe :store_name',
    ],
];
