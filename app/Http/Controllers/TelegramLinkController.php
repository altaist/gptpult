<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramLinkController extends Controller
{
    private TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Генерировать ссылку для связи с Telegram
     */
    public function generateLink(Request $request)
    {
        $user = Auth::user();

        // Проверяем, не связан ли уже аккаунт
        if ($user->telegram_id) {
            return response()->json([
                'error' => 'Аккаунт уже связан с Telegram',
                'telegram_username' => $user->telegram_username,
                'linked_at' => $user->telegram_linked_at
            ], 400);
        }

        // Генерируем токен связки
        $token = $this->telegramService->generateLinkToken($user);
        
        // Получаем ссылку на бота
        $botUrl = $this->telegramService->getBotLinkUrl($token);

        return response()->json([
            'bot_url' => $botUrl,
            'token' => $token,
            'expires_in' => 600 // токен действует 10 минут
        ]);
    }

    /**
     * Генерировать ссылку для авторизации через Telegram
     */
    public function generateAuthLink(Request $request)
    {
        $user = Auth::user();

        // Проверяем, не связан ли уже аккаунт
        if ($user->telegram_id) {
            return response()->json([
                'error' => 'Аккаунт уже связан с Telegram',
                'telegram_username' => $user->telegram_username,
                'linked_at' => $user->telegram_linked_at
            ], 400);
        }

        // Генерируем токен авторизации (отличается от токена связки)
        $token = $this->telegramService->generateAuthToken($user);
        
        // Получаем ссылку на бота с параметром авторизации
        $botUrl = $this->telegramService->getBotAuthUrl($token);

        return response()->json([
            'bot_url' => $botUrl,
            'token' => $token,
            'expires_in' => 600 // токен действует 10 минут
        ]);
    }

    /**
     * Отвязать Telegram аккаунт
     */
    public function unlink(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->telegram_id) {
            return response()->json([
                'error' => 'Аккаунт не связан с Telegram'
            ], 400);
        }

        $user->update([
            'telegram_id' => null,
            'telegram_username' => null,
            'telegram_link_token' => null,
            'telegram_token_expires_at' => null,
            'telegram_linked_at' => null,
        ]);

        return response()->json([
            'message' => 'Telegram аккаунт успешно отвязан'
        ]);
    }

    /**
     * Получить статус связи с Telegram
     */
    public function status(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'is_linked' => !empty($user->telegram_id),
            'telegram_username' => $user->telegram_username,
            'linked_at' => $user->telegram_linked_at
        ]);
    }
} 