<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Console\Command;

class ApproveDocument extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:approve {document_id : ID документа для утверждения}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Утверждает документ (устанавливает статус approved)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentId = $this->argument('document_id');

        try {
            $document = Document::findOrFail($documentId);
            
            $this->info("Найден документ: {$document->title}");
            $this->info("Текущий статус: {$document->status}");
            
            // Обновляем статус на approved
            $document->update(['status' => DocumentStatus::APPROVED]);
            
            $this->info("✅ Документ успешно утвержден!");
            $this->info("Новый статус: {$document->status}");
            
            $this->line('');
            $this->line('Если документ отслеживается в браузере, должна произойти автоматическая переадресация.');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->error("❌ Документ с ID {$documentId} не найден");
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при утверждении документа: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 