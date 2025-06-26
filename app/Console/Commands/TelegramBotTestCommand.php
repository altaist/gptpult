<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramBotTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {--timeout=300 : Timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запустить Telegram бота в тестовом режиме (long polling)';

    private TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('⛔ Тестовый режим недоступен в production!');
            return 1;
        }

        $timeout = $this->option('timeout');
        
        $this->info('🚀 Запуск Telegram бота в тестовом режиме...');
        $this->info("⏱️  Таймаут: {$timeout} секунд");
        $this->newLine();

        // Удаляем веб-хук для тестового режима
        $this->info('🗑️  Удаляем веб-хук...');
        $result = $this->telegramService->deleteWebhook();
        
        if ($result['ok']) {
            $this->info('✅ Веб-хук удален');
        } else {
            $this->warn('⚠️  Ошибка при удалении веб-хука: ' . ($result['description'] ?? 'Unknown error'));
        }

        // Получаем информацию о боте
        $this->info('🤖 Получаем информацию о боте...');
        $botInfo = $this->telegramService->getMe();
        
        if ($botInfo['ok']) {
            $bot = $botInfo['result'];
            $this->info("✅ Бот подключен: @{$bot['username']} ({$bot['first_name']})");
        } else {
            $this->error('❌ Ошибка подключения к боту!');
            return 1;
        }

        $this->newLine();
        $this->info('📱 Бот готов к работе! Отправьте ему сообщение...');
        $this->info('🛑 Нажмите Ctrl+C для остановки');
        $this->newLine();

        $offset = 0;
        $startTime = time();

        while (true) {
            // Проверяем таймаут
            if (time() - $startTime > $timeout) {
                $this->info('⏱️  Таймаут достигнут, завершаем работу...');
                break;
            }

            try {
                $updates = $this->getUpdates($offset);

                if ($updates['ok'] && !empty($updates['result'])) {
                    foreach ($updates['result'] as $update) {
                        $this->processUpdate($update);
                        $offset = $update['update_id'] + 1;
                    }
                }

                sleep(1); // Пауза между запросами

            } catch (\Exception $e) {
                $this->error('❌ Ошибка: ' . $e->getMessage());
                Log::error('Telegram test mode error', ['error' => $e->getMessage()]);
                sleep(5);
            }
        }

        $this->info('🏁 Тестовый режим завершен');
        return 0;
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
     * Обработать обновление
     */
    private function processUpdate(array $update): void
    {
        $updateId = $update['update_id'];

        // Обработка сообщений
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $from = $message['from'];

            $this->info("📨 Сообщение #{$updateId} от @{$from['username']} ({$chatId}): {$text}");

            // Обработка команды /start
            if (str_starts_with($text, '/start')) {
                $this->telegramService->handleStart($message);
            }
            // Команда /help
            elseif ($text === '/help') {
                $this->telegramService->sendMessage($chatId,
                    "🤖 <b>Доступные команды:</b>\n\n" .
                    "/start - Начать работу с ботом\n" .
                    "/help - Показать эту справку\n\n" .
                    "💬 Нужна помощь? Обратитесь в поддержку: @gptpult_support"
                );
            }
            // Обычное сообщение - показываем меню
            else {
                $this->telegramService->handleMessage($message);
            }
        }

        // Обработка callback запросов
        if (isset($update['callback_query'])) {
            $callbackQuery = $update['callback_query'];
            $data = $callbackQuery['data'] ?? '';
            $chatId = $callbackQuery['message']['chat']['id'];

            $this->info("🔘 Callback #{$updateId} от {$chatId}: {$data}");
        }
    }
} 