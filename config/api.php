<?php

return [
    'default_currency' => env('API_DEFAULT_CURRENCY', 3),

    'iveri' => [
        'IVERI_MODE' => env('IVERI_MODE', 'TEST'),
        'IVERI_TEST_MODE' => env('IVERI_TEST_MODE', true),
        'IVERI_LIVE_BASE_URL' => env('IVERI_LIVE_BASE_URL', 'https://api.iveri.com/v1'),
        'IVERI_TEST_BASE_URL' => env('IVERI_TEST_BASE_URL', 'https://test.iveri.com/v1'),
        'IVERI_LIVE_APP_ID' => env('IVERI_LIVE_APP_ID', ''),
        'IVERI_TEST_APP_ID' => env('IVERI_TEST_APP_ID', ''),
        'IVERI_URL' => env('IVERI_URL', 'https://portal.host.iveri.com/Lite/AuthoriseInfo.aspx'),
        'IVERI_TEST_PORTAL_URL' => env('IVERI_TEST_PORTAL_URL', 'https://portal.test.iveri.com'),
        'IVERI_LIVE_PORTAL_URL' => env('IVERI_LIVE_PORTAL_URL', 'https://portal.iveri.com'),
    ],
    'zimswitch' => [
        'MODE' => env('ZIMSWITCH_MODE', 'TEST'),
        'ENTITY_ID' => env('ZIMSWITCH_LIVE_ENTITY_ID', ''),
        'TEST_ENTITY_ID' => env('ZIMSWITCH_TEST_ENTITY_ID', ''),
        'TEST_AUTHORIZATION_TOKEN' => env('ZIMSWITCH_TEST_AUTHORIZATION_TOKEN', ''),
        'LIVE_AUTHORIZATION_TOKEN' => env('ZIMSWITCH_LIVE_AUTHORIZATION_TOKEN', ''),
        'PAYMENT_BRAND' => env('ZIMSWITCH_PAYMENT_BRAND', ''),
        'TEST_MODE' => env('ZIMSWITCH_TEST_MODE', true),
        'TEST_OPPWA_URL' => env('ZIMSWITCH_TEST_OPPWA_URL', 'https://test.zimswitch.co.zw/v1/oppwa'),
        'LIVE_OPPWA_URL' => env('ZIMSWITCH_LIVE_OPPWA_URL', 'https://api.zimswitch.co.zw/v1/oppwa'),
        'PAYTYPE' => env('ZIMSWITCH_PAYTYPE', ''),
        'SHOPPERURL' => env('ZIMSWITCH_SHOPPERURL', ''),
        'RESULTURL' => env('ZIMSWITCH_RESULTURL', ''),
        'TEST_BASEURL' => env('ZIMSWITCH_TEST_BASEURL', ''),
        'LIVE_BASEURL' => env('ZIMSWITCH_LIVE_BASEURL', ''),
        'TEST_CHECKOUTURL' => env('ZIMSWITCH_TEST_CHECKOUTURL', ''),
        'LIVE_CHECKOUTURL' => env('ZIMSWITCH_LIVE_CHECKOUTURL', ''),
    ]

];
