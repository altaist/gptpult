<?php

namespace App\Services\Telegram;

use App\Models\User;
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
            return $this->linkTelegramAccount($chatId, $user, $token);
        }

        // Если нет токена, проверяем, связан ли уже аккаунт
        $linkedUser = User::where('telegram_id', $chatId)->first();
        
        if ($linkedUser) {
            return $this->sendLinkedUserMenu($chatId, $linkedUser);
        }

        // Если аккаунт не связан, отправляем инструкции по связке
        return $this->sendMessage($chatId, 
            "🤖 <b>Добро пожаловать в GPT Пульт!</b>\n\n" .
            "Для связки аккаунта с Telegram используйте кнопку <b>\"Связать\"</b> в личном кабинете.\n\n" .
            "После связки вы сможете:\n" .
            "• Быстро входить в ЛК одним кликом\n" .
            "• Создавать новые документы\n" .
            "• Получать уведомления о готовых заданиях\n\n" .
            "💬 Нужна помощь? Обратитесь в поддержку: @gptpult_support"
        );
    }

    /**
     * Обработать обычное сообщение
     */
    public function handleMessage(array $message): array
    {
        $chatId = $message['chat']['id'];
        
        // Проверяем, связан ли аккаунт
        $linkedUser = User::where('telegram_id', $chatId)->first();
        
        if ($linkedUser) {
            return $this->sendLinkedUserMenu($chatId, $linkedUser);
        }

        // Если аккаунт не связан, отправляем инструкции
        return $this->sendMessage($chatId, 
            "🤖 <b>Привет!</b>\n\n" .
            "Для использования бота необходимо связать ваш Telegram с аккаунтом GPT Пульт.\n\n" .
            "Войдите в личный кабинет и нажмите кнопку <b>\"Связать\"</b> в разделе Telegram.\n\n" .
            "💬 Нужна помощь? Обратитесь в поддержку: @gptpult_support"
        );
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

        // Формируем базовый URL приложения
        $baseUrl = $this->getBaseUrl();
        $isHttps = str_starts_with($baseUrl, 'https://');

        $messageText = "👋 <b>Привет, {$user->name}!</b>\n\n" .
            "🔗 Ваш аккаунт связан с Telegram\n" .
            "💰 Баланс: " . number_format($user->balance_rub ?? 0, 0, ',', ' ') . " ₽\n\n" .
            "Выберите действие:";

        // Создаем инлайн клавиатуру
        if ($isHttps) {
            // Для HTTPS используем Mini App
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🏠 Личный кабинет',
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
                            'url' => 'https://t.me/gptpult_support'
                        ]
                    ]
                ]
            ];
        } else {
            // Для HTTP используем обычные URL с автологином
            $lkUrl = "{$baseUrl}/auto-login/{$user->auth_token}?redirect=" . urlencode('/lk');
            $newDocUrl = "{$baseUrl}/auto-login/{$user->auth_token}?redirect=" . urlencode('/new');
            
            $keyboard = [
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
                            'url' => 'https://t.me/gptpult_support'
                        ]
                    ]
                ]
            ];
        }

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
            return $this->sendMessage($chatId, 
                "❌ Недействительный токен связки.\n\n" .
                "Получите новый токен в личном кабинете."
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
            'name' => $userName,
        ]);

        Log::info('User update result', ['success' => $updateResult]);

        // Проверяем auth_token для fallback
        if (!$user->auth_token) {
            Log::warning('User has no auth_token, generating new one', ['user_id' => $user->id]);
            $user->update(['auth_token' => \Illuminate\Support\Str::random(32)]);
            $user->refresh();
        }

        // Формируем URL для Mini App или fallback
        $baseUrl = $this->getBaseUrl();
        $isHttps = str_starts_with($baseUrl, 'https://');
        
        Log::info('Telegram account linked successfully', [
            'user_id' => $user->id,
            'telegram_id' => $chatId,
            'telegram_username' => $telegramUser['username'] ?? null
        ]);

        // Создаем инлайн клавиатуру
        if ($isHttps) {
            // Для HTTPS используем Mini App
            $keyboard = [
                'inline_keyboard' => [[
                    [
                        'text' => '🔗 Войти в личный кабинет',
                        'web_app' => ['url' => $baseUrl . '/lk']
                    ]
                ]]
            ];
        } else {
            // Для HTTP используем обычный URL с автологином
            $loginUrl = "{$baseUrl}/auto-login/{$user->auth_token}?redirect=" . urlencode('/lk');
            $keyboard = [
                'inline_keyboard' => [[
                    [
                        'text' => '🔗 Войти в личный кабинет',
                        'url' => $loginUrl
                    ]
                ]]
            ];
        }

        Log::info('Sending success message with keyboard', ['keyboard' => $keyboard, 'is_https' => $isHttps]);

        $messageText = "✅ <b>Аккаунт успешно связан!</b>\n\n" .
            "Добро пожаловать, {$user->name}!\n\n" .
            "Теперь вы можете войти в личный кабинет одним кликом:";

        return $this->sendMessage($chatId, $messageText, $keyboard);
    }

    /**
     * Генерировать токен связки для пользователя
     */
    public function generateLinkToken(User $user): string
    {
        $token = Str::random(32);
        
        $user->update([
            'telegram_link_token' => $token
        ]);

        return $token;
    }

    /**
     * Получить ссылку на бота с токеном
     */
    public function getBotLinkUrl(string $token): string
    {
        $botUsername = config('services.telegram.bot_username');
        return "https://t.me/{$botUsername}?start={$token}";
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