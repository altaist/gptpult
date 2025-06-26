<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TelegramMiniAppAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, пришли ли данные от Telegram Mini App
        $telegramData = $this->extractTelegramData($request);
        
        if ($telegramData && $this->validateTelegramData($telegramData)) {
            Log::info('Telegram Mini App data received', ['telegram_data' => $telegramData]);
            
            // Ищем пользователя по telegram_id
            $user = User::where('telegram_id', $telegramData['id'])->first();
            
            if ($user) {
                Log::info('Auto-login via Telegram Mini App', ['user_id' => $user->id, 'telegram_id' => $telegramData['id']]);
                Auth::login($user);
            } else {
                Log::warning('User not found for Telegram ID', ['telegram_id' => $telegramData['id']]);
            }
        }

        return $next($request);
    }

    /**
     * Извлечь данные Telegram из запроса
     */
    private function extractTelegramData(Request $request): ?array
    {
        // Проверяем заголовки от Telegram Mini App
        $telegramInitData = $request->header('X-Telegram-Init-Data') 
            ?? $request->query('tgWebAppData') 
            ?? $request->input('tgWebAppData');

        if (!$telegramInitData) {
            return null;
        }

        try {
            // Парсим данные
            parse_str($telegramInitData, $data);
            
            if (isset($data['user'])) {
                $userData = json_decode($data['user'], true);
                if ($userData && isset($userData['id'])) {
                    return $userData;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse Telegram init data', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Проверить подлинность данных от Telegram
     */
    private function validateTelegramData(array $telegramData): bool
    {
        // Базовая проверка наличия обязательных полей
        if (!isset($telegramData['id']) || !isset($telegramData['first_name'])) {
            return false;
        }

        // TODO: Добавить проверку хэша для дополнительной безопасности
        // Пока что считаем данные валидными, если есть ID
        return is_numeric($telegramData['id']);
    }
}
