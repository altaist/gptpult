<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'telegram/webhook',
        'payment/yookassa/webhook',
        'api/payment/yookassa/create/*',
        'api/payment/status/*',
        'api/user/transitions',
    ];

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        // Проверяем стандартные исключения
        if (parent::inExceptArray($request)) {
            return true;
        }

        // Если это Telegram WebApp, пропускаем CSRF для API маршрутов
        $isTelegram = $request->userAgent() && str_contains($request->userAgent(), 'Telegram');
        if ($isTelegram && str_starts_with($request->path(), 'api/')) {
            return true;
        }

        return false;
    }
} 