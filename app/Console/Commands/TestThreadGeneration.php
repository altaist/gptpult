<?php

namespace App\Console\Commands;

use App\Jobs\StartGenerateDocument;
use App\Jobs\StartFullGenerateDocument;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Console\Command;

class TestThreadGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:thread-generation {--user-id=1} {--topic=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует генерацию документа с использованием thread_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $topic = $this->option('topic') ?: 'Тестовая тема для проверки thread_id';

        // Проверяем существование пользователя
        $user = User::find($userId);
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }

        // Получаем первый тип документа
        $documentType = DocumentType::first();
        if (!$documentType) {
            $this->error('Не найден ни один тип документа');
            return 1;
        }

        $this->info('Создаю тестовый документ для проверки thread_id...');

        // Создаем документ с тестовыми данными
        $document = Document::factory()->minimal()->create([
            'user_id' => $userId,
            'document_type_id' => $documentType->id,
            'title' => $topic,
            'status' => 'draft',
        ]);

        // Обновляем topic в структуре
        $structure = $document->structure;
        $structure['topic'] = $topic;
        $document->structure = $structure;
        $document->save();

        $this->info("Документ создан: ID {$document->id}");
        $this->info("Текущий thread_id: " . ($document->thread_id ?? 'null'));

        // Запускаем генерацию структуры
        $this->info('Запускаю генерацию структуры...');
        dispatch(new StartGenerateDocument($document));

        // Даем время для выполнения
        sleep(5);

        // Проверяем результат
        $document->refresh();
        $this->info("После генерации структуры:");
        $this->info("- Статус: {$document->status->value}");
        $this->info("- Thread ID: " . ($document->thread_id ?? 'null'));
        
        if ($document->thread_id) {
            $this->info("✅ Thread ID успешно сохранен в БД!");
            
            // Если структура готова, запускаем полную генерацию
            if ($document->status->value === 'pre_generated') {
                $this->info('Запускаю полную генерацию с использованием thread_id...');
                dispatch(new StartFullGenerateDocument($document));
                
                sleep(10);
                
                $document->refresh();
                $this->info("После полной генерации:");
                $this->info("- Статус: {$document->status->value}");
                $this->info("- Thread ID: " . ($document->thread_id ?? 'null'));
                
                if ($document->thread_id) {
                    $this->info("✅ Thread ID сохранен и использован для полной генерации!");
                } else {
                    $this->warn("⚠️  Thread ID был утерян во время полной генерации");
                }
            }
        } else {
            $this->error("❌ Thread ID не был сохранен в БД");
        }

        return 0;
    }
} 