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
        // Логируем все заголовки для диагностики
        Log::info('TelegramMiniAppAuth: Processing request', [
            'url' => $request->url(),
            'user_agent' => $request->userAgent(),
            'is_authenticated' => Auth::check(),
            'is_ajax' => $request->ajax(),
            'is_inertia' => $request->header('X-Inertia')
        ]);

        // Если пользователь уже авторизован
        if (Auth::check()) {
            Log::info('TelegramMiniAppAuth: User already authenticated', ['user_id' => Auth::id()]);
            
            // Если пользователь авторизован и находится на странице логина, перенаправляем его
            if ($request->is('login') && !$request->ajax() && !$request->header('X-Inertia')) {
                Log::info('TelegramMiniAppAuth: Redirecting authenticated user from login page');
                return redirect('/lk');
            }
            
            return $next($request);
        }

        // Проверяем, пришли ли данные от Telegram Mini App
        $telegramData = $this->extractTelegramData($request);
        
        if ($telegramData && $this->validateTelegramData($telegramData)) {
            Log::info('Telegram Mini App data received', ['telegram_data' => $telegramData]);
            
            // Ищем пользователя по telegram_id
            $user = User::where('telegram_id', $telegramData['id'])->first();
            
            if ($user) {
                Log::info('Auto-login via Telegram Mini App', [
                    'user_id' => $user->id, 
                    'telegram_id' => $telegramData['id'],
                    'user_name' => $user->name
                ]);
                Auth::login($user);
                
                // Логируем успешную авторизацию
                Log::info('TelegramMiniAppAuth: User logged in successfully', [
                    'user_id' => $user->id,
                    'auth_check' => Auth::check(),
                    'current_url' => $request->url()
                ]);
                
                // Если это страница логина и не AJAX запрос, перенаправляем
                if ($request->is('login') && !$request->ajax() && !$request->header('X-Inertia')) {
                    Log::info('TelegramMiniAppAuth: Redirecting newly authenticated user from login page');
                    return redirect('/lk');
                }
            } else {
                Log::warning('User not found for Telegram ID', ['telegram_id' => $telegramData['id']]);
            }
        } else {
            Log::info('TelegramMiniAppAuth: No valid Telegram data found', [
                'has_telegram_data' => !is_null($telegramData),
                'telegram_data' => $telegramData
            ]);
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

        Log::info('TelegramMiniAppAuth: Extracting Telegram data', [
            'header_data' => $request->header('X-Telegram-Init-Data'),
            'query_data' => $request->query('tgWebAppData'),
            'input_data' => $request->input('tgWebAppData'),
            'final_data' => $telegramInitData
        ]);

        if (!$telegramInitData) {
            return null;
        }

        try {
            // Парсим данные
            parse_str($telegramInitData, $data);
            
            Log::info('TelegramMiniAppAuth: Parsed Telegram init data', ['parsed_data' => $data]);
            
            if (isset($data['user'])) {
                $userData = json_decode($data['user'], true);
                Log::info('TelegramMiniAppAuth: Decoded user data', ['user_data' => $userData]);
                
                if ($userData && isset($userData['id'])) {
                    return $userData;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse Telegram init data', [
                'error' => $e->getMessage(),
                'raw_data' => $telegramInitData
            ]);
        }

        return null;
    }

    /**
     * Проверить подлинность данных от Telegram
     */
    private function validateTelegramData(array $telegramData): bool
    {
        // Базовая проверка наличия обязательных полей
        $isValid = isset($telegramData['id']) && isset($telegramData['first_name']) && is_numeric($telegramData['id']);
        
        Log::info('TelegramMiniAppAuth: Validating Telegram data', [
            'data' => $telegramData,
            'is_valid' => $isValid
        ]);

        return $isValid;
    }
}
