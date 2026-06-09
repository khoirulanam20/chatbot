<?php

$whatsappNumber = env('CONTACT_WHATSAPP', '6285117494221');
$whatsappMessage = env('CONTACT_WHATSAPP_MESSAGE', 'Halo, saya ingin permintaan demo AI CS Chatbot.');

return [
    'contact_email' => env('CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

    'whatsapp_number' => $whatsappNumber,

    'whatsapp_message' => $whatsappMessage,

    'whatsapp_url' => 'https://wa.me/'.$whatsappNumber.'?text='.urlencode($whatsappMessage),

    'brand' => [
        'primary'         => '#0066FF',
        'primary_active'  => '#0052CC',
        'brand_accent'    => '#3399FF',
        'accent_muted'    => '#E6F0FF',
        'ink'             => '#1F2937',
    ],
];
