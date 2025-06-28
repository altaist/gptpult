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
        'orders/process',
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

        // Расширенная проверка на Telegram WebApp
        $userAgent = $request->userAgent();
        $isTelegram = $userAgent && (
            str_contains($userAgent, 'Telegram') ||
            $request->hasHeader('X-Telegram-Bot-Api-Secret-Token') ||
            $request->hasHeader('X-Telegram-Auth-User-Id') ||
            $request->hasHeader('X-Telegram-Cookie-telegram_auth_user_') ||
            $request->session()->has('telegram_user_id')
        );
        
        // Логируем для отладки
        if (str_starts_with($request->path(), 'api/') || str_starts_with($request->path(), 'orders/')) {
            \Illuminate\Support\Facades\Log::info('CSRF Check', [
                'path' => $request->path(),
                'user_agent' => $userAgent,
                'is_telegram' => $isTelegram,
                'has_telegram_headers' => [
                    'secret_token' => $request->hasHeader('X-Telegram-Bot-Api-Secret-Token'),
                    'user_id' => $request->hasHeader('X-Telegram-Auth-User-Id'),
                    'cookie' => $request->hasHeader('X-Telegram-Cookie-telegram_auth_user_'),
                    'session' => $request->session()->has('telegram_user_id')
                ],
                'will_skip_csrf' => $isTelegram
            ]);
        }

        // Если это Telegram WebApp, пропускаем CSRF для API маршрутов и orders/process
        if ($isTelegram && (str_starts_with($request->path(), 'api/') || str_starts_with($request->path(), 'orders/'))) {
            return true;
        }

        return false;
    }
} 