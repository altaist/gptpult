<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Обработать веб-хук от Telegram
     */
    public function webhook(Request $request)
    {
        $update = $request->all();
        
        Log::info('Telegram webhook received', $update);

        try {
            // Обработка сообщений
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
            
            // Обработка callback запросов (инлайн кнопки)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
            
            return response()->json(['ok' => true]);
            
        } catch (\Exception $e) {
            Log::error('Telegram webhook processing failed', [
                'error' => $e->getMessage(),
                'update' => $update
            ]);
            
            return response()->json(['ok' => false], 500);
        }
    }

    /**
     * Обработать текстовое сообщение
     */
    private function handleMessage(array $message): void
    {
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'];

        // Команда /start
        if (str_starts_with($text, '/start')) {
            $this->telegramService->handleStart($message);
            return;
        }

        // Команда /help
        if ($text === '/help') {
            $this->telegramService->sendMessage($chatId, 
                "🤖 <b>Доступные команды:</b>\n\n" .
                "/start - Начать работу с ботом\n" .
                "/help - Показать эту справку\n\n" .
                "💬 Нужна помощь? Обратитесь в поддержку: @gptpult_help"
            );
            return;
        }

        // Обычное сообщение - показываем то же меню что и при /start
        $this->telegramService->handleMessage($message);
    }

    /**
     * Обработать callback query (нажатие на инлайн кнопку)
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'];
        
        // Здесь можно добавить обработку различных callback данных
        Log::info('Callback query received', ['data' => $data, 'chat_id' => $chatId]);
    }

    /**
     * Установить веб-хук (для продакшена)
     */
    public function setWebhook(Request $request)
    {
        $webhookUrl = config('services.telegram.webhook_url');
        
        if (!$webhookUrl) {
            return response()->json([
                'error' => 'Webhook URL not configured'
            ], 400);
        }

        $result = $this->telegramService->setWebhook($webhookUrl);

        return response()->json($result);
    }

    /**
     * Удалить веб-хук
     */
    public function deleteWebhook()
    {
        $result = $this->telegramService->deleteWebhook();
        return response()->json($result);
    }

    /**
     * Получить информацию о боте
     */
    public function getMe()
    {
        $result = $this->telegramService->getMe();
        return response()->json($result);
    }

    /**
     * Тестовый режим - получение обновлений через long polling
     * Использовать только для локальной разработки!
     */
    public function testMode()
    {
        if (app()->environment('production')) {
            return response()->json([
                'error' => 'Test mode is not available in production'
            ], 403);
        }

        // Удаляем веб-хук для тестового режима
        $this->telegramService->deleteWebhook();
        
        $offset = 0;
        
        echo "🚀 Telegram бот запущен в тестовом режиме\n";
        echo "Нажмите Ctrl+C для остановки\n\n";
        
        while (true) {
            try {
                $updates = $this->getUpdates($offset);
                
                if ($updates['ok'] && !empty($updates['result'])) {
                    foreach ($updates['result'] as $update) {
                        $this->processTestUpdate($update);
                        $offset = $update['update_id'] + 1;
                    }
                }
                
                sleep(1); // Пауза между запросами
                
            } catch (\Exception $e) {
                Log::error('Test mode error', ['error' => $e->getMessage()]);
                echo "❌ Ошибка: " . $e->getMessage() . "\n";
                sleep(5);
            }
        }
    }

    /**
     * Получить обновления через long polling
     */
    private function getUpdates(int $offset = 0): array
    {
        $botToken = config('services.telegram.bot_token');
        $url = "https://api.telegram.org/bot{$botToken}/getUpdates";
        
        $response = file_get_contents($url . '?' . http_build_query([
            'offset' => $offset,
            'timeout' => 30,
            'allowed_updates' => json_encode(['message', 'callback_query'])
        ]));
        
        return json_decode($response, true);
    }

    /**
     * Обработать обновление в тестовом режиме
     */
    private function processTestUpdate(array $update): void
    {
        echo "📨 Новое обновление: " . json_encode($update, JSON_UNESCAPED_UNICODE) . "\n";
        
        // Обработка сообщений
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }
        
        // Обработка callback запросов
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }
} 