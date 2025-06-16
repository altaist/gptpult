<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Jobs\StartFullGenerateDocument;
use App\Models\Document;
use Illuminate\Console\Command;

class TestFullGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:full-generation {document_id : ID документа для полной генерации}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует полную генерацию документа';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentId = $this->argument('document_id');

        try {
            $document = Document::findOrFail($documentId);
            
            $this->info("🔍 Найден документ: {$document->title}");
            $this->info("📊 Текущий статус: {$document->status->value} ({$document->status->getLabel()})");
            
            // Проверяем, можно ли запустить полную генерацию
            if (!$document->status->canStartFullGeneration()) {
                $this->error("❌ Документ не готов к полной генерации");
                $this->error("Требуемый статус: pre_generated");
                $this->error("Текущий статус: {$document->status->value}");
                
                // Предлагаем установить нужный статус
                if ($this->confirm('Установить статус pre_generated для тестирования?')) {
                    $document->update(['status' => DocumentStatus::PRE_GENERATED]);
                    $this->info("✅ Статус изменен на pre_generated");
                } else {
                    return self::FAILURE;
                }
            }
            
            $this->info("🚀 Запускаем полную генерацию документа...");
            
            // Запускаем Job для полной генерации
            StartFullGenerateDocument::dispatch($document);
            
            $this->info("✅ Job полной генерации успешно добавлен в очередь!");
            $this->info("📋 Для выполнения запустите: php artisan queue:work --queue=document_creates");
            
            $this->line('');
            $this->line('🔄 Статус можно отслеживать через:');
            $this->line("   - Веб-интерфейс: /documents/{$document->id}");
            $this->line("   - API: GET /documents/{$document->id}/generation-progress");
            $this->line("   - Логи: storage/logs/queue.log");
            
            // Показываем текущую структуру документа
            $structure = $document->structure ?? [];
            $this->line('');
            $this->line('📊 Текущая структура документа:');
            
            if (!empty($structure['contents'])) {
                $this->line("  ✅ Базовое содержание: " . count($structure['contents']) . " разделов");
            } else {
                $this->line("  ❌ Базовое содержание: отсутствует");
            }
            
            if (!empty($structure['objectives'])) {
                $this->line("  ✅ Цели: " . count($structure['objectives']) . " пунктов");
            } else {
                $this->line("  ❌ Цели: отсутствуют");
            }
            
            if (!empty($structure['detailed_contents'])) {
                $this->line("  ✅ Детальное содержание: " . count($structure['detailed_contents']) . " разделов");
            } else {
                $this->line("  ❓ Детальное содержание: будет создано при полной генерации");
            }
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->error("❌ Документ с ID {$documentId} не найден");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при запуске полной генерации: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
} 