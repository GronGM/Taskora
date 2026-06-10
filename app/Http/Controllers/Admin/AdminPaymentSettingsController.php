<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AdminPaymentSettingsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/PaymentSettings/Index', [
            'warning' => 'Реальные платежи не подключены. Сейчас используется тестовая платежная архитектура и stub-режим',
            'settings' => [
                'current_provider' => config('payments.provider', 'stub'),
                'provider_mode' => config('payments.provider_mode', 'stub'),
                'taskora_payments_mode' => config('payments.mode', 'stub'),
                'payment_provider' => config('payments.provider', 'stub'),
                'payment_provider_mode' => config('payments.provider_mode', 'stub'),
                'yookassa' => [
                    'enabled' => (bool) config('payments.yookassa.enabled', false),
                    'safe_deal_enabled' => (bool) config('payments.yookassa.safe_deal_enabled', false),
                    'shop_id_present' => filled(config('payments.yookassa.shop_id')),
                    'secret_key_present' => filled(config('payments.yookassa.secret_key')),
                ],
            ],
        ]);
    }
}
