<?php

return [
    'name' => 'Sapo',

    'base_url'       => env('SAPO_BASE_URL'),
    'access_token'   => env('SAPO_ACCESS_TOKEN'),
    'webhook_secret' => env('SAPO_WEBHOOK_SECRET'),

    'sold_serials_endpoint' => env('SAPO_SOLD_SERIALS_ENDPOINT', '/admin/orders.json'),
];
