<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramRunLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:run-local {--timeout=30} {--test-mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запуск телеграм бота локально через long polling';

    private $telegramController;
    private $lastUpdateId = 0;
    private $testMode = false;

    public function __construct(TelegramController $telegramController)
    {
        parent::__construct();
        $this->telegramController = $telegramController;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botToken = config('telegram.bot_token');
        $timeout = $this->option('timeout');
        $this->testMode = $this->option('test-mode');

        if (!$botToken) {
            $this->error('❌ TELEGRAM_BOT_TOKEN не настроен в .env файле');
            return 1;
        }

        // Удаляем webhook для локального режима
        $this->removeWebhook($botToken);

        if ($this->testMode) {
            $this->info('🧪 Запуск в тестовом режиме');
            $this->runTestMode();
            return 0;
        }

        $this->info('🚀 Запуск Telegram бота локально...');
        $this->info("⏱️  Таймаут: {$timeout} секунд");
        $this->info('❌ Для остановки нажмите Ctrl+C');
        $this->newLine();

        while (true) {
            try {
                $updates = $this->getUpdates($botToken, $timeout);
                
                if (!empty($updates)) {
                    foreach ($updates as $update) {
                        $this->processUpdate($update);
                        $this->lastUpdateId = $update['update_id'] + 1;
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Ошибка: " . $e->getMessage());
                sleep(5);
            }
        }

        return 0;
    }

    /**
     * Получить обновления через long polling
     */
    private function getUpdates($botToken, $timeout)
    {
        $url = "https://api.telegram.org/bot{$botToken}/getUpdates";
        
        $data = [
            'offset' => $this->lastUpdateId,
            'timeout' => $timeout,
            'allowed_updates' => json_encode(['message', 'callback_query'])
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout + 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \Exception("Ошибка при получении обновлений от Telegram API");
        }

        $result = json_decode($response, true);
        
        if (!$result['ok']) {
            throw new \Exception("Telegram API ошибка: " . $result['description']);
        }

        return $result['result'];
    }

    /**
     * Обработать обновление
     */
    private function processUpdate($update)
    {
        $this->info("📨 Получено обновление #{$update['update_id']}");
        
        if (isset($update['message'])) {
            $message = $update['message'];
            $from = $message['from'];
            $text = $message['text'] ?? '';
            
            $this->info("👤 От: {$from['first_name']} (@{$from['username']}) - ID: {$from['id']}");
            $this->info("💬 Сообщение: {$text}");
        }

        // Создаем фейковый Request объект с данными обновления
        $request = new Request();
        $request->merge($update);
        
        // Вызываем контроллер webhook
        $response = $this->telegramController->webhook($request);
        
        if ($response->getStatusCode() === 200) {
            $this->info("✅ Обработано успешно");
        } else {
            $this->error("❌ Ошибка обработки: " . $response->getStatusCode());
        }
        
        $this->newLine();
    }

    /**
     * Удалить webhook
     */
    private function removeWebhook($botToken)
    {
        $url = "https://api.telegram.org/bot{$botToken}/deleteWebhook";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_exec($ch);
        curl_close($ch);
        
        $this->info('🗑️  Webhook удален для локального режима');
    }

    /**
     * Запуск в тестовом режиме
     */
    private function runTestMode()
    {
        $this->info('📝 Тестовый режим - симуляция различных сценариев');
        $this->newLine();

        // Тестовые данные пользователя
        $testUserId = $this->ask('Введите ID пользователя для тестирования (по умолчанию 1)', '1');
        
        $this->info('🎯 Тестируем различные сценарии взаимодействия:');
        $this->newLine();
        
        $scenarios = [
            [
                'description' => '/start без параметров (новый пользователь)',
                'text' => '/start',
                'telegram_id' => 999999999, // Не существующий в БД
                'username' => 'new_user'
            ],
            [
                'description' => '/start без параметров (существующий пользователь)',
                'text' => '/start',
                'telegram_id' => 123456789, // Может быть связан с пользователем
                'username' => 'existing_user'
            ],
            [
                'description' => "/start с корректным ID пользователя",
                'text' => "/start {$testUserId}",
                'telegram_id' => 111222333,
                'username' => 'linking_user'
            ],
            [
                'description' => '/start с некорректным параметром',
                'text' => '/start abc123',
                'telegram_id' => 444555666,
                'username' => 'invalid_user'
            ],
            [
                'description' => 'Обычное текстовое сообщение (связанный пользователь)',
                'text' => 'Привет! Как дела?',
                'telegram_id' => 123456789, // Тот же что и выше
                'username' => 'existing_user'
            ],
            [
                'description' => 'Обычное текстовое сообщение (не связанный пользователь)',
                'text' => 'Помощь',
                'telegram_id' => 999999999, // Не связанный пользователь
                'username' => 'new_user'
            ],
            [
                'description' => 'Запрос информации',
                'text' => 'Мой баланс',
                'telegram_id' => 123456789,
                'username' => 'existing_user'
            ],
            [
                'description' => 'Команда помощи',
                'text' => '/help',
                'telegram_id' => 777888999,
                'username' => 'help_user'
            ]
        ];

        foreach ($scenarios as $index => $scenario) {
            if ($this->confirm("🧪 Тестировать: {$scenario['description']}?", true)) {
                $this->info("📤 Сценарий: {$scenario['description']}");
                $this->info("💬 Сообщение: '{$scenario['text']}'");
                $this->info("👤 Telegram ID: {$scenario['telegram_id']}");
                
                $testUpdate = [
                    'update_id' => rand(1000, 9999),
                    'message' => [
                        'message_id' => rand(1, 1000),
                        'from' => [
                            'id' => $scenario['telegram_id'],
                            'is_bot' => false,
                            'first_name' => 'Тестовый',
                            'last_name' => 'Пользователь',
                            'username' => $scenario['username']
                        ],
                        'chat' => [
                            'id' => $scenario['telegram_id'],
                            'type' => 'private'
                        ],
                        'date' => time(),
                        'text' => $scenario['text']
                    ]
                ];

                $this->processUpdate($testUpdate);
                
                $this->newLine();
                if ($index < count($scenarios) - 1) {
                    $this->info("⏳ Пауза 2 секунды...");
                    sleep(2);
                }
            }
        }

        $this->info('✅ Тестирование сценариев завершено');
        $this->newLine();
        $this->info('📊 Результаты показывают как бот обрабатывает разные типы сообщений');
    }
} 