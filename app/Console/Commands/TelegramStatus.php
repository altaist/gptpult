<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TelegramStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверить статус телеграм бота';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botToken = config('telegram.bot_token');
        $botUsername = config('telegram.bot_username');
        $webhookUrl = config('telegram.webhook_url');

        if (!$botToken) {
            $this->error('❌ TELEGRAM_BOT_TOKEN не настроен в .env файле');
            return 1;
        }

        $this->info('🔍 Проверка статуса Telegram бота...');
        $this->newLine();

        // Проверяем информацию о боте
        $url = "https://api.telegram.org/bot{$botToken}/getMe";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            $this->error('❌ Ошибка при подключении к Telegram API');
            return 1;
        }

        $botInfo = json_decode($response, true);

        if ($botInfo['ok']) {
            $bot = $botInfo['result'];
            $this->info("✅ Бот активен: @{$bot['username']}");
            $this->info("   Имя: {$bot['first_name']}");
            $this->info("   ID: {$bot['id']}");
        } else {
            $this->error('❌ Ошибка в ответе от Telegram API');
            return 1;
        }

        // Проверяем webhook
        $webhookInfoUrl = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhookInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== false) {
            $webhookInfo = json_decode($response, true);
            
            if ($webhookInfo['ok']) {
                $webhook = $webhookInfo['result'];
                
                if (!empty($webhook['url'])) {
                    $this->info("✅ Webhook установлен: {$webhook['url']}");
                    $this->info("   Ожидает обновлений: " . ($webhook['has_custom_certificate'] ? 'Да' : 'Нет'));
                    if (isset($webhook['pending_update_count'])) {
                        $this->info("   Ожидающих обновлений: {$webhook['pending_update_count']}");
                    }
                } else {
                    $this->warn('⚠️  Webhook не установлен');
                    $this->info('   Выполните: php artisan telegram:set-webhook');
                }
            }
        }

        $this->newLine();
        $this->info('📋 Настройки:');
        $this->info("   Bot Username: {$botUsername}");
        $this->info("   Webhook URL: {$webhookUrl}");
        $this->info("   Ссылка на бота: https://t.me/{$botUsername}");

        return 0;
    }
}
