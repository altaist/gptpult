<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Console\Command;

class SetDocumentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:set-status {document_id : ID документа} {status : Статус документа}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Устанавливает статус документа';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentId = $this->argument('document_id');
        $statusValue = $this->argument('status');

        try {
            // Проверяем, что статус валидный
            $validStatuses = DocumentStatus::values();
            if (!in_array($statusValue, $validStatuses)) {
                $this->error("❌ Недопустимый статус: {$statusValue}");
                $this->line('Доступные статусы:');
                foreach (DocumentStatus::cases() as $status) {
                    $this->line("  • {$status->value} - {$status->getLabel()}");
                }
                return 1;
            }

            $document = Document::findOrFail($documentId);
            $status = DocumentStatus::from($statusValue);
            
            $this->info("Найден документ: {$document->title}");
            $this->info("Текущий статус: {$document->status->getLabel()} ({$document->status->value})");
            
            // Обновляем статус
            $document->update(['status' => $status]);
            
            $this->info("✅ Статус документа успешно изменен!");
            $this->info("Новый статус: {$document->status->getLabel()} ({$document->status->value})");
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->error("❌ Документ с ID {$documentId} не найден");
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при изменении статуса документа: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 