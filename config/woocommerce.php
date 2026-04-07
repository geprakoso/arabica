<?php

return [
    'store_url' => env('WOOCOMMERCE_STORE_URL'),

    'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),

    'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),

    'version' => 'wc/v3',

    'timeout' => 30,

    'verify_ssl' => true,

    'query_string_auth' => true,
];
