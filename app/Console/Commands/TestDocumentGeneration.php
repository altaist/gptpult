<?php

namespace App\Console\Commands;

use App\Jobs\StartGenerateDocument;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Console\Command;

class TestDocumentGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:document-generation {--user-id=1} {--topic=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует генерацию документа через GPT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $topic = $this->option('topic') ?: 'Тестовая тема для генерации документа';

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

        $this->info('Создаю тестовый документ...');

        // Создаем документ с тестовыми данными
        $document = Document::factory()->create([
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

        $this->info("Документ создан с ID: {$document->id}");
        $this->info("Название: {$document->title}");

        // Запускаем Job
        $this->info('Запускаю Job для генерации документа...');
        StartGenerateDocument::dispatch($document);

        $this->info('Job отправлен в очередь document_creates');
        $this->info('Для обработки запустите: php artisan queue:work-documents');
        $this->info('Или: php artisan queue:work --queue=document_creates');

        return 0;
    }
} 