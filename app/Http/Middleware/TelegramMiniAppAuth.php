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
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isTelegram = $this->isTelegramRequest($request);
        
        // Логируем запрос для диагностики
        Log::info('TelegramMiniAppAuth: Обработка запроса', [
            'url' => $request->url(),
            'path' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'is_telegram' => $isTelegram,
            'is_authenticated' => Auth::check(),
            'session_id' => $request->session()->getId(),
        ]);

        // Только для Telegram WebApp
        if (!$isTelegram) {
            return $next($request);
        }

        // Шаг 1: Попытка восстановления из localStorage данных
        if ($this->tryRestoreFromLocalStorage($request)) {
            Log::info('TelegramMiniAppAuth: Пользователь восстановлен из localStorage');
            
            if ($request->is('login')) {
                return $this->handleLoginPageRedirect($request, $next);
            }
            
            return $next($request);
        }

        // Шаг 2: Попытка восстановления из Telegram cookies  
        if ($this->tryRestoreFromTelegramCookies($request)) {
            Log::info('TelegramMiniAppAuth: Пользователь восстановлен из Telegram cookies');
            
            if ($request->is('login')) {
                return $this->handleLoginPageRedirect($request, $next);
            }
            
            return $next($request);
        }

        // Шаг 3: Попытка аутентификации через Telegram данные
        $telegramData = $this->extractTelegramData($request);
        
        if ($telegramData && $this->validateTelegramData($telegramData)) {
            Log::info('TelegramMiniAppAuth: Получены данные Telegram WebApp', [
                'telegram_id' => $telegramData['id'],
                'username' => $telegramData['username'] ?? 'N/A'
            ]);
            
            $user = $this->findOrCreateTelegramUser($telegramData, $request);
            
            if ($user) {
                $this->setupTelegramSession($request, $user);
                
                Log::info('TelegramMiniAppAuth: Пользователь аутентифицирован', [
                    'user_id' => $user->id,
                    'telegram_id' => $telegramData['id']
                ]);
                
                if ($request->is('login')) {
                    return $this->handleLoginPageRedirect($request, $next);
                }
            }
        }

        return $next($request);
    }

    /**
     * Проверяем, является ли запрос от Telegram
     */
    private function isTelegramRequest(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        return $userAgent && (
            str_contains($userAgent, 'Telegram') ||
            $request->hasHeader('X-Telegram-Init-Data') ||
            $request->hasHeader('X-Telegram-Auth-User-Id') ||
            $this->hasTelegramCookies($request)
        );
    }

    /**
     * Попытка восстановления из localStorage данных
     */
    private function tryRestoreFromLocalStorage(Request $request): bool
    {
        $telegramUserId = $request->header('X-Telegram-Auth-User-Id');
        $telegramTimestamp = $request->header('X-Telegram-Auth-Timestamp');
        
        if (!$telegramUserId || !$telegramTimestamp) {
            return false;
        }

        // Проверяем, что данные не старше 24 часов
        $timestampDiff = time() - ($telegramTimestamp / 1000);
        
        if ($timestampDiff >= 86400) { // 24 часа
            Log::info('TelegramMiniAppAuth: localStorage данные слишком старые', [
                'timestamp_diff' => $timestampDiff
            ]);
            return false;
        }

        $user = User::find($telegramUserId);
        
        if ($user && $user->telegram_id) {
            Log::info('TelegramMiniAppAuth: Восстанавливаем пользователя из localStorage', [
                'user_id' => $user->id,
                'timestamp_diff' => $timestampDiff
            ]);
            
            $this->setupTelegramSession($request, $user);
            return true;
        }

        return false;
    }

    /**
     * Попытка восстановления из Telegram cookies
     */
    private function tryRestoreFromTelegramCookies(Request $request): bool
    {
        $user = $this->restoreUserFromTelegramCookies($request);
        
        if ($user) {
            Log::info('TelegramMiniAppAuth: Восстанавливаем пользователя из Telegram cookies', [
                'user_id' => $user->id
            ]);
            
            $this->setupTelegramSession($request, $user);
            return true;
        }

        return false;
    }

    /**
     * Обработка перенаправления со страницы логина
     */
    private function handleLoginPageRedirect(Request $request, Closure $next): Response
    {
        if ($request->ajax() || $request->header('X-Inertia')) {
            Log::info('TelegramMiniAppAuth: Отправляем заголовок перенаправления для AJAX');
            $response = $next($request);
            $response->headers->set('X-Telegram-Redirect', '/lk');
            return $response;
        } else {
            Log::info('TelegramMiniAppAuth: Перенаправляем пользователя в ЛК');
            return redirect('/lk');
        }
    }

    /**
     * Найти или создать пользователя Telegram
     */
    private function findOrCreateTelegramUser(array $telegramData, Request $request): ?User
    {
        // Ищем существующего пользователя
        $user = User::where('telegram_id', $telegramData['id'])->first();
        
        if ($user) {
            Log::info('TelegramMiniAppAuth: Найден существующий пользователь', [
                'user_id' => $user->id,
                'telegram_id' => $telegramData['id']
            ]);
            return $user;
        }

        // Создаем нового пользователя
        Log::info('TelegramMiniAppAuth: Создаем нового пользователя', [
            'telegram_id' => $telegramData['id']
        ]);
        
        return $this->createAndLinkTelegramUser($telegramData, $request);
    }

    /**
     * Настройка сессии для Telegram WebApp
     */
    private function setupTelegramSession(Request $request, User $user): void
    {
        // Авторизуем пользователя
        Auth::login($user, true); // remember = true
        
        // Сохраняем сессию
        $request->session()->save();
        
        // Создаем специальные куки для Telegram WebApp
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
        
        cookie()->queue($cookie);
        
        Log::info('TelegramMiniAppAuth: Сессия Telegram настроена', [
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
            
            if ($user && $user->telegram_id) {
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
        // Проверяем различные источники данных
        $telegramInitData = $request->header('X-Telegram-Init-Data') 
            ?? $request->query('tgWebAppData') 
            ?? $request->input('tgWebAppData')
            ?? $request->input('init_data')
            ?? $request->input('telegram_init_data');

        // Если это POST запрос, проверяем raw content
        if (!$telegramInitData && $request->isMethod('POST') && $request->getContent()) {
            try {
                $jsonData = json_decode($request->getContent(), true);
                if ($jsonData) {
                    $telegramInitData = $jsonData['init_data'] 
                        ?? $jsonData['tgWebAppData'] 
                        ?? $jsonData['telegram_init_data'] 
                        ?? null;
                }
            } catch (\Exception $e) {
                Log::warning('TelegramMiniAppAuth: Ошибка парсинга JSON', [
                    'error' => $e->getMessage()
                ]);
            }
        }

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
            Log::warning('TelegramMiniAppAuth: Ошибка парсинга Telegram данных', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Проверить подлинность данных от Telegram
     */
    private function validateTelegramData(array $telegramData): bool
    {
        return isset($telegramData['id']) 
            && isset($telegramData['first_name']) 
            && is_numeric($telegramData['id']);
    }

    /**
     * Автоматически создать новый аккаунт и связать с Telegram
     */
    private function createAndLinkTelegramUser(array $telegramData, Request $request): ?User
    {
        try {
            // Проверяем перенос документов от временного пользователя
            $autoAuthToken = $this->extractAutoAuthToken($request);
            $tempUser = null;
            
            if ($autoAuthToken) {
                $tempUser = User::where('auth_token', $autoAuthToken)->first();
                
                if ($tempUser && (str_ends_with($tempUser->email, '@auto.user'))) {
                    Log::info('TelegramMiniAppAuth: Найден временный пользователь для переноса', [
                        'temp_user_id' => $tempUser->id,
                        'documents_count' => $tempUser->documents()->count()
                    ]);
                }
            }

            // Формируем данные пользователя
            $firstName = $telegramData['first_name'] ?? 'Пользователь';
            $lastName = $telegramData['last_name'] ?? '';
            $userName = trim($firstName . ' ' . $lastName);
            
            // Создаем нового пользователя
            $user = User::create([
                'name' => $userName,
                'email' => Str::random(10) . '@auto.user',
                'password' => Hash::make(Str::random(16)),
                'auth_token' => Str::random(32),
                'role_id' => UserRole::USER,
                'status' => 1,
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
                        'auto_created' => true,
                        'created_at' => now()->toISOString(),
                    ]
                ],
                'settings' => [],
                'statistics' => []
            ]);

            // Переносим документы от временного пользователя
            if ($tempUser && $user) {
                $transferService = new DocumentTransferService();
                $transferResult = $transferService->transferDocuments($tempUser, $user);
                
                Log::info('TelegramMiniAppAuth: Перенос документов завершен', [
                    'from_user_id' => $tempUser->id,
                    'to_user_id' => $user->id,
                    'transfer_result' => $transferResult
                ]);
            }

            Log::info('TelegramMiniAppAuth: Создан новый пользователь Telegram', [
                'user_id' => $user->id,
                'telegram_id' => $telegramData['id'],
                'has_transferred_docs' => !is_null($tempUser)
            ]);

            return $user;
            
        } catch (\Exception $e) {
            Log::error('TelegramMiniAppAuth: Ошибка создания пользователя', [
                'error' => $e->getMessage(),
                'telegram_data' => $telegramData
            ]);
            
            return null;
        }
    }

    /**
     * Извлечение токена временного пользователя
     */
    private function extractAutoAuthToken(Request $request): ?string
    {
        // Проверяем заголовки
        $token = $request->header('X-Auth-Token') 
            ?? $request->header('X-Auto-Auth-Token')
            ?? $request->cookie('auth_token');
        
        if ($token) {
            return $token;
        }
        
        // Проверяем текущего авторизованного пользователя
        if (Auth::check()) {
            $currentUser = Auth::user();
            if ($currentUser && $currentUser->auth_token && str_ends_with($currentUser->email, '@auto.user')) {
                return $currentUser->auth_token;
            }
        }
        
        return null;
    }

    /**
     * Проверяем наличие Telegram cookies
     */
    private function hasTelegramCookies(Request $request): bool
    {
        $telegramCookies = collect($request->cookies->all())
            ->filter(function ($value, $key) {
                return str_starts_with($key, 'telegram_auth_user_');
            });
        
        return $telegramCookies->isNotEmpty();
    }
}
