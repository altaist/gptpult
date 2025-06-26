<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;

class TelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook {action : set|delete|info} {--url= : Webhook URL for set action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Управление веб-хуком Telegram бота';

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
        $action = $this->argument('action');

        switch ($action) {
            case 'set':
                return $this->setWebhook();
            case 'delete':
                return $this->deleteWebhook();
            case 'info':
                return $this->getWebhookInfo();
            default:
                $this->error('❌ Неизвестное действие. Используйте: set, delete, info');
                return 1;
        }
    }

    /**
     * Установить веб-хук
     */
    private function setWebhook(): int
    {
        $url = $this->option('url') ?: config('services.telegram.webhook_url');

        if (!$url) {
            $this->error('❌ URL веб-хука не указан. Используйте --url или установите TELEGRAM_WEBHOOK_URL в .env');
            return 1;
        }

        $this->info("🔗 Устанавливаем веб-хук: {$url}");

        $result = $this->telegramService->setWebhook($url);

        if ($result['ok']) {
            $this->info('✅ Веб-хук успешно установлен!');
            $this->info('📝 Описание: ' . ($result['description'] ?? 'No description'));
            return 0;
        } else {
            $this->error('❌ Ошибка при установке веб-хука: ' . ($result['description'] ?? 'Unknown error'));
            return 1;
        }
    }

    /**
     * Удалить веб-хук
     */
    private function deleteWebhook(): int
    {
        $this->info('🗑️  Удаляем веб-хук...');

        $result = $this->telegramService->deleteWebhook();

        if ($result['ok']) {
            $this->info('✅ Веб-хук успешно удален!');
            $this->info('📝 Описание: ' . ($result['description'] ?? 'No description'));
            return 0;
        } else {
            $this->error('❌ Ошибка при удалении веб-хука: ' . ($result['description'] ?? 'Unknown error'));
            return 1;
        }
    }

    /**
     * Получить информацию о веб-хуке
     */
    private function getWebhookInfo(): int
    {
        $this->info('ℹ️  Получаем информацию о веб-хуке...');

        $result = $this->telegramService->getWebhookInfo();

        if ($result['ok']) {
            $info = $result['result'];
            
            $this->info('📋 Информация о веб-хуке:');
            $this->table(['Параметр', 'Значение'], [
                ['URL', $info['url'] ?: 'Не установлен'],
                ['Проверка SSL', $info['has_custom_certificate'] ? 'Да' : 'Нет'],
                ['Ожидающие обновления', $info['pending_update_count']],
                ['Последняя ошибка', $info['last_error_message'] ?? 'Нет'],
                ['Дата последней ошибки', isset($info['last_error_date']) ? date('Y-m-d H:i:s', $info['last_error_date']) : 'Нет'],
            ]);
            
            return 0;
        } else {
            $this->error('❌ Ошибка при получении информации о веб-хуке: ' . ($result['description'] ?? 'Unknown error'));
            return 1;
        }
    }
} 