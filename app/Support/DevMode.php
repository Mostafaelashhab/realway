<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * وضع المطوّر: بيفتح بيانات الكراسي. من غيره التطبيق ميذكرش الكراسي أصلًا.
 * المفتاح في .env (EGTRAIN_DEV_KEY)، والمتصفح بيفتكره في كوكي مشفّر.
 */
class DevMode
{
    public const COOKIE = 'egtrain_dev';

    /** الميزة متسطّبة أصلًا؟ (مفتاح موجود في الإعدادات) */
    public static function configured(): bool
    {
        return is_string(config('egtrain.dev_key')) && config('egtrain.dev_key') !== '';
    }

    public static function matches(?string $key): bool
    {
        return self::configured()
            && is_string($key)
            && hash_equals((string) config('egtrain.dev_key'), $key);
    }

    /** الزائر ده فاتح وضع المطوّر؟ */
    public static function active(?Request $request = null): bool
    {
        $request ??= request();

        return self::matches($request?->cookie(self::COOKIE));
    }
}
