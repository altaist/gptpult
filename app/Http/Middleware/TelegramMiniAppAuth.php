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
            'path' => $request->path(),
            'user_agent' => $request->userAgent(),
            'is_authenticated' => Auth::check(),
            'is_ajax' => $request->ajax(),
            'is_inertia' => $request->header('X-Inertia'),
            'method' => $request->method(),
            'accepts_html' => $request->accepts('text/html'),
            'is_login_path' => $request->is('login')
        ]);

        // Если пользователь уже авторизован
        if (Auth::check()) {
            Log::info('TelegramMiniAppAuth: User already authenticated', [
                'user_id' => Auth::id(),
                'current_path' => $request->path(),
                'is_login_path' => $request->is('login'),
                'should_redirect' => $request->is('login') && !$request->ajax() && !$request->header('X-Inertia')
            ]);
            
            // Если пользователь авторизован и находится на странице логина, перенаправляем его
            if ($request->is('login') && !$request->ajax() && !$request->header('X-Inertia')) {
                Log::info('TelegramMiniAppAuth: Redirecting authenticated user from login page');
                return redirect('/lk');
            }
            
            return $next($request);
        }

        // Проверяем специальные Telegram куки, если обычная авторизация не сработала
        if ($request->userAgent() && str_contains($request->userAgent(), 'Telegram')) {
            $telegramCookies = collect($request->cookies->all())
                ->filter(function ($value, $key) {
                    return str_starts_with($key, 'telegram_auth_user_');
                });
            
            if ($telegramCookies->isNotEmpty()) {
                $userId = $telegramCookies->first();
                $user = User::find($userId);
                
                if ($user) {
                    Log::info('TelegramMiniAppAuth: Restoring user from Telegram cookie', [
                        'user_id' => $user->id,
                        'cookies_found' => $telegramCookies->keys()->toArray()
                    ]);
                    
                    Auth::login($user);
                    
                    // Если это страница логина, перенаправляем
                    if ($request->is('login') && !$request->ajax() && !$request->header('X-Inertia')) {
                        return redirect('/lk');
                    }
                    
                    return $next($request);
                }
            }
        }

        // Проверяем специальные заголовки с данными из localStorage
        $telegramUserId = $request->header('X-Telegram-Auth-User-Id');
        $telegramTimestamp = $request->header('X-Telegram-Auth-Timestamp');
        
        if ($telegramUserId && $telegramTimestamp && $request->userAgent() && str_contains($request->userAgent(), 'Telegram')) {
            // Проверяем, что данные не слишком старые (не более 24 часов)
            $timestampDiff = time() - ($telegramTimestamp / 1000);
            
            if ($timestampDiff < 86400) { // 24 часа
                $user = User::find($telegramUserId);
                
                if ($user && $user->telegram_id) {
                    Log::info('TelegramMiniAppAuth: Restoring user from localStorage data', [
                        'user_id' => $user->id,
                        'timestamp_diff' => $timestampDiff
                    ]);
                    
                    Auth::login($user);
                    
                    // Если это страница логина, перенаправляем
                    if ($request->is('login') && !$request->ajax() && !$request->header('X-Inertia')) {
                        return redirect('/lk');
                    }
                    
                    return $next($request);
                }
            } else {
                Log::info('TelegramMiniAppAuth: localStorage data too old', ['timestamp_diff' => $timestampDiff]);
            }
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
                
                // Для Telegram WebApp устанавливаем специальные настройки сессий
                if ($request->userAgent() && str_contains($request->userAgent(), 'Telegram')) {
                    // Принудительно регенерируем сессию для Telegram
                    $request->session()->regenerate();
                    
                    // Устанавливаем куки с параметрами для Telegram WebApp
                    cookie()->queue(cookie(
                        'telegram_auth_user_' . $user->id,
                        $user->id,
                        config('session.lifetime'),
                        '/',
                        config('session.domain'),
                        true, // secure
                        false, // httpOnly - должно быть false для Telegram WebApp
                        false, // raw
                        'none' // sameSite - должно быть none для Telegram WebApp
                    ));
                    
                    Log::info('TelegramMiniAppAuth: Set special Telegram cookies', [
                        'user_id' => $user->id,
                        'session_id' => $request->session()->getId()
                    ]);
                }
                
                // Логируем успешную авторизацию
                Log::info('TelegramMiniAppAuth: User logged in successfully', [
                    'user_id' => $user->id,
                    'auth_check' => Auth::check(),
                    'current_url' => $request->url(),
                    'current_path' => $request->path(),
                    'is_login_path' => $request->is('login'),
                    'is_ajax' => $request->ajax(),
                    'is_inertia' => $request->header('X-Inertia'),
                    'should_redirect' => $request->is('login') && !$request->ajax() && !$request->header('X-Inertia')
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
