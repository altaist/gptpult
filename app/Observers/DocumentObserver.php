<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\Telegram\TelegramNotificationService;
use Illuminate\Support\Facades\Log;

class DocumentObserver
{
    private TelegramNotificationService $telegramNotificationService;

    public function __construct(TelegramNotificationService $telegramNotificationService)
    {
        $this->telegramNotificationService = $telegramNotificationService;
    }

    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        // Отправляем уведомление о создании документа
        if ($document->status && $document->status->value === 'pre_generating') {
            try {
                $this->telegramNotificationService->notifyDocumentCreated($document);
            } catch (\Exception $e) {
                Log::error('Failed to send Telegram notification on document created', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        // Проверяем, изменился ли статус
        if ($document->wasChanged('status')) {
            $oldStatus = $document->getOriginal('status');
            $newStatus = $document->status->value;

            Log::info('Document status changed', [
                'document_id' => $document->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            try {
                match ($newStatus) {
                    'pre_generated' => $this->telegramNotificationService->notifyDocumentStructureReady($document),
                    'full_generated' => $this->handleFullGenerationCompleted($document),
                    'pre_generation_failed', 'full_generation_failed' => $this->telegramNotificationService->notifyDocumentGenerationFailed($document),
                    default => null
                };
            } catch (\Exception $e) {
                Log::error('Failed to send Telegram notification on document status change', [
                    'document_id' => $document->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Обработка завершения полной генерации
     */
    private function handleFullGenerationCompleted(Document $document): void
    {
        // Отправляем уведомление о готовности содержания
        $this->telegramNotificationService->notifyDocumentContentReady($document);

        // Отправляем файл документа пользователю
        $this->telegramNotificationService->sendDocumentFile($document);
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "restored" event.
     */
    public function restored(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "force deleted" event.
     */
    public function forceDeleted(Document $document): void
    {
        //
    }
}
