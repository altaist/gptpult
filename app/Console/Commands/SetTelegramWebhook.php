<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Установить webhook для телеграм бота';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botToken = config('telegram.bot_token');
        $webhookUrl = config('telegram.webhook_url');

        if (!$botToken) {
            $this->error('TELEGRAM_BOT_TOKEN не настроен в .env файле');
            return 1;
        }

        if (!$webhookUrl) {
            $this->error('TELEGRAM_WEBHOOK_URL не настроен');
            return 1;
        }

        $this->info("Настройка webhook для бота...");
        $this->info("Webhook URL: {$webhookUrl}");

        $url = "https://api.telegram.org/bot{$botToken}/setWebhook";
        
        $data = [
            'url' => $webhookUrl,
            'allowed_updates' => json_encode(['message', 'callback_query'])
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            $this->error('Ошибка при отправке запроса к Telegram API');
            return 1;
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && $result['ok']) {
            $this->info('✅ Webhook успешно установлен!');
            $this->info("Описание: " . $result['description']);
            return 0;
        } else {
            $this->error('❌ Ошибка при установке webhook:');
            $this->error(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 1;
        }
    }
}
