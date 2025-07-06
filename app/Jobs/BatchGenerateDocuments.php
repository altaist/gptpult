<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class BatchGenerateDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 1200; // 20 минут
    public $tries = 2;

    public function __construct(
        protected array $documentIds,
        protected array $options = []
    ) {
        $this->onQueue('document_creates');
    }

    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            Log::info('Batch генерация отменена');
            return;
        }

        try {
            Log::channel('queue')->info('🚀 Начало batch генерации документов', [
                'document_count' => count($this->documentIds),
                'batch_id' => $this->batch()->id
            ]);

            // Создаем задачи для каждого документа
            $jobs = [];
            foreach ($this->documentIds as $documentId) {
                $document = Document::find($documentId);
                if ($document) {
                    $jobs[] = new AsyncGenerateDocument($document, $this->options);
                }
            }

            // Запускаем batch с задачами
            Bus::batch($jobs)
                ->then(function () {
                    Log::channel('queue')->info('✅ Batch генерация завершена успешно');
                })
                ->catch(function () {
                    Log::channel('queue')->error('❌ Batch генерация завершилась с ошибками');
                })
                ->finally(function () {
                    Log::channel('queue')->info('🏁 Batch генерация завершена');
                })
                ->onQueue('document_creates')
                ->dispatch();

        } catch (\Exception $e) {
            Log::channel('queue')->error('❌ Ошибка в batch генерации', [
                'error' => $e->getMessage(),
                'document_ids' => $this->documentIds
            ]);
            
            throw $e;
        }
    }
} 