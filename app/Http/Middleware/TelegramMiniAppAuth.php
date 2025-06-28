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
        $isTelegram = $request->userAgent() && str_contains($request->userAgent(), 'Telegram');
        
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
            'is_login_path' => $request->is('login'),
            'is_telegram' => $isTelegram,
            'session_id' => $request->session()->getId()
        ]);

        // Если пользователь уже авторизован
        if (Auth::check()) {
            Log::info('TelegramMiniAppAuth: User already authenticated', [
                'user_id' => Auth::id(),
                'current_path' => $request->path(),
                'is_login_path' => $request->is('login'),
                'should_redirect' => $request->is('login')
            ]);
            
            // Если пользователь авторизован и находится на странице логина, перенаправляем его
            if ($request->is('login')) {
                if ($request->ajax() || $request->header('X-Inertia')) {
                    Log::info('TelegramMiniAppAuth: Sending redirect header for authenticated AJAX user');
                    $response = $next($request);
                    $response->headers->set('X-Telegram-Redirect', '/lk');
                    return $response;
                } else {
                    Log::info('TelegramMiniAppAuth: Redirecting authenticated user from login page');
                    return redirect('/lk');
                }
            }
            
            return $next($request);
        }

        // Для Telegram пытаемся восстановить авторизацию из специальных куки
        if ($isTelegram) {
            $user = $this->restoreUserFromTelegramCookies($request);
            
            if ($user) {
                Log::info('TelegramMiniAppAuth: Restoring user from Telegram cookie', [
                    'user_id' => $user->id,
                    'session_id_before' => $request->session()->getId()
                ]);
                
                // Принудительно настраиваем сессию для Telegram
                $this->setupTelegramSession($request, $user);
                
                Log::info('TelegramMiniAppAuth: User restored from cookie', [
                    'user_id' => $user->id,
                    'auth_check' => Auth::check(),
                    'session_id_after' => $request->session()->getId()
                ]);
                
                // Если это страница логина, перенаправляем
                if ($request->is('login')) {
                    if ($request->ajax() || $request->header('X-Inertia')) {
                        Log::info('TelegramMiniAppAuth: Sending redirect header for restored AJAX user');
                        $response = $next($request);
                        $response->headers->set('X-Telegram-Redirect', '/lk');
                        return $response;
                    } else {
                        Log::info('TelegramMiniAppAuth: Redirecting restored user from login page');
                        return redirect('/lk');
                    }
                }
                
                return $next($request);
            }
        }

        // Проверяем специальные заголовки с данными из localStorage
        $telegramUserId = $request->header('X-Telegram-Auth-User-Id');
        $telegramTimestamp = $request->header('X-Telegram-Auth-Timestamp');
        
        if ($telegramUserId && $telegramTimestamp && $isTelegram) {
            // Проверяем, что данные не слишком старые (не более 24 часов)
            $timestampDiff = time() - ($telegramTimestamp / 1000);
            
            if ($timestampDiff < 86400) { // 24 часа
                $user = User::find($telegramUserId);
                
                if ($user && $user->telegram_id) {
                    Log::info('TelegramMiniAppAuth: Restoring user from localStorage data', [
                        'user_id' => $user->id,
                        'timestamp_diff' => $timestampDiff
                    ]);
                    
                    // Принудительно настраиваем сессию для Telegram
                    $this->setupTelegramSession($request, $user);
                    
                    // Если это страница логина, перенаправляем
                    if ($request->is('login')) {
                        if ($request->ajax() || $request->header('X-Inertia')) {
                            $response = $next($request);
                            $response->headers->set('X-Telegram-Redirect', '/lk');
                            return $response;
                        } else {
                            return redirect('/lk');
                        }
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
                
                // Принудительно настраиваем сессию для Telegram
                $this->setupTelegramSession($request, $user);
                
                // Логируем успешную авторизацию
                Log::info('TelegramMiniAppAuth: User logged in successfully', [
                    'user_id' => $user->id,
                    'auth_check' => Auth::check(),
                    'current_url' => $request->url(),
                    'current_path' => $request->path(),
                    'is_login_path' => $request->is('login'),
                    'is_ajax' => $request->ajax(),
                    'is_inertia' => $request->header('X-Inertia')
                ]);
                
                // Если это страница логина, перенаправляем
                if ($request->is('login')) {
                    if ($request->ajax() || $request->header('X-Inertia')) {
                        Log::info('TelegramMiniAppAuth: Sending redirect header for newly authenticated AJAX user');
                        $response = $next($request);
                        $response->headers->set('X-Telegram-Redirect', '/lk');
                        return $response;
                    } else {
                        Log::info('TelegramMiniAppAuth: Redirecting newly authenticated user from login page');
                        return redirect('/lk');
                    }
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
     * Настройка сессии для Telegram WebApp
     */
    private function setupTelegramSession(Request $request, User $user): void
    {
        // Принудительно авторизуем пользователя
        Auth::login($user, true); // remember = true
        
        // Принудительно сохраняем сессию
        $request->session()->save();
        
        // Создаем специальные куки для Telegram WebApp
        $cookieName = 'telegram_auth_user_' . $user->id;
        $cookieValue = $user->id;
        $cookieLifetime = config('session.lifetime', 120);
        
        // Создаем куки с правильными параметрами для Telegram WebApp
        $cookie = cookie(
            $cookieName,
            $cookieValue,
            $cookieLifetime,
            '/', // path
            null, // domain
            true, // secure - обязательно true для HTTPS
            false, // httpOnly - false для доступа из JS в Telegram
            false, // raw
            'none' // sameSite - none для работы в iframe Telegram
        );
        
        // Добавляем куки к ответу
        cookie()->queue($cookie);
        
        // Также устанавливаем через заголовки
        $cookieHeader = sprintf(
            '%s=%s; Max-Age=%d; Path=/; Secure; SameSite=None',
            $cookieName,
            $cookieValue,
            $cookieLifetime * 60
        );
        
        // Принудительно сохраняем в response headers
        if (app()->bound('response')) {
            app('response')->headers->set('Set-Cookie', $cookieHeader, false);
        }
        
        Log::info('TelegramMiniAppAuth: Telegram session setup completed', [
            'user_id' => $user->id,
            'session_id' => $request->session()->getId(),
            'cookie_name' => $cookieName,
            'auth_check' => Auth::check()
        ]);
    }

    /**
     * Восстановить пользователя из Telegram куки
     */
    private function restoreUserFromTelegramCookies(Request $request): ?User
    {
        $telegramCookies = collect($request->cookies->all())
            ->filter(function ($value, $key) {
                return str_starts_with($key, 'telegram_auth_user_');
            });
        
        if ($telegramCookies->isNotEmpty()) {
            $userId = $telegramCookies->first();
            $user = User::find($userId);
            
            if ($user) {
                Log::info('TelegramMiniAppAuth: Found user in Telegram cookies', [
                    'user_id' => $user->id,
                    'cookies_found' => $telegramCookies->keys()->toArray()
                ]);
                return $user;
            }
        }
        
        return null;
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
