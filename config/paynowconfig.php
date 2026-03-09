<?php

return [
    'name' => 'Api',
    'paymenturl' => env('PAYMENT_PORTAL', 'https://payment.praz.org.zw/paymentportal/invoice'),
    'default_currency' => 3,
    'return_url' => env('PAYNOW_RETURN_URL', 'https://prazpayments.test/paynow/'),
    'mode' => env('PAYNOW_MODE', 'test'),
    'fbc' => env("FBC_URL", "https://portal.fbc.co.zw/test/praz/onlinepayments/public/pay?invt="),
    'paynowbiller' => env("PAYNOWBILLER_URL", "https://staging.topup.co.zw/pay-bill/procurement-regulatory-authority-of-zimba")
];
