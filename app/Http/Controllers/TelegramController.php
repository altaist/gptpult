<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Получить ссылку на телеграм бот для связки аккаунта
     */
    public function getBotLink(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Имя бота (должно быть в .env)
        $botUsername = config('telegram.bot_username', 'your_bot_username');
        
        // Простая ссылка с ID пользователя
        $telegramUrl = "https://t.me/{$botUsername}?start={$user->id}";
        
        return response()->json([
            'bot_url' => $telegramUrl,
            'user_id' => $user->id
        ]);
    }
    
    /**
     * Webhook для обработки сообщений от телеграм бота
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Логируем сырые данные запроса
            Log::info('Telegram webhook called', [
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl()
            ]);
            
            $update = $request->all();
            
            Log::info('Telegram webhook received:', $update);
            
            // Проверяем, есть ли сообщение
            if (!isset($update['message'])) {
                Log::info('No message in update, skipping');
                return response()->json(['status' => 'ok']);
            }
            
            $message = $update['message'];
            $telegramUserId = $message['from']['id'];
            $telegramUsername = $message['from']['username'] ?? null;
            $text = $message['text'] ?? '';
            
            // Логируем информацию о сообщении
            Log::info('Processing message', [
                'telegram_user_id' => $telegramUserId,
                'username' => $telegramUsername,
                'text' => $text
            ]);
            
            // Обработка команды /start с параметрами (связка аккаунта)
            if (str_starts_with($text, '/start ')) {
                $this->handleStartWithParameter($telegramUserId, $telegramUsername, $text);
            }
            // Обработка команды /start без параметров (приветствие)
            elseif ($text === '/start') {
                $this->handleStartCommand($telegramUserId, $telegramUsername);
            }
            // Обработка обычных текстовых сообщений
            else {
                $this->handleTextMessage($telegramUserId, $telegramUsername, $text);
            }
            
            return response()->json(['status' => 'ok']);
            
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
    
    /**
     * Обработка команды /start с параметрами (связка аккаунта)
     */
    private function handleStartWithParameter($telegramUserId, $telegramUsername, $text): void
    {
        $userId = str_replace('/start ', '', $text);
        
        // Проверяем, что это число (ID пользователя)
        if (!is_numeric($userId)) {
            $this->sendInvalidParameterMessage($telegramUserId);
            return;
        }
        
        // Ищем пользователя по ID
        $user = User::find($userId);
        
        if (!$user) {
            // Пользователь не найден
            $this->sendUserNotFoundMessage($telegramUserId);
            return;
        }
        
        // Проверяем, не привязан ли уже этот Telegram к другому аккаунту
        $existingUser = User::where('telegram_id', $telegramUserId)->first();
        
        if ($existingUser && $existingUser->id !== $user->id) {
            // Telegram уже привязан к другому аккаунту
            $this->sendAlreadyLinkedToAnotherAccountMessage($telegramUserId, $existingUser->name);
            return;
        }
        
        if ($user->telegram_id && $user->telegram_id != $telegramUserId) {
            // У пользователя уже привязан другой Telegram
            $this->sendAccountAlreadyLinkedMessage($telegramUserId, $user->name);
            return;
        }
        
        if (!$user->telegram_id) {
            // Связываем аккаунт с телеграмом
            $user->update([
                'telegram_id' => $telegramUserId,
                'telegram_username' => $telegramUsername,
                'telegram_connected_at' => now()
            ]);
            
            // Отправляем сообщение с инлайн-кнопкой
            $this->sendSuccessMessage($telegramUserId, $user->name);
            
            Log::info("User {$user->id} connected Telegram account {$telegramUserId}");
        } else {
            // Если уже связан с тем же Telegram
            $this->sendAlreadyConnectedMessage($telegramUserId, $user->name);
        }
    }
    
    /**
     * Обработка команды /start без параметров (приветствие)
     */
    private function handleStartCommand($telegramUserId, $telegramUsername): void
    {
        // Проверяем, есть ли уже связанный аккаунт
        $existingUser = User::where('telegram_id', $telegramUserId)->first();
        
        if ($existingUser) {
            // Если аккаунт уже связан, показываем персонализированное приветствие
            $this->sendPersonalizedWelcomeMessage($telegramUserId, $existingUser);
        } else {
            // Если аккаунта нет, показываем общее приветствие
            $this->sendWelcomeMessage($telegramUserId);
        }
    }
    
    /**
     * Обработка обычных текстовых сообщений
     */
    private function handleTextMessage($telegramUserId, $telegramUsername, $text): void
    {
        // Проверяем, есть ли связанный аккаунт
        $existingUser = User::where('telegram_id', $telegramUserId)->first();
        
        if ($existingUser) {
            // Если аккаунт связан, показываем кнопки для личного кабинета
            $this->sendLinkedUserHelpMessage($telegramUserId, $existingUser);
        } else {
            // Если аккаунт не связан, предлагаем связать
            $this->sendUnlinkedUserHelpMessage($telegramUserId);
        }
    }
    
    /**
     * Отправить сообщение об успешной связке
     */
    private function sendSuccessMessage($chatId, $userName): void
    {
        $botToken = config('telegram.bot_token');
        $appUrl = config('app.url');
        
        // Для локальной разработки используем продакшн URL для кнопок
        $buttonUrl = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1') 
            ? 'https://gptpult.ru' 
            : $appUrl;
        
        if (!$botToken) {
            Log::warning('Telegram bot token not configured');
            return;
        }
        
        $text = "🎉 <b>Аккаунт успешно связан!</b>\n\n";
        $text .= "Привет, <b>{$userName}</b>!\n";
        $text .= "Теперь вы будете получать уведомления о статусе ваших документов прямо в Telegram.\n\n";
        $text .= "✅ Связка завершена\n";
        $text .= "🔔 Уведомления включены";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Вернуться в профиль',
                        'url' => $buttonUrl . '/lk'
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение если аккаунт уже связан
     */
    private function sendAlreadyConnectedMessage($chatId, $userName): void
    {
        $appUrl = config('app.url');
        
        // Для локальной разработки используем продакшн URL для кнопок
        $buttonUrl = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1') 
            ? 'https://gptpult.ru' 
            : $appUrl;
        
        $text = "✅ <b>Аккаунт уже связан</b>\n\n";
        $text .= "Привет, <b>{$userName}</b>!\n";
        $text .= "Ваш аккаунт уже связан с этим Telegram.\n";
        $text .= "Вы получаете все уведомления.";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Перейти в профиль',
                        'url' => $buttonUrl . '/lk'
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить приветственное сообщение
     */
    private function sendWelcomeMessage($chatId): void
    {
        $appUrl = config('app.url');
        
        // Для локальной разработки используем продакшн URL для кнопок
        $buttonUrl = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1') 
            ? 'https://gptpult.ru' 
            : $appUrl;
        
        $text = "👋 <b>Добро пожаловать в GPT PULT Bot!</b>\n\n";
        $text .= "Для связки аккаунта:\n";
        $text .= "1. Перейдите в ваш личный кабинет\n";
        $text .= "2. Нажмите кнопку \"Подключить Телеграм\"\n";
        $text .= "3. Вернитесь сюда и нажмите /start\n\n";
        $text .= "🔗 Связка позволит получать уведомления о готовности документов";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🌐 Открыть сайт',
                        'url' => $buttonUrl
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение если пользователь не найден
     */
    private function sendUserNotFoundMessage($chatId): void
    {
        $appUrl = 'https://gptpult.ru';
        
        $text = "❌ <b>Пользователь не найден</b>\n\n";
        $text .= "Возможно, ссылка устарела или некорректна.\n\n";
        $text .= "Для связки аккаунта:\n";
        $text .= "1. Войдите в ваш личный кабинет\n";
        $text .= "2. Нажмите кнопку \"Подключить Телеграм\"\n";
        $text .= "3. Перейдите по новой ссылке";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🌐 Открыть сайт',
                        'url' => $appUrl
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение если Telegram уже привязан к другому аккаунту
     */
    private function sendAlreadyLinkedToAnotherAccountMessage($chatId, $existingUserName): void
    {
        $appUrl = 'https://gptpult.ru';
        
        $text = "⚠️ <b>Telegram уже привязан</b>\n\n";
        $text .= "Ваш Telegram аккаунт уже связан с аккаунтом пользователя <b>{$existingUserName}</b>.\n\n";
        $text .= "Один Telegram может быть привязан только к одному аккаунту сайта.\n\n";
        $text .= "Если это ошибка, обратитесь в поддержку.";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Перейти в профиль',
                        'url' => $appUrl . '/lk'
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение если у аккаунта уже привязан другой Telegram
     */
    private function sendAccountAlreadyLinkedMessage($chatId, $userName): void
    {
        $appUrl = 'https://gptpult.ru';
        
        $text = "⚠️ <b>Аккаунт уже привязан</b>\n\n";
        $text .= "Аккаунт пользователя <b>{$userName}</b> уже связан с другим Telegram.\n\n";
        $text .= "Для смены привязки обратитесь в поддержку.";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Перейти на сайт',
                        'url' => $appUrl
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение в телеграм
     */
    private function sendTelegramMessage($chatId, $text, $keyboard = null): void
    {
        $botToken = config('telegram.bot_token');
        
        Log::info('Attempting to send Telegram message', [
            'chat_id' => $chatId,
            'text' => $text,
            'bot_token_configured' => !empty($botToken),
            'bot_token_length' => $botToken ? strlen($botToken) : 0
        ]);
        
        if (!$botToken) {
            Log::warning('Telegram bot token not configured');
            return;
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }
        
        Log::info('Sending to Telegram API', [
            'url' => $url,
            'data' => $data
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        Log::info('Telegram API response', [
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $curlError
        ]);
        
        if ($response === false) {
            Log::error('Failed to send Telegram message: ' . $curlError);
        } elseif ($httpCode !== 200) {
            Log::error('Telegram API error', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
        } else {
            Log::info('Telegram message sent successfully');
        }
    }
    
    /**
     * Проверить статус связки аккаунта
     */
    public function checkConnection(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'is_connected' => !empty($user->telegram_id),
            'telegram_username' => $user->telegram_username,
            'connected_at' => $user->telegram_connected_at
        ]);
    }
    
    /**
     * Отправить сообщение о неверном параметре
     */
    private function sendInvalidParameterMessage($chatId): void
    {
        $appUrl = config('app.url');
        
        $text = "❌ <b>Неверный параметр</b>\n\n";
        $text .= "Параметр команды /start должен быть числовым ID пользователя.\n\n";
        $text .= "Для корректной связки аккаунта:\n";
        $text .= "1. Войдите в ваш личный кабинет\n";
        $text .= "2. Нажмите кнопку \"Подключить Телеграм\"\n";
        $text .= "3. Перейдите по новой ссылке";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🌐 Открыть сайт',
                        'url' => $appUrl
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить персонализированное приветственное сообщение
     */
    private function sendPersonalizedWelcomeMessage($chatId, User $user): void
    {
        $appUrl = config('app.url');
        
        // Для локальной разработки используем продакшн URL для кнопок
        $buttonUrl = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1') 
            ? 'https://gptpult.ru' 
            : $appUrl;
        
        $text = "👋 <b>Добро пожаловать, {$user->name}!</b>\n\n";
        $text .= "Рады видеть вас в GPT PULT Bot!\n\n";
        $text .= "🎯 <b>Ваш аккаунт уже подключен</b>\n";
        $text .= "📱 Вы получаете все уведомления\n";
        $text .= "💳 Баланс: " . number_format($user->balance_rub ?? 0, 0, ',', ' ') . " ₽\n\n";
        $text .= "💡 Напишите любое сообщение для быстрого доступа к функциям";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Личный кабинет',
                        'url' => $buttonUrl . '/lk'
                    ]
                ],
                [
                    [
                        'text' => '📄 Мои документы',
                        'url' => $buttonUrl . '/documents'
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение для связанного пользователя
     */
    private function sendLinkedUserHelpMessage($chatId, User $user): void
    {
        $appUrl = config('app.url');
        $supportBotUsername = config('telegram.support_bot_username', 'support');
        
        // Для локальной разработки используем продакшн URL для кнопок
        $buttonUrl = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1') 
            ? 'https://gptpult.ru' 
            : $appUrl;
        
        $text = "🤖 <b>Чем могу помочь, {$user->name}?</b>\n\n";
        $text .= "📊 <b>Информация:</b>\n";
        $text .= "💳 Баланс: " . number_format($user->balance_rub ?? 0, 0, ',', ' ') . " ₽\n";
        $text .= "📅 В системе с: " . $user->created_at->format('d.m.Y') . "\n\n";
        $text .= "👇 Выберите нужное действие:";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Личный кабинет',
                        'url' => $buttonUrl . '/lk'
                    ],
                    [
                        'text' => '📄 Документы',
                        'url' => $buttonUrl . '/documents'
                    ]
                ],
                [
                    [
                        'text' => '📝 Создать документ',
                        'url' => $buttonUrl . '/new'
                    ],
                    [
                        'text' => '💰 Пополнить баланс',
                        'url' => $buttonUrl . '/lk#balance'
                    ]
                ],
                [
                    [
                        'text' => '💬 Поддержка',
                        'url' => "https://t.me/{$supportBotUsername}"
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение для несвязанного пользователя
     */
    private function sendUnlinkedUserHelpMessage($chatId): void
    {
        $appUrl = config('app.url');
        $supportBotUsername = config('telegram.support_bot_username', 'support');
        
        // Для локальной разработки используем продакшн URL для кнопок
        $buttonUrl = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1') 
            ? 'https://gptpult.ru' 
            : $appUrl;
        
        $text = "🤖 <b>Добро пожаловать в GPT PULT Bot!</b>\n\n";
        $text .= "Похоже, ваш аккаунт еще не связан с Telegram.\n\n";
        $text .= "🔗 <b>Для связки аккаунта:</b>\n";
        $text .= "1. Войдите в личный кабинет\n";
        $text .= "2. Нажмите \"Подключить Телеграм\"\n";
        $text .= "3. Вернитесь сюда по ссылке\n\n";
        $text .= "📋 <b>После связки вы сможете:</b>\n";
        $text .= "• Получать уведомления о документах\n";
        $text .= "• Быстро переходить в личный кабинет\n";
        $text .= "• Следить за балансом";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🌐 Открыть сайт',
                        'url' => $buttonUrl
                    ],
                    [
                        'text' => '🏠 Личный кабинет',
                        'url' => $buttonUrl . '/lk'
                    ]
                ],
                [
                    [
                        'text' => '💬 Поддержка',
                        'url' => "https://t.me/{$supportBotUsername}"
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($chatId, $text, $keyboard);
    }
}
