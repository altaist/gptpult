<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramDebug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:debug {--check-users} {--test-api} {--show-config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отладка и диагностика телеграм бота';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Диагностика Telegram бота');
        $this->newLine();

        if ($this->option('check-users')) {
            $this->checkUsers();
        } elseif ($this->option('test-api')) {
            $this->testTelegramApi();
        } elseif ($this->option('show-config')) {
            $this->showConfig();
        } else {
            $this->runFullDiagnostic();
        }

        return 0;
    }

    /**
     * Полная диагностика
     */
    private function runFullDiagnostic()
    {
        $this->info('📋 Запуск полной диагностики...');
        $this->newLine();

        // 1. Проверка конфигурации
        $this->checkConfiguration();
        
        // 2. Проверка API
        $this->checkTelegramConnection();
        
        // 3. Проверка пользователей
        $this->checkTelegramUsers();
        
        // 4. Проверка логов
        $this->checkLogs();

        $this->newLine();
        $this->info('✅ Диагностика завершена');
    }

    /**
     * Проверка конфигурации
     */
    private function checkConfiguration()
    {
        $this->info('🔧 Проверка конфигурации:');

        $token = config('telegram.bot_token');
        $username = config('telegram.bot_username');
        $webhookUrl = config('telegram.webhook_url');

        if (!$token) {
            $this->error('   ❌ TELEGRAM_BOT_TOKEN не настроен');
        } else {
            $this->info('   ✅ TELEGRAM_BOT_TOKEN: ' . substr($token, 0, 10) . '...');
        }

        if (!$username) {
            $this->warn('   ⚠️  TELEGRAM_BOT_USERNAME не настроен');
        } else {
            $this->info('   ✅ TELEGRAM_BOT_USERNAME: @' . $username);
        }

        if (!$webhookUrl) {
            $this->warn('   ⚠️  TELEGRAM_WEBHOOK_URL не настроен');
        } else {
            $this->info('   ✅ TELEGRAM_WEBHOOK_URL: ' . $webhookUrl);
        }

        $this->newLine();
    }

    /**
     * Проверка подключения к Telegram API
     */
    private function checkTelegramConnection()
    {
        $this->info('🌐 Проверка подключения к Telegram API:');

        $token = config('telegram.bot_token');
        if (!$token) {
            $this->error('   ❌ Невозможно проверить - токен не настроен');
            $this->newLine();
            return;
        }

        try {
            $url = "https://api.telegram.org/bot{$token}/getMe";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $this->error("   ❌ Ошибка cURL: {$curlError}");
                return;
            }

            if ($httpCode !== 200) {
                $this->error("   ❌ HTTP ошибка: {$httpCode}");
                return;
            }

            $result = json_decode($response, true);
            
            if (!$result || !$result['ok']) {
                $this->error('   ❌ Telegram API вернул ошибку');
                if (isset($result['description'])) {
                    $this->error("      Описание: {$result['description']}");
                }
                return;
            }

            $bot = $result['result'];
            $this->info('   ✅ Подключение успешно');
            $this->info("      Имя бота: {$bot['first_name']}");
            $this->info("      Username: @{$bot['username']}");
            $this->info("      ID: {$bot['id']}");

        } catch (\Exception $e) {
            $this->error("   ❌ Исключение: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Проверка пользователей с Telegram
     */
    private function checkTelegramUsers()
    {
        $this->info('👥 Проверка пользователей с Telegram:');

        $users = User::whereNotNull('telegram_id')->get();
        $totalUsers = User::count();

        $this->info("   📊 Всего пользователей: {$totalUsers}");
        $this->info("   📱 С привязанным Telegram: {$users->count()}");

        if ($users->count() > 0) {
            $this->info('   📋 Последние 5 подключений:');
            
            $recentUsers = $users->sortByDesc('telegram_connected_at')->take(5);
            
            foreach ($recentUsers as $user) {
                $connectedAt = $user->telegram_connected_at->format('d.m.Y H:i');
                $this->info("      • {$user->name} (@{$user->telegram_username}) - {$connectedAt}");
            }
        } else {
            $this->warn('   ⚠️  Нет пользователей с привязанным Telegram');
        }

        $this->newLine();
    }

    /**
     * Проверка логов
     */
    private function checkLogs()
    {
        $this->info('📄 Проверка логов:');

        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            $this->warn('   ⚠️  Файл логов не найден');
            $this->newLine();
            return;
        }

        $this->info("   📁 Файл логов: {$logFile}");
        $this->info("   📏 Размер: " . $this->formatBytes(filesize($logFile)));

        // Ищем последние записи, связанные с Telegram
        $command = "tail -n 100 '{$logFile}' | grep -i telegram | tail -n 5";
        $telegramLogs = shell_exec($command);

        if ($telegramLogs) {
            $this->info('   📝 Последние записи о Telegram:');
            $lines = explode("\n", trim($telegramLogs));
            foreach ($lines as $line) {
                if (trim($line)) {
                    $this->info('      ' . trim($line));
                }
            }
        } else {
            $this->info('   📝 Нет недавних записей о Telegram в логах');
        }

        $this->newLine();
    }

    /**
     * Детальная проверка пользователей
     */
    private function checkUsers()
    {
        $this->info('👥 Детальная проверка пользователей с Telegram:');
        $this->newLine();

        $users = User::whereNotNull('telegram_id')->get();

        if ($users->isEmpty()) {
            $this->warn('❌ Нет пользователей с привязанным Telegram');
            return;
        }

        foreach ($users as $user) {
            $this->info("📱 Пользователь: {$user->name} (ID: {$user->id})");
            $this->info("   Email: {$user->email}");
            $this->info("   Telegram ID: {$user->telegram_id}");
            $this->info("   Username: @{$user->telegram_username}");
            $this->info("   Подключен: " . $user->telegram_connected_at->format('d.m.Y H:i:s'));
            $this->info("   Баланс: " . number_format($user->balance_rub ?? 0, 0, ',', ' ') . " ₽");
            $this->newLine();
        }
    }

    /**
     * Тестирование Telegram API
     */
    private function testTelegramApi()
    {
        $this->info('🧪 Тестирование Telegram API:');
        $this->newLine();

        $token = config('telegram.bot_token');
        if (!$token) {
            $this->error('❌ TELEGRAM_BOT_TOKEN не настроен');
            return;
        }

        // Тест getMe
        $this->testGetMe($token);
        
        // Тест getWebhookInfo
        $this->testGetWebhookInfo($token);
        
        // Тест getUpdates
        $this->testGetUpdates($token);
    }

    /**
     * Тест метода getMe
     */
    private function testGetMe($token)
    {
        $this->info('🔍 Тестирование getMe:');
        
        $response = $this->makeApiRequest($token, 'getMe');
        
        if ($response && $response['ok']) {
            $bot = $response['result'];
            $this->info('   ✅ Успешно');
            $this->info("      ID: {$bot['id']}");
            $this->info("      Имя: {$bot['first_name']}");
            $this->info("      Username: @{$bot['username']}");
            $this->info("      Поддерживает группы: " . ($bot['can_join_groups'] ? 'Да' : 'Нет'));
        } else {
            $this->error('   ❌ Ошибка');
        }
        
        $this->newLine();
    }

    /**
     * Тест метода getWebhookInfo
     */
    private function testGetWebhookInfo($token)
    {
        $this->info('🔗 Тестирование getWebhookInfo:');
        
        $response = $this->makeApiRequest($token, 'getWebhookInfo');
        
        if ($response && $response['ok']) {
            $webhook = $response['result'];
            $this->info('   ✅ Успешно');
            
            if ($webhook['url']) {
                $this->info("      URL: {$webhook['url']}");
                $this->info("      Обновлений в очереди: " . ($webhook['pending_update_count'] ?? 0));
                if (isset($webhook['last_error_date'])) {
                    $errorDate = date('d.m.Y H:i:s', $webhook['last_error_date']);
                    $this->warn("      Последняя ошибка: {$errorDate}");
                    if (isset($webhook['last_error_message'])) {
                        $this->warn("      Сообщение ошибки: {$webhook['last_error_message']}");
                    }
                }
            } else {
                $this->info('      Webhook не установлен');
            }
        } else {
            $this->error('   ❌ Ошибка');
        }
        
        $this->newLine();
    }

    /**
     * Тест метода getUpdates
     */
    private function testGetUpdates($token)
    {
        $this->info('📨 Тестирование getUpdates:');
        
        $response = $this->makeApiRequest($token, 'getUpdates', ['limit' => 1]);
        
        if ($response && $response['ok']) {
            $updates = $response['result'];
            $this->info('   ✅ Успешно');
            $this->info("      Количество обновлений: " . count($updates));
            
            if (!empty($updates)) {
                $lastUpdate = end($updates);
                $this->info("      Последнее обновление ID: {$lastUpdate['update_id']}");
            }
        } else {
            $this->error('   ❌ Ошибка');
        }
        
        $this->newLine();
    }

    /**
     * Выполнить запрос к Telegram API
     */
    private function makeApiRequest($token, $method, $params = [])
    {
        $url = "https://api.telegram.org/bot{$token}/{$method}";
        
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Показать конфигурацию
     */
    private function showConfig()
    {
        $this->info('⚙️  Конфигурация Telegram бота:');
        $this->newLine();

        $config = [
            'TELEGRAM_BOT_TOKEN' => config('telegram.bot_token'),
            'TELEGRAM_BOT_USERNAME' => config('telegram.bot_username'),
            'TELEGRAM_WEBHOOK_URL' => config('telegram.webhook_url'),
        ];

        foreach ($config as $key => $value) {
            if ($key === 'TELEGRAM_BOT_TOKEN' && $value) {
                $value = substr($value, 0, 10) . '...';
            }
            
            $status = $value ? '✅' : '❌';
            $displayValue = $value ?: 'не установлено';
            
            $this->info("   {$status} {$key}: {$displayValue}");
        }

        $this->newLine();
        $this->info('📁 Пути файлов:');
        $this->info('   Config: ' . config_path('telegram.php'));
        $this->info('   Controller: ' . app_path('Http/Controllers/TelegramController.php'));
        $this->info('   Service: ' . app_path('Services/TelegramNotificationService.php'));
    }

    /**
     * Форматировать размер файла
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
} 