<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\Documents\DocumentTransferService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
    private string $botToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Отправить сообщение пользователю
     */
    public function sendMessage(int $chatId, string $text, array $keyboard = null): array
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        Log::info('Sending Telegram message', [
            'chat_id' => $chatId,
            'text_length' => strlen($text),
            'has_keyboard' => !empty($keyboard)
        ]);

        $result = $this->makeRequest('sendMessage', $data);
        
        Log::info('Telegram message send result', [
            'success' => $result['ok'] ?? false,
            'message_id' => $result['result']['message_id'] ?? null,
            'error' => $result['description'] ?? null
        ]);

        return $result;
    }

    /**
     * Установить веб-хук
     */
    public function setWebhook(string $url): array
    {
        return $this->makeRequest('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query']
        ]);
    }

    /**
     * Удалить веб-хук
     */
    public function deleteWebhook(): array
    {
        return $this->makeRequest('deleteWebhook');
    }

    /**
     * Получить информацию о веб-хуке
     */
    public function getWebhookInfo(): array
    {
        return $this->makeRequest('getWebhookInfo');
    }

    /**
     * Получить информацию о боте
     */
    public function getMe(): array
    {
        return $this->makeRequest('getMe');
    }

    /**
     * Обработать сообщение /start с токеном
     */
    public function handleStart(array $message): array
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user = $message['from'];

        // Извлекаем токен из команды /start
        if (preg_match('/^\/start\s+(.+)$/', $text, $matches)) {
            $token = trim($matches[1]);
            
            Log::info('Received /start with token', [
                'chat_id' => $chatId,
                'token' => substr($token, 0, 10) . '...',
                'is_auth_token' => str_starts_with($token, 'auth_')
            ]);
            
            // ВАЖНО: Токены авторизации имеют приоритет!
            // Проверяем тип токена и обрабатываем соответственно
            if (str_starts_with($token, 'auth_')) {
                Log::info('Processing auth token with priority', ['chat_id' => $chatId]);
                return $this->handleTelegramAuth($chatId, $user, $token);
            } else {
                Log::info('Processing link token', ['chat_id' => $chatId]);
                return $this->linkTelegramAccount($chatId, $user, $token);
            }
        }

        // Если нет токена, проверяем, связан ли уже аккаунт
        $linkedUser = User::where('telegram_id', $chatId)->first();
        
        if ($linkedUser) {
            Log::info('No token provided, showing menu for linked user', [
                'chat_id' => $chatId,
                'user_id' => $linkedUser->id
            ]);
            return $this->sendLinkedUserMenu($chatId, $linkedUser);
        }

        // НОВАЯ ЛОГИКА: Если аккаунт не связан, автоматически создаем нового пользователя
        Log::info('No token and no linked user, creating new account automatically', ['chat_id' => $chatId]);
        return $this->createAutoAccount($chatId, $user);
    }

    /**
     * Обработать обычное сообщение
     */
    public function handleMessage(array $message): array
    {
        $chatId = $message['chat']['id'];
        $user = $message['from'];
        
        // Проверяем, связан ли аккаунт
        $linkedUser = User::where('telegram_id', $chatId)->first();
        
        if ($linkedUser) {
            return $this->sendLinkedUserMenu($chatId, $linkedUser);
        }

        // НОВАЯ ЛОГИКА: Если аккаунт не связан, автоматически создаем нового пользователя
        Log::info('No linked user found, creating new account automatically', ['chat_id' => $chatId]);
        return $this->createAutoAccount($chatId, $user);
    }

    /**
     * Отправить меню для связанного пользователя
     */
    private function sendLinkedUserMenu(int $chatId, User $user): array
    {
        // Проверяем auth_token для fallback
        if (!$user->auth_token) {
            $user->update(['auth_token' => \Illuminate\Support\Str::random(32)]);
            $user->refresh();
        }

        $messageText = "👋 <b>Привет, {$user->name}!</b>\n\n" .
            "🔗 Ваш аккаунт связан с Telegram\n" .
            "💰 Баланс: " . number_format($user->balance_rub ?? 0, 0, ',', ' ') . " ₽\n\n" .
            "Выберите действие:";

        // Используем новую клавиатуру с двумя кнопками
        $keyboard = $this->createLoginKeyboard($user);

        return $this->sendMessage($chatId, $messageText, $keyboard);
    }

    /**
     * Получить базовый URL приложения
     */
    private function getBaseUrl(): string
    {
        // В тестовом режиме используем настроенный тестовый URL
        if (app()->environment('local')) {
            return config('services.telegram.test_app_url');
        }
        
        return config('app.url');
    }

    /**
     * Связать аккаунт Telegram с пользователем
     */
    public function linkTelegramAccount(int $chatId, array $telegramUser, string $token): array
    {
        Log::info('Starting Telegram account linking', [
            'chat_id' => $chatId,
            'token' => $token,
            'telegram_user' => $telegramUser
        ]);

        // Находим пользователя по токену связки
        $user = User::where('telegram_link_token', $token)->first();

        if (!$user) {
            Log::warning('Invalid link token', ['token' => $token]);

            // Для недействительного токена показываем только ссылку на поддержку
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '💬 Поддержка',
                            'url' => 'https://t.me/gptpult_help'
                        ]
                    ]
                ]
            ];

            return $this->sendMessage($chatId, 
                "❌ Недействительный токен связки.\n\n" .
                "Получите новый токен в личном кабинете.\n\n" .
                "💡 Если у вас еще нет аккаунта, создайте его на сайте gptpult.ru",
                $keyboard
            );
            
        }

        // Проверяем срок действия токена
        if (!$this->isTokenValid($user)) {
            Log::warning('Expired link token', ['user_id' => $user->id, 'token' => $token]);

            // Используем новую клавиатуру с двумя кнопками
            $keyboard = $this->createLoginKeyboard($user);

            return $this->sendMessage($chatId, 
                "❌ Токен связки истёк.\n\n" .
                "Получите новый токен в личном кабинете.",
                $keyboard
            );
        }

        Log::info('User found by token', ['user_id' => $user->id, 'user_name' => $user->name]);

        // Проверяем, не связан ли уже этот Telegram аккаунт
        $existingUser = User::where('telegram_id', $chatId)->first();
        if ($existingUser && $existingUser->id !== $user->id) {
            Log::warning('Telegram account already linked to another user', [
                'chat_id' => $chatId,
                'existing_user_id' => $existingUser->id,
                'current_user_id' => $user->id
            ]);
            return $this->sendMessage($chatId, 
                "❌ Этот Telegram аккаунт уже связан с другим пользователем."
            );
        }

        // Проверяем, не связан ли уже этот пользователь с другим Telegram
        if ($user->telegram_id && $user->telegram_id !== $chatId) {
            Log::warning('User already linked to another Telegram', [
                'user_id' => $user->id,
                'existing_telegram_id' => $user->telegram_id,
                'new_chat_id' => $chatId
            ]);
            return $this->sendMessage($chatId, 
                "❌ Ваш аккаунт уже связан с другим Telegram.\n\n" .
                "Один аккаунт = один Telegram!"
            );
        }

        // Формируем имя пользователя
        $userName = $telegramUser['first_name'] . (($telegramUser['last_name'] ?? '') ? ' ' . $telegramUser['last_name'] : '');
        
        Log::info('Updating user with Telegram data', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'username' => $telegramUser['username'] ?? null,
            'new_name' => $userName
        ]);

        // Связываем аккаунты
        $updateResult = $user->update([
            'telegram_id' => $chatId,
            'telegram_username' => $telegramUser['username'] ?? null,
            'telegram_linked_at' => now(),
            'telegram_link_token' => null, // Очищаем токен
            'telegram_token_expires_at' => null, // Очищаем время истечения
            'name' => $userName,
        ]);

        Log::info('User update result', ['success' => $updateResult]);

        // Проверяем auth_token для fallback
        if (!$user->auth_token) {
            Log::warning('User has no auth_token, generating new one', ['user_id' => $user->id]);
            $user->update(['auth_token' => \Illuminate\Support\Str::random(32)]);
            $user->refresh();
        }

        Log::info('Telegram account linked successfully', [
            'user_id' => $user->id,
            'telegram_id' => $chatId,
            'telegram_username' => $telegramUser['username'] ?? null
        ]);

        // Используем новую клавиатуру с двумя кнопками
        $keyboard = $this->createLoginKeyboard($user);

        Log::info('Sending success message with keyboard', ['keyboard' => $keyboard]);

        $messageText = "✅ <b>Аккаунт успешно связан!</b>\n\n" .
            "Добро пожаловать, {$user->name}!\n\n" .
            "Теперь вы можете войти в личный кабинет:";

        return $this->sendMessage($chatId, $messageText, $keyboard);
    }

    /**
     * Генерировать токен связки для пользователя
     */
    public function generateLinkToken(User $user): string
    {
        $token = Str::random(32);
        $expiresAt = now()->addMinutes(10); // Токен действует 10 минут
        
        $user->update([
            'telegram_link_token' => $token,
            'telegram_token_expires_at' => $expiresAt
        ]);

        Log::info('Generated link token', [
            'user_id' => $user->id,
            'expires_at' => $expiresAt->toISOString()
        ]);

        return $token;
    }

    /**
     * Генерировать токен авторизации для пользователя
     */
    public function generateAuthToken(User $user): string
    {
        $token = 'auth_' . Str::random(32);
        $expiresAt = now()->addMinutes(10); // Токен действует 10 минут
        
        $user->update([
            'telegram_link_token' => $token,
            'telegram_token_expires_at' => $expiresAt
        ]);

        Log::info('Generated auth token', [
            'user_id' => $user->id,
            'expires_at' => $expiresAt->toISOString()
        ]);

        return $token;
    }

    /**
     * Проверить действительность токена
     */
    private function isTokenValid(User $user): bool
    {
        if (!$user->telegram_link_token || !$user->telegram_token_expires_at) {
            Log::info('Token invalid: missing token or expiration', [
                'user_id' => $user->id,
                'has_token' => !empty($user->telegram_link_token),
                'has_expiration' => !empty($user->telegram_token_expires_at)
            ]);
            return false;
        }

        $isValid = now()->isBefore($user->telegram_token_expires_at);
        
        if (!$isValid) {
            Log::info('Token expired, clearing', [
                'user_id' => $user->id,
                'expired_at' => $user->telegram_token_expires_at->toISOString(),
                'current_time' => now()->toISOString()
            ]);
            
            // Очищаем истекший токен
            $user->update([
                'telegram_link_token' => null,
                'telegram_token_expires_at' => null
            ]);
        } else {
            Log::info('Token is valid', [
                'user_id' => $user->id,
                'expires_at' => $user->telegram_token_expires_at->toISOString(),
                'current_time' => now()->toISOString()
            ]);
        }

        return $isValid;
    }

    /**
     * Получить URL бота для связки
     */
    public function getBotLinkUrl(string $token): string
    {
        $botUsername = config('services.telegram.bot_username');
        return "https://t.me/{$botUsername}?start={$token}";
    }

    /**
     * Получить URL бота для авторизации
     */
    public function getBotAuthUrl(string $token): string
    {
        $botUsername = config('services.telegram.bot_username');
        return "https://t.me/{$botUsername}?start={$token}";
    }

    /**
     * Обработать авторизацию через Telegram
     */
    private function handleTelegramAuth(int $chatId, array $telegramUser, string $token): array
    {
        Log::info('Starting Telegram authorization', [
            'chat_id' => $chatId,
            'token' => substr($token, 0, 15) . '...',
            'telegram_user' => $telegramUser
        ]);

        // СНАЧАЛА находим пользователя по токену авторизации
        $userWithToken = User::where('telegram_link_token', $token)->first();

        if (!$userWithToken) {
            Log::warning('Invalid auth token', ['token' => substr($token, 0, 15) . '...']);

            // Для недействительного токена не показываем клавиатуру, только ссылку на поддержку
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '💬 Поддержка',
                            'url' => 'https://t.me/gptpult_help'
                        ]
                    ]
                ]
            ];

            return $this->sendMessage($chatId, 
                "❌ Недействительный токен авторизации.\n\n" .
                "Получите новый токен в личном кабинете.\n\n" .
                "💡 Если у вас еще нет аккаунта, создайте его на сайте gptpult.ru",
                $keyboard
            );
        }

        // Проверяем срок действия токена
        if (!$this->isTokenValid($userWithToken)) {
            Log::warning('Expired auth token', ['user_id' => $userWithToken->id, 'token' => substr($token, 0, 15) . '...']);
            
            // Для истекшего токена показываем клавиатуру с логином через auth_token
            $keyboard = $this->createLoginKeyboard($userWithToken);

            return $this->sendMessage($chatId, 
                "❌ Токен авторизации истёк.\n\n" .
                "Получите новый токен в личном кабинете.",
                $keyboard
            );
        }

        Log::info('User found by auth token', [
            'user_id' => $userWithToken->id, 
            'user_name' => $userWithToken->name,
            'user_email' => $userWithToken->email
        ]);

        // ПОТОМ проверяем, есть ли уже пользователь с таким Telegram ID
        $existingUserWithTelegram = User::where('telegram_id', $chatId)->first();
        
        // Попытка переноса документов от временного пользователя
        $documentsTransferred = 0;
        $transferService = new DocumentTransferService();
        $finalUser = $userWithToken; // По умолчанию используем пользователя с токеном
        
        if ($existingUserWithTelegram) {
            Log::info('Found existing user with this Telegram ID', [
                'existing_user_id' => $existingUserWithTelegram->id,
                'existing_user_email' => $existingUserWithTelegram->email,
                'token_user_id' => $userWithToken->id,
                'token_user_email' => $userWithToken->email
            ]);
            
            if ($existingUserWithTelegram->id === $userWithToken->id) {
                // Это тот же пользователь, просто обновляем токен
                Log::info('Same user, just clearing token');
                $userWithToken->update([
                    'telegram_link_token' => null,
                    'telegram_token_expires_at' => null
                ]);
                $finalUser = $userWithToken;
            } else {
                // Разные пользователи! Нужно перенести документы
                if ($transferService->isTempUser($userWithToken)) {
                    Log::info('Token user is temporary, transferring documents to existing Telegram user', [
                        'from_temp_user' => $userWithToken->id,
                        'to_permanent_user' => $existingUserWithTelegram->id
                    ]);
                    
                    // Переносим документы от временного к постоянному
                    $transferResult = $transferService->transferDocuments($userWithToken, $existingUserWithTelegram);
                    $documentsTransferred = $transferResult['transferred_count'];
                    
                    // Очищаем токен у временного пользователя
                    $userWithToken->update([
                        'telegram_link_token' => null,
                        'telegram_token_expires_at' => null
                    ]);
                    
                    // Используем постоянного пользователя
                    $finalUser = $existingUserWithTelegram;
                } else {
                    Log::warning('Token user is permanent but Telegram already linked to another user', [
                        'token_user' => $userWithToken->id,
                        'telegram_user' => $existingUserWithTelegram->id
                    ]);
                    
                    return $this->sendMessage($chatId, 
                        "❌ Этот Telegram уже связан с другим аккаунтом.\n\n" .
                        "Для связки с новым аккаунтом сначала отвяжите Telegram от текущего аккаунта."
                    );
                }
            }
        } else {
            // Нет пользователя с таким Telegram ID
            Log::info('No existing user with this Telegram ID, linking to token user');
            
            // Проверяем, это временный пользователь или нет
            if ($transferService->isTempUser($userWithToken)) {
                // Превращаем временного пользователя в постоянного
                $firstName = $telegramUser['first_name'];
                $lastName = $telegramUser['last_name'] ?? '';
                $userName = trim($firstName . ' ' . $lastName);
                
                // Обновляем email с автогенерированного на более постоянный
                $userWithToken->update([
                    'name' => $userName,
                    'email' => Str::random(10) . '@linked.user', // Меняем с @auto.user на @linked.user
                    'telegram_id' => $chatId,
                    'telegram_username' => $telegramUser['username'] ?? null,
                    'telegram_linked_at' => now(),
                    'telegram_link_token' => null, // Очищаем токен
                    'telegram_token_expires_at' => null, // Очищаем время истечения
                ]);
                
                Log::info('Converted temporary user to permanent', [
                    'user_id' => $userWithToken->id,
                    'old_email_type' => '@auto.user',
                    'new_email_type' => '@linked.user'
                ]);
            } else {
                // Это постоянный пользователь, просто связываем с Telegram
                $firstName = $telegramUser['first_name'];
                $lastName = $telegramUser['last_name'] ?? '';
                $userName = trim($firstName . ' ' . $lastName);
                
                $userWithToken->update([
                    'telegram_id' => $chatId,
                    'telegram_username' => $telegramUser['username'] ?? null,
                    'telegram_linked_at' => now(),
                    'telegram_link_token' => null, // Очищаем токен
                    'telegram_token_expires_at' => null, // Очищаем время истечения
                    'name' => $userName,
                ]);
                
                Log::info('Linked permanent user with Telegram', [
                    'user_id' => $userWithToken->id,
                    'telegram_id' => $chatId
                ]);
            }
            
            $finalUser = $userWithToken;
        }

        // Проверяем auth_token для fallback
        if (!$finalUser->auth_token) {
            Log::warning('User has no auth_token, generating new one', ['user_id' => $finalUser->id]);
            $finalUser->update(['auth_token' => Str::random(32)]);
            $finalUser->refresh();
        }

        Log::info('Telegram authorization completed successfully', [
            'final_user_id' => $finalUser->id,
            'telegram_id' => $chatId,
            'telegram_username' => $telegramUser['username'] ?? null,
            'documents_transferred' => $documentsTransferred
        ]);

        // Используем новую клавиатуру с двумя кнопками
        $keyboard = $this->createLoginKeyboard($finalUser);

        Log::info('Sending auth success message with keyboard', ['keyboard' => $keyboard]);

        // Формируем сообщение в зависимости от того, были ли перенесены документы
        $messageText = "✅ <b>Авторизация через Telegram успешна!</b>\n\n" .
            "Добро пожаловать, {$finalUser->name}!\n\n";
        
        $messageText .= "Ваш аккаунт теперь связан с Telegram. Войдите в личный кабинет:";

        return $this->sendMessage($chatId, $messageText, $keyboard);
    }

    /**
     * Создать клавиатуру с двумя кнопками входа (браузер + веб-приложение)
     */
    private function createLoginKeyboard(User $user): array
    {
        // Проверяем auth_token для fallback
        if (!$user->auth_token) {
            $user->update(['auth_token' => Str::random(32)]);
            $user->refresh();
        }

        // Формируем базовый URL приложения
        $baseUrl = $this->getBaseUrl();
        $isHttps = str_starts_with($baseUrl, 'https://');

        if ($isHttps) {
            // Для HTTPS используем обе кнопки
            return [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🌐 Войти в браузере',
                            'url' => "{$baseUrl}/auto-login/{$user->auth_token}?redirect=" . urlencode('/lk')
                        ]
                    ],
                    [
                        [
                            'text' => '📱 Открыть веб-приложение',
                            'web_app' => ['url' => $baseUrl . '/lk']
                        ]
                    ],
                    [
                        [
                            'text' => '📝 Создать документ',
                            'web_app' => ['url' => $baseUrl . '/new']
                        ]
                    ],
                    [
                        [
                            'text' => '💬 Поддержка',
                            'url' => 'https://t.me/gptpult_help'
                        ]
                    ]
                ]
            ];
        } else {
            // Для HTTP только браузерные ссылки
            $lkUrl = "{$baseUrl}/auto-login/{$user->auth_token}?redirect=" . urlencode('/lk');
            $newDocUrl = "{$baseUrl}/auto-login/{$user->auth_token}?redirect=" . urlencode('/new');
            
            return [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🏠 Личный кабинет',
                            'url' => $lkUrl
                        ]
                    ],
                    [
                        [
                            'text' => '📝 Создать документ',
                            'url' => $newDocUrl
                        ]
                    ],
                    [
                        [
                            'text' => '💬 Поддержка',
                            'url' => 'https://t.me/gptpult_help'
                        ]
                    ]
                ]
            ];
        }
    }

    /**
     * Автоматически создать аккаунт для нового пользователя Telegram
     */
    private function createAutoAccount(int $chatId, array $telegramUser): array
    {
        Log::info('Creating auto account for Telegram user', [
            'chat_id' => $chatId,
            'telegram_user' => $telegramUser
        ]);

        // Формируем имя пользователя из Telegram данных
        $userName = $telegramUser['first_name'] . (($telegramUser['last_name'] ?? '') ? ' ' . $telegramUser['last_name'] : '');
        
        // Генерируем уникальный email для автосозданного пользователя
        $email = 'telegram_' . $chatId . '@auto.user';
        
        // Проверяем, не существует ли уже пользователь с таким email (дополнительная защита)
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            Log::warning('User with auto email already exists', [
                'email' => $email,
                'existing_user_id' => $existingUser->id
            ]);
            
            // Если пользователь существует, но не связан с Telegram, связываем его
            if (!$existingUser->telegram_id) {
                $existingUser->update([
                    'telegram_id' => $chatId,
                    'telegram_username' => $telegramUser['username'] ?? null,
                    'telegram_linked_at' => now(),
                ]);
                
                return $this->sendLinkedUserMenu($chatId, $existingUser);
            }
            
            // Если уже связан, просто показываем меню
            return $this->sendLinkedUserMenu($chatId, $existingUser);
        }

        try {
            // Создаем нового пользователя
            $newUser = User::create([
                'name' => $userName,
                'email' => $email,
                'password' => bcrypt(\Illuminate\Support\Str::random(32)), // Случайный пароль
                'auth_token' => \Illuminate\Support\Str::random(32),
                'role_id' => \App\Enums\UserRole::USER,
                'status' => 1, // Активный статус
                'balance_rub' => 0,
                'telegram_id' => $chatId,
                'telegram_username' => $telegramUser['username'] ?? null,
                'telegram_linked_at' => now(),
                'privacy_consent' => true,
                'privacy_consent_at' => now(),
            ]);

            Log::info('Auto account created successfully', [
                'user_id' => $newUser->id,
                'telegram_id' => $chatId,
                'email' => $email,
                'name' => $userName
            ]);

            // Отправляем приветственное сообщение
            $welcomeMessage = "🎉 <b>Добро пожаловать в GPT Пульт!</b>\n\n" .
                "👋 Привет, {$userName}!\n\n" .
                "✅ Ваш аккаунт автоматически создан и связан с Telegram\n" .
                "💰 Стартовый баланс: 0 ₽\n\n" .
                "🚀 Теперь вы можете:\n" .
                "• Войти в личный кабинет\n" .
                "• Создавать документы\n" .
                "• Получать уведомления\n\n" .
                "Выберите действие:";

            $keyboard = $this->createLoginKeyboard($newUser);

            return $this->sendMessage($chatId, $welcomeMessage, $keyboard);

        } catch (\Exception $e) {
            Log::error('Failed to create auto account', [
                'chat_id' => $chatId,
                'telegram_user' => $telegramUser,
                'error' => $e->getMessage()
            ]);

            // В случае ошибки отправляем сообщение с инструкциями по ручной связке
            return $this->sendMessage($chatId, 
                "❌ <b>Ошибка при создании аккаунта</b>\n\n" .
                "Попробуйте позже или создайте аккаунт вручную через сайт и используйте кнопку связки.\n\n" .
                "💬 Нужна помощь? Обратитесь в поддержку: @gptpult_help"
            );
        }
    }

    /**
     * Выполнить запрос к Telegram API
     */
    private function makeRequest(string $method, array $data = []): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/{$method}", $data);
            
            $result = $response->json();
            
            if (!$response->successful() || !$result['ok']) {
                Log::error('Telegram API error', [
                    'method' => $method,
                    'data' => $data,
                    'response' => $result
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Telegram API request failed', [
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
} 