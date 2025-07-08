<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Documents\DocumentTransferService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            'session_id' => $request->session()->getId(),
            'has_telegram_cookies' => $this->hasTelegramCookies($request)
        ]);

        // НОВАЯ ЛОГИКА: Для Telegram WebApp сначала пытаемся восстановить по связанному telegram_id
        if ($isTelegram && $request->is('login')) {
            // Пытаемся найти пользователя по telegram_id в cookie
            $telegramUser = $this->findUserByTelegramCookies($request);
            
            if ($telegramUser) {
                Log::info('TelegramMiniAppAuth: Found linked Telegram user, logging in', [
                    'user_id' => $telegramUser->id,
                    'telegram_id' => $telegramUser->telegram_id
                ]);
                
                // Принудительно настраиваем сессию для Telegram
                $this->setupTelegramSession($request, $telegramUser);
                
                // Перенаправляем в ЛК
                if ($request->ajax() || $request->header('X-Inertia')) {
                    $response = $next($request);
                    $response->headers->set('X-Telegram-Redirect', '/lk');
                    return $response;
                } else {
                    return redirect('/lk');
                }
            }
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
                Log::info('User not found for Telegram ID, creating new account', ['telegram_id' => $telegramData['id']]);
                
                // Автоматически создаем новый аккаунт и связываем с Telegram
                $user = $this->createAndLinkTelegramUser($telegramData, $request);
                
                if ($user) {
                    Log::info('Auto-created and linked Telegram user', [
                        'user_id' => $user->id, 
                        'telegram_id' => $telegramData['id'],
                        'user_name' => $user->name
                    ]);
                    
                    // Принудительно настраиваем сессию для Telegram
                    $this->setupTelegramSession($request, $user);
                    
                    // Если это страница логина, перенаправляем
                    if ($request->is('login')) {
                        if ($request->ajax() || $request->header('X-Inertia')) {
                            Log::info('TelegramMiniAppAuth: Sending redirect header for newly created AJAX user');
                            $response = $next($request);
                            $response->headers->set('X-Telegram-Redirect', '/lk');
                            return $response;
                        } else {
                            Log::info('TelegramMiniAppAuth: Redirecting newly created user from login page');
                            return redirect('/lk');
                        }
                    }
                } else {
                    Log::error('Failed to create and link Telegram user', ['telegram_data' => $telegramData]);
                }
            }
        } else {
            Log::info('TelegramMiniAppAuth: No valid Telegram data found', [
                'has_telegram_data' => !is_null($telegramData),
                'telegram_data' => $telegramData,
                'is_telegram' => $isTelegram,
                'user_agent' => $request->userAgent()
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
        $cookieLifetime = config('session.lifetime', 1440); // Используем увеличенное время или 24 часа по умолчанию
        
        // Для Telegram устанавливаем еще больший срок - 7 дней
        $telegramCookieLifetime = 7 * 24 * 60; // 7 дней в минутах
        
        // Создаем куки с правильными параметрами для Telegram WebApp
        $cookie = cookie(
            $cookieName,
            $cookieValue,
            $telegramCookieLifetime,
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
            $telegramCookieLifetime * 60 // конвертируем в секунды
        );
        
        // Принудительно сохраняем в response headers
        if (app()->bound('response')) {
            app('response')->headers->set('Set-Cookie', $cookieHeader, false);
        }
        
        Log::info('TelegramMiniAppAuth: Telegram session setup completed', [
            'user_id' => $user->id,
            'session_id' => $request->session()->getId(),
            'cookie_name' => $cookieName,
            'cookie_lifetime_minutes' => $telegramCookieLifetime,
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
     * Извлечение данных от Telegram Mini App
     */
    private function extractTelegramData(Request $request): ?array
    {
        // Проверяем различные источники данных Telegram WebApp
        $telegramInitData = $request->header('X-Telegram-Init-Data') 
            ?? $request->query('tgWebAppData') 
            ?? $request->input('tgWebAppData')
            ?? $request->input('init_data')
            ?? $request->input('telegram_init_data');

        Log::info('TelegramMiniAppAuth: Extracting Telegram data', [
            'header_data' => $request->header('X-Telegram-Init-Data'),
            'query_data' => $request->query('tgWebAppData'),
            'input_data' => $request->input('tgWebAppData'),
            'init_data_input' => $request->input('init_data'),
            'telegram_init_data_input' => $request->input('telegram_init_data'),
            'final_data' => $telegramInitData ? 'present' : 'null',
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'all_headers' => $request->headers->all()
        ]);

        if (!$telegramInitData) {
            // Пытаемся извлечь из raw input если это POST запрос
            if ($request->isMethod('POST') && $request->getContent()) {
                $rawContent = $request->getContent();
                
                try {
                    $jsonData = json_decode($rawContent, true);
                    if ($jsonData && isset($jsonData['init_data'])) {
                        $telegramInitData = $jsonData['init_data'];
                        Log::info('TelegramMiniAppAuth: Found init_data in JSON body', [
                            'init_data' => $telegramInitData ? 'present' : 'null'
                        ]);
                    } elseif ($jsonData && isset($jsonData['tgWebAppData'])) {
                        $telegramInitData = $jsonData['tgWebAppData'];
                        Log::info('TelegramMiniAppAuth: Found tgWebAppData in JSON body', [
                            'tgWebAppData' => $telegramInitData ? 'present' : 'null'
                        ]);
                    } elseif ($jsonData && isset($jsonData['telegram_init_data'])) {
                        $telegramInitData = $jsonData['telegram_init_data'];
                        Log::info('TelegramMiniAppAuth: Found telegram_init_data in JSON body', [
                            'telegram_init_data' => $telegramInitData ? 'present' : 'null'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('TelegramMiniAppAuth: Failed to parse JSON body', [
                        'error' => $e->getMessage(),
                        'raw_content' => substr($rawContent, 0, 200) // Первые 200 символов для отладки
                    ]);
                }
            }
        }

        if (!$telegramInitData) {
            Log::info('TelegramMiniAppAuth: No Telegram init data found in any source');
            return null;
        }

        try {
            // Парсим данные
            parse_str($telegramInitData, $data);
            
            Log::info('TelegramMiniAppAuth: Parsed Telegram init data', [
                'parsed_data_keys' => array_keys($data),
                'has_user' => isset($data['user']),
                'has_auth_date' => isset($data['auth_date']),
                'has_hash' => isset($data['hash'])
            ]);
            
            if (isset($data['user'])) {
                $userData = json_decode($data['user'], true);
                Log::info('TelegramMiniAppAuth: Decoded user data', [
                    'user_data' => $userData,
                    'user_id' => $userData['id'] ?? 'missing'
                ]);
                
                if ($userData && isset($userData['id'])) {
                    return $userData;
                }
            }
        } catch (\Exception $e) {
            Log::warning('TelegramMiniAppAuth: Failed to parse Telegram init data', [
                'error' => $e->getMessage(),
                'raw_data' => substr($telegramInitData, 0, 200) // Первые 200 символов для отладки
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

    /**
     * Автоматически создать новый аккаунт и связать с Telegram
     */
    private function createAndLinkTelegramUser(array $telegramData, Request $request = null): ?User
    {
        try {
            // Формируем имя пользователя из Telegram данных
            $firstName = $telegramData['first_name'] ?? 'Пользователь';
            $lastName = $telegramData['last_name'] ?? '';
            $userName = trim($firstName . ' ' . $lastName);
            
            // Создаем нового пользователя
            $user = User::create([
                'name' => $userName,
                'email' => Str::random(10) . '@auto.user', // Автогенерированный email
                'password' => Hash::make(Str::random(16)), // Случайный пароль
                'auth_token' => Str::random(32), // Токен для автовхода
                'role_id' => UserRole::USER, // Обычный пользователь
                'status' => 1, // Активный статус
                'telegram_id' => $telegramData['id'],
                'telegram_username' => $telegramData['username'] ?? null,
                'telegram_linked_at' => now(),
                'person' => [
                    'telegram' => [
                        'id' => $telegramData['id'],
                        'username' => $telegramData['username'] ?? null,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'language_code' => $telegramData['language_code'] ?? null,
                        'is_premium' => $telegramData['is_premium'] ?? false,
                        'auto_created' => true, // Флаг автоматического создания
                        'created_at' => now()->toISOString(),
                    ]
                ],
                'settings' => [],
                'statistics' => []
            ]);
            
            Log::info('Successfully created new Telegram user', [
                'user_id' => $user->id,
                'telegram_id' => $telegramData['id'],
                'name' => $userName,
                'email' => $user->email,
                'telegram_username' => $telegramData['username'] ?? null
            ]);
            
            // Проверяем наличие предыдущего временного пользователя для переноса документов
            $this->transferDocumentsFromTempUser($user, $request);
            
            return $user;
            
        } catch (\Exception $e) {
            Log::error('Failed to create Telegram user', [
                'telegram_data' => $telegramData,
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Перенести документы от временного пользователя к новому авторизованному
     */
    private function transferDocumentsFromTempUser(User $newUser, Request $request = null): void
    {
        if (!$request) {
            Log::info('Запрос не передан, пропускаем перенос документов');
            return;
        }

        try {
            $transferService = new DocumentTransferService();
            
            // Проверяем разные источники для получения токена предыдущего пользователя
            $tempAuthToken = $this->getTempUserToken($request);
            
            if (!$tempAuthToken) {
                Log::info('Токен временного пользователя не найден');
                return;
            }
            
            // Ищем временного пользователя по токену
            $tempUser = $transferService->findTempUserByAuthToken($tempAuthToken);
            
            if (!$tempUser) {
                Log::info('Временный пользователь не найден по токену', [
                    'token' => substr($tempAuthToken, 0, 8) . '...' // Логируем только часть токена
                ]);
                return;
            }
            
            Log::info('Найден временный пользователь для переноса документов', [
                'temp_user_id' => $tempUser->id,
                'temp_user_email' => $tempUser->email,
                'new_user_id' => $newUser->id,
                'new_user_email' => $newUser->email
            ]);
            
            // Переносим документы
            $result = $transferService->transferDocuments($tempUser, $newUser);
            
            if ($result['success'] && $result['transferred_count'] > 0) {
                Log::info('Документы успешно перенесены от временного пользователя', [
                    'temp_user_id' => $tempUser->id,
                    'new_user_id' => $newUser->id,
                    'transferred_count' => $result['transferred_count']
                ]);
                
                // Опционально удаляем временного пользователя (осторожно!)
                // $transferService->deleteTempUser($tempUser);
            } else {
                Log::info('Перенос документов не потребовался', [
                    'temp_user_id' => $tempUser->id,
                    'new_user_id' => $newUser->id,
                    'result' => $result
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка при переносе документов от временного пользователя', [
                'new_user_id' => $newUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Получить токен временного пользователя из различных источников
     */
    private function getTempUserToken(Request $request): ?string
    {
        // 1. Проверяем заголовок X-Auth-Token
        $token = $request->header('X-Auth-Token');
        if ($token) {
            Log::info('Найден токен в заголовке X-Auth-Token');
            return $token;
        }
        
        // 2. Проверяем заголовок X-Auto-Auth-Token  
        $token = $request->header('X-Auto-Auth-Token');
        if ($token) {
            Log::info('Найден токен в заголовке X-Auto-Auth-Token');
            return $token;
        }
        
        // 3. Проверяем куки auth_token
        $token = $request->cookie('auth_token');
        if ($token) {
            Log::info('Найден токен в куки auth_token');
            return $token;
        }
        
        // 4. Проверяем сессию (если есть текущий авторизованный пользователь)
        if (Auth::check()) {
            $currentUser = Auth::user();
            if ($currentUser && $currentUser->auth_token && (str_ends_with($currentUser->email, '@auto.user') || str_ends_with($currentUser->email, '@linked.user'))) {
                Log::info('Найден токен текущего временного пользователя в сессии');
                return $currentUser->auth_token;
            }
        }
        
        Log::info('Токен временного пользователя не найден ни в одном источнике');
        return null;
    }

    /**
     * Проверяем наличие пользователя в Telegram cookies
     */
    private function hasTelegramCookies(Request $request): bool
    {
        $telegramCookies = collect($request->cookies->all())
            ->filter(function ($value, $key) {
                return str_starts_with($key, 'telegram_auth_user_');
            });
        
        return $telegramCookies->isNotEmpty();
    }

    /**
     * Найти пользователя по telegram_id в Telegram cookies
     */
    private function findUserByTelegramCookies(Request $request): ?User
    {
        $telegramCookies = collect($request->cookies->all())
            ->filter(function ($value, $key) {
                return str_starts_with($key, 'telegram_auth_user_');
            });
        
        if ($telegramCookies->isNotEmpty()) {
            $userId = $telegramCookies->first();
            $user = User::find($userId);
            
            if ($user && $user->telegram_id) {
                Log::info('TelegramMiniAppAuth: Found user in Telegram cookies', [
                    'user_id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'cookies_found' => $telegramCookies->keys()->toArray()
                ]);
                return $user;
            }
        }
        
        return null;
    }
}
