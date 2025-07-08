<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoAuthController extends Controller
{
    /**
     * Автоматический вход по токену
     */
    public function autoLogin(Request $request)
    {
        // Проверяем, есть ли данные Telegram WebApp
        $telegramInitData = $request->header('X-Telegram-Init-Data') 
            ?? $request->input('telegram_init_data');
            
        if ($telegramInitData) {
            Log::info('AutoAuth: Processing Telegram WebApp data', [
                'init_data' => $telegramInitData,
                'user_agent' => $request->userAgent()
            ]);
            
            // Парсим данные Telegram
            try {
                parse_str($telegramInitData, $data);
                
                if (isset($data['user'])) {
                    $userData = json_decode($data['user'], true);
                    
                    if ($userData && isset($userData['id'])) {
                        Log::info('AutoAuth: Telegram user data found', ['user_data' => $userData]);
                        
                        // Ищем пользователя по telegram_id
                        $user = User::where('telegram_id', $userData['id'])->first();
                        
                        if ($user) {
                            Log::info('AutoAuth: User found, logging in', [
                                'user_id' => $user->id,
                                'telegram_id' => $userData['id']
                            ]);
                            
                            Auth::login($user, true); // remember = true
                            
                            return response()->json([
                                'success' => true,
                                'user' => $user,
                                'message' => 'Successfully logged in via Telegram WebApp'
                            ]);
                        } else {
                            Log::warning('AutoAuth: User not found for Telegram ID', [
                                'telegram_id' => $userData['id']
                            ]);
                            
                            return response()->json([
                                'success' => false,
                                'error' => 'User not found for Telegram ID: ' . $userData['id']
                            ], 404);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('AutoAuth: Failed to parse Telegram data', [
                    'error' => $e->getMessage(),
                    'raw_data' => $telegramInitData
                ]);
            }
        }
        
        // Обработка обычного токена
        $request->validate([
            'auth_token' => 'required|string'
        ]);

        $user = User::where('auth_token', $request->auth_token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        Auth::login($user);
        
        return response()->json([
            'user' => $user,
            'message' => 'Successfully logged in'
        ]);
    }

    /**
     * Автоматический вход по токену через GET запрос (для Telegram)
     */
    public function autoLoginByToken(Request $request, string $authToken)
    {
        $user = User::where('auth_token', $authToken)->first();

        if (!$user) {
            return redirect('/')->with('error', 'Недействительный токен авторизации');
        }

        Auth::login($user);

        // Получаем параметр redirect и валидируем его
        $redirectTo = $request->query('redirect', '/lk');
        
        // Список разрешенных маршрутов для безопасности
        $allowedRoutes = ['/lk', '/new', '/documents', '/profile'];
        
        // Проверяем, что redirect начинается с / и входит в разрешенные
        if (!str_starts_with($redirectTo, '/') || !$this->isAllowedRoute($redirectTo, $allowedRoutes)) {
            $redirectTo = '/lk'; // По умолчанию в ЛК
        }
        
        return redirect($redirectTo)->with('success', 'Добро пожаловать, ' . $user->name . '!');
    }

    /**
     * Проверить, разрешен ли маршрут для перенаправления
     */
    private function isAllowedRoute(string $route, array $allowedRoutes): bool
    {
        foreach ($allowedRoutes as $allowedRoute) {
            if (str_starts_with($route, $allowedRoute)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Автоматическая регистрация
     */
    public function autoRegister(Request $request)
    {
        $request->validate([
            'auth_token' => 'required|string',
            'name' => 'required|string|max:255',
            'data' => 'nullable|array'
        ]);

        // Проверяем, не существует ли уже пользователь с таким токеном
        $existingUser = User::where('auth_token', $request->auth_token)->first();
        if ($existingUser) {
            Auth::login($existingUser);
            return response()->json([
                'user' => $existingUser,
                'message' => 'User already exists'
            ]);
        }

        // Подготавливаем данные для person
        $personData = $request->data ?? [];
        if (isset($personData['telegram'])) {
            $personData['telegram'] = [
                'id' => $personData['telegram']['id'] ?? null,
                'username' => $personData['telegram']['username'] ?? null,
                'data' => $personData['telegram']['data'] ?? []
            ];
        }

        // Создаем нового пользователя
        $user = User::create([
            'name' => $request->name,
            'email' => Str::random(10) . '@auto.user',
            'password' => Hash::make(Str::random(16)),
            'auth_token' => $request->auth_token,
            'role_id' => 0, // Обычный пользователь
            'status' => 1, // Активный
            'person' => $personData,
            'settings' => [],
            'statistics' => []
        ]);

        // Сохраняем токен в базе данных
        $user->auth_token = $request->auth_token;
        $user->save();

        Auth::login($user);

        return response()->json([
            'user' => $user,
            'message' => 'Successfully registered'
        ]);
    }

    /**
     * Выход из системы
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Специальная авторизация для Telegram WebApp
     */
    public function telegramAuth(Request $request)
    {
        Log::info('TelegramAuth: Processing Telegram WebApp authentication', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->userAgent(),
            'all_headers' => $request->headers->all(),
            'has_init_data' => $request->has('init_data'),
            'has_tgWebAppData' => $request->has('tgWebAppData'),
            'has_header_init_data' => !empty($request->header('X-Telegram-Init-Data')),
            'input_data' => $request->all(),
            'raw_content' => $request->getContent()
        ]);

        // Получаем данные Telegram WebApp
        $telegramInitData = $request->input('init_data') 
            ?? $request->input('tgWebAppData')
            ?? $request->header('X-Telegram-Init-Data');
            
        Log::info('TelegramAuth: Extracted init data', [
            'init_data_source' => $telegramInitData ? 'found' : 'not_found',
            'init_data_length' => $telegramInitData ? strlen($telegramInitData) : 0,
            'init_data_preview' => $telegramInitData ? substr($telegramInitData, 0, 100) . '...' : null
        ]);
            
        if (!$telegramInitData) {
            Log::warning('TelegramAuth: No Telegram init data provided');
            return response()->json([
                'success' => false,
                'error' => 'No Telegram init data provided'
            ], 400);
        }

        try {
            // Парсим данные Telegram
            parse_str($telegramInitData, $data);
            
            Log::info('TelegramAuth: Parsed Telegram data', [
                'data_keys' => array_keys($data),
                'has_user' => isset($data['user']),
                'has_auth_date' => isset($data['auth_date'])
            ]);
            
            if (!isset($data['user'])) {
                Log::warning('TelegramAuth: No user data in init data');
                return response()->json([
                    'success' => false,
                    'error' => 'No user data in Telegram init data'
                ], 400);
            }

            $userData = json_decode($data['user'], true);
            
            if (!$userData || !isset($userData['id'])) {
                Log::warning('TelegramAuth: Invalid user data format');
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid Telegram user data format'
                ], 400);
            }

            Log::info('TelegramAuth: Telegram user data decoded', [
                'telegram_id' => $userData['id'],
                'username' => $userData['username'] ?? 'N/A',
                'first_name' => $userData['first_name'] ?? 'N/A'
            ]);
            
            // Ищем пользователя по telegram_id
            $user = User::where('telegram_id', $userData['id'])->first();
            
            if (!$user) {
                Log::info('TelegramAuth: User not found, creating new user', [
                    'telegram_id' => $userData['id']
                ]);
                
                // Создаем нового пользователя автоматически
                $user = $this->createTelegramUser($userData);
                
                if (!$user) {
                    Log::error('TelegramAuth: Failed to create new user');
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to create user account'
                    ], 500);
                }
                
                Log::info('TelegramAuth: New user created successfully', [
                    'user_id' => $user->id,
                    'telegram_id' => $userData['id']
                ]);
            }

            Log::info('TelegramAuth: User found, authenticating', [
                'user_id' => $user->id,
                'telegram_id' => $userData['id'],
                'user_email' => $user->email
            ]);
            
            // Авторизуем пользователя
            Auth::login($user, true); // remember = true для длительной сессии
            
            // Regenerate session для безопасности
            $request->session()->regenerate();
            
            // Настраиваем куки для Telegram WebApp
            $this->setupTelegramCookies($user);
            
            Log::info('TelegramAuth: User successfully authenticated', [
                'user_id' => $user->id,
                'session_id' => $request->session()->getId()
            ]);
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'auth_token' => $user->auth_token,
                    'telegram_id' => $user->telegram_id
                ],
                'message' => 'Successfully authenticated via Telegram WebApp',
                'redirect_url' => '/lk'
            ]);
            
        } catch (\Exception $e) {
            Log::error('TelegramAuth: Error processing Telegram authentication', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error processing Telegram authentication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создать нового пользователя из данных Telegram
     */
    private function createTelegramUser(array $userData): ?User
    {
        try {
            $firstName = $userData['first_name'] ?? 'Пользователь';
            $lastName = $userData['last_name'] ?? '';
            $userName = trim($firstName . ' ' . $lastName);
            
            $user = User::create([
                'name' => $userName,
                'email' => Str::random(10) . '@auto.user',
                'password' => Hash::make(Str::random(16)),
                'auth_token' => Str::random(32),
                'role_id' => \App\Enums\UserRole::USER,
                'status' => 1,
                'telegram_id' => $userData['id'],
                'telegram_username' => $userData['username'] ?? null,
                'telegram_linked_at' => now(),
                'person' => [
                    'telegram' => [
                        'id' => $userData['id'],
                        'username' => $userData['username'] ?? null,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'language_code' => $userData['language_code'] ?? null,
                        'is_premium' => $userData['is_premium'] ?? false,
                        'auto_created' => true,
                        'created_at' => now()->toISOString(),
                    ]
                ],
                'settings' => [],
                'statistics' => []
            ]);
            
            return $user;
        } catch (\Exception $e) {
            Log::error('Failed to create Telegram user', [
                'telegram_data' => $userData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Настройка куки для Telegram WebApp
     */
    private function setupTelegramCookies(User $user): void
    {
        $cookieName = 'telegram_auth_user_' . $user->id;
        $cookieValue = $user->id;
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
        
        Log::info('TelegramAuth: Cookies setup completed', [
            'user_id' => $user->id,
            'cookie_name' => $cookieName,
            'cookie_lifetime_minutes' => $telegramCookieLifetime
        ]);
    }
} 