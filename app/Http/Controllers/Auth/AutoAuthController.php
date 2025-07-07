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
        Log::info('TelegramAuth: Received request', [
            'has_init_data' => $request->has('init_data'),
            'user_agent' => $request->userAgent(),
            'method' => $request->method()
        ]);

        // Получаем init_data из тела запроса
        $initData = $request->input('init_data');
        
        if (!$initData) {
            Log::warning('TelegramAuth: No init_data provided');
            return response()->json([
                'success' => false,
                'error' => 'No Telegram init data provided'
            ], 400);
        }

        try {
            // Парсим данные Telegram
            parse_str($initData, $data);
            
            Log::info('TelegramAuth: Parsed init data', [
                'has_user' => isset($data['user']),
                'data_keys' => array_keys($data)
            ]);
            
            if (!isset($data['user'])) {
                Log::warning('TelegramAuth: No user data in init_data');
                return response()->json([
                    'success' => false,
                    'error' => 'No user data in init_data'
                ], 400);
            }

            $userData = json_decode($data['user'], true);
            
            if (!$userData || !isset($userData['id'])) {
                Log::warning('TelegramAuth: Invalid user data', [
                    'user_data' => $userData
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid user data'
                ], 400);
            }

            Log::info('TelegramAuth: Processing user', [
                'telegram_id' => $userData['id'],
                'first_name' => $userData['first_name'] ?? null,
                'username' => $userData['username'] ?? null
            ]);

            // Ищем пользователя по telegram_id
            $user = User::where('telegram_id', $userData['id'])->first();
            
            if ($user) {
                Log::info('TelegramAuth: User found, logging in', [
                    'user_id' => $user->id,
                    'telegram_id' => $userData['id']
                ]);
                
                // Авторизуем пользователя
                Auth::login($user, true); // remember = true
                
                // Создаем специальные куки для Telegram WebApp (аналогично middleware)
                $this->setupTelegramCookies($user);
                
                return response()->json([
                    'success' => true,
                    'user' => $user->only(['id', 'name', 'email']),
                    'message' => 'Successfully logged in via Telegram WebApp'
                ]);
            } else {
                Log::info('TelegramAuth: User not found, creating new account', [
                    'telegram_id' => $userData['id']
                ]);
                
                // Создаем нового пользователя
                $user = $this->createTelegramUser($userData);
                
                if ($user) {
                    Log::info('TelegramAuth: New user created and logged in', [
                        'user_id' => $user->id,
                        'telegram_id' => $userData['id']
                    ]);
                    
                    // Авторизуем пользователя
                    Auth::login($user, true);
                    
                    // Создаем специальные куки для Telegram WebApp
                    $this->setupTelegramCookies($user);
                    
                    return response()->json([
                        'success' => true,
                        'user' => $user->only(['id', 'name', 'email']),
                        'message' => 'New account created and logged in via Telegram WebApp'
                    ]);
                } else {
                    Log::error('TelegramAuth: Failed to create new user');
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to create new account'
                    ], 500);
                }
            }
        } catch (\Exception $e) {
            Log::error('TelegramAuth: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
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
        
        Log::info('TelegramAuth: Cookies setup completed', [
            'user_id' => $user->id,
            'cookie_name' => $cookieName
        ]);
    }
} 