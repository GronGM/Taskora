<?php

return [
    'mode' => env('TASKORA_PAYMENTS_MODE', 'stub'),
    'platform_fee_percent' => (float) env('TASKORA_PLATFORM_FEE_PERCENT', 15),
    'provider' => env('PAYMENT_PROVIDER', 'stub'),
    'provider_mode' => env('PAYMENT_PROVIDER_MODE', env('TASKORA_PAYMENTS_MODE', 'stub')),
    'yookassa' => [
        'enabled' => filter_var(env('YOOKASSA_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'safe_deal_enabled' => filter_var(env('YOOKASSA_SAFE_DEAL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
    ],
];
