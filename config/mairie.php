<?php

return [
    // Identité de la collectivité (utilisée dans les en-têtes, reçus et actes).
    'name' => env('MAIRIE_NAME', 'Mairie de Moundou'),
    'short_name' => env('MAIRIE_SHORT_NAME', 'Mairie360'),
    'country' => env('MAIRIE_COUNTRY', 'Tchad'),
    'currency' => 'FCFA',

    // Lignes d'en-tête des documents officiels (à ajuster avec la mairie).
    'document_header' => array_filter(explode('|', env('MAIRIE_DOC_HEADER', ''))),

    // Gabarit d'acte de naissance : tant que la mairie n'a pas validé le rendu (textes arabes inclus),
    // l'impression porte un filigrane « ÉPREUVE ». Passer ACT_TEMPLATE_VALIDATED=true une fois validé.
    'act_template_validated' => (bool) env('ACT_TEMPLATE_VALIDATED', false),

    // Reçus hors ligne : taille d'un lot de numéros attribué à un collecteur.
    'receipt_block_size' => (int) env('RECEIPT_BLOCK_SIZE', 50),
    'receipt_block_low_watermark' => 15,

    // Modes de paiement autorisés.
    'payment_methods' => [
        'espece' => 'Espèces',
        'mobile_money' => 'Mobile money',
        'cheque' => 'Chèque',
        'virement' => 'Virement',
    ],
];
