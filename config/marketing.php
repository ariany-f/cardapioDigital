<?php

return [
    'contact_email' => env('MARKETING_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'contato@example.com')),
    'plan_slug' => env('MARKETING_PLAN_SLUG', 'basico'),
    'plan_name' => env('MARKETING_PLAN_NAME', 'App Cardápio Completo'),
    'plan_price_monthly' => (float) env('MARKETING_PLAN_PRICE', 29.90),
];
