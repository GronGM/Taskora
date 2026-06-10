<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class BetaAccess
{
    public const SESSION_KEY = 'taskora_beta_access_granted';

    public const BANNER_TEXT = 'Тестовый режим: реальные платежи и выплаты не подключены. Используйте только тестовые данные.';

    public static function enabled(): bool
    {
        return (bool) config('beta.enabled', false);
    }

    public static function hasPassword(): bool
    {
        return filled(config('beta.password'));
    }

    public static function hasAccess(Request $request): bool
    {
        if (! self::enabled()) {
            return true;
        }

        if ($request->session()->get(self::SESSION_KEY) === true) {
            return true;
        }

        $cookieValue = $request->cookie(self::cookieName());
        $expectedValue = self::cookieValue();

        return is_string($cookieValue)
            && is_string($expectedValue)
            && hash_equals($expectedValue, $cookieValue);
    }

    public static function grant(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, true);

        Cookie::queue(cookie(
            name: self::cookieName(),
            value: self::cookieValue(),
            minutes: 60 * 24 * 7,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));
    }

    public static function cookieName(): string
    {
        return (string) config('beta.cookie_name', 'taskora_beta_access');
    }

    public static function shouldShowTestModeBanner(): bool
    {
        return self::enabled() || in_array(config('app.env'), ['local', 'staging'], true);
    }

    public static function shouldNoIndex(): bool
    {
        return self::enabled() || config('app.env') !== 'production';
    }

    public static function debugWarning(): ?string
    {
        if (! config('app.debug') || (! self::enabled() && config('app.env') !== 'staging')) {
            return null;
        }

        return 'APP_DEBUG=true. Не показывайте эту ссылку тестировщикам, пока debug-режим не выключен.';
    }

    private static function cookieValue(): ?string
    {
        if (! self::hasPassword()) {
            return null;
        }

        return hash_hmac(
            'sha256',
            'taskora-beta-access',
            config('app.key').'|'.config('beta.password'),
        );
    }
}
