<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Telegram\TelegramNotificationService;
use Illuminate\Console\Command;

class TestTelegramNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:telegram-notifications {document_id : ID документа для тестирования}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует отправку уведомлений в Telegram';

    /**
     * Execute the console command.
     */
    public function handle(TelegramNotificationService $telegramService)
    {
        $documentId = $this->argument('document_id');

        try {
            $document = Document::with(['user', 'documentType'])->findOrFail($documentId);
            
            $this->info("Найден документ: {$document->title}");
            $this->info("Тип: {$document->documentType->name}");
            $this->info("Статус: {$document->status->value}");
            $this->info("Пользователь: {$document->user->name} (ID: {$document->user->id})");
            
            if (!$document->user->telegram_id) {
                $this->error('У пользователя не привязан Telegram!');
                return self::FAILURE;
            }
            
            $this->info("Telegram ID: {$document->user->telegram_id}");
            
            $this->line('');
            $this->info('Тестируем уведомления...');
            
            // Тест 1: Уведомление о создании
            $this->line('');
            $this->info('1. Отправляем уведомление о создании документа...');
            
            try {
                $telegramService->notifyDocumentCreated($document);
                $this->info('Успешно отправлено');
            } catch (\Exception $e) {
                $this->error('Ошибка: ' . $e->getMessage());
            }
            
            // Тест 2: Уведомление о готовности структуры  
            $this->line('');
            $this->info('Отправляем уведомление о готовности структуры...');
            
            try {
                $telegramService->notifyDocumentStructureReady($document);
                $this->info('Уведомление о структуре отправлено');
            } catch (\Exception $e) {
                $this->error('Ошибка: ' . $e->getMessage());
            }
            
            sleep(2);

            // Тестируем уведомление о готовности содержания
            $this->info('🎉 Отправляем уведомление о готовности содержания...');
            $telegramService->notifyDocumentContentReady($document);
            $this->info('✅ Уведомление о содержании отправлено');

            sleep(2);

            // Тестируем отправку файла (если документ готов)
            if ($document->status->value === 'full_generated') {
                $this->info('📎 Отправляем файл документа...');
                $telegramService->sendDocumentFile($document);
                $this->info('✅ Файл отправлен');
            } else {
                $this->info('⏭️ Пропускаем отправку файла (документ не полностью готов)');
            }

            $this->line('');
            $this->info('🎯 Все уведомления успешно отправлены!');
            $this->info('📱 Проверьте Telegram чат для подтверждения доставки');

            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
