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
            
            // Проверяем, является ли это командой /start с ID пользователя
            if (str_starts_with($text, '/start ')) {
                $userId = str_replace('/start ', '', $text);
                
                // Проверяем, что это число (ID пользователя)
                if (is_numeric($userId)) {
                    // Ищем пользователя по ID
                    $user = User::find($userId);
                    
                    if ($user && !$user->telegram_id) {
                        // Связываем аккаунт с телеграмом только если еще не связан
                        $user->update([
                            'telegram_id' => $telegramUserId,
                            'telegram_username' => $telegramUsername,
                            'telegram_connected_at' => now()
                        ]);
                        
                        // Отправляем сообщение с инлайн-кнопкой
                        $this->sendSuccessMessage($telegramUserId, $user->name);
                        
                        Log::info("User {$user->id} connected Telegram account {$telegramUserId}");
                    } elseif ($user && $user->telegram_id) {
                        // Если уже связан, просто отправляем сообщение
                        $this->sendAlreadyConnectedMessage($telegramUserId, $user->name);
                    }
                }
            } elseif ($text === '/start') {
                // Если просто /start без параметров
                $this->sendWelcomeMessage($telegramUserId);
            }
            
            return response()->json(['status' => 'ok']);
            
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
    
    /**
     * Отправить сообщение об успешной связке
     */
    private function sendSuccessMessage($chatId, $userName): void
    {
        $botToken = config('telegram.bot_token');
        $appUrl = config('app.url');
        
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
                        'url' => $appUrl . '/lk'
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
        
        $text = "✅ <b>Аккаунт уже связан</b>\n\n";
        $text .= "Привет, <b>{$userName}</b>!\n";
        $text .= "Ваш аккаунт уже связан с этим Telegram.\n";
        $text .= "Вы получаете все уведомления.";
        
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
     * Отправить приветственное сообщение
     */
    private function sendWelcomeMessage($chatId): void
    {
        $appUrl = config('app.url');
        
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
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response === false) {
            Log::error('Failed to send Telegram message');
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
}
