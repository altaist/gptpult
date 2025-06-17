<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestDocumentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:create-test {user : ID или email пользователя}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создает тестовые документы для указанного пользователя с разными статусами и типами';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userInput = $this->argument('user');
        
        // Найти пользователя по ID или email
        $user = is_numeric($userInput) 
            ? User::find($userInput)
            : User::where('email', $userInput)->first();

        if (!$user) {
            $this->error("❌ Пользователь не найден: {$userInput}");
            return Command::FAILURE;
        }

        $this->info("👤 Создаю тестовые документы для пользователя: {$user->name} ({$user->email})");
        $this->line('');

        // Получить все типы документов
        $documentTypes = DocumentType::all();
        
        if ($documentTypes->isEmpty()) {
            $this->error('❌ Не найдено ни одного типа документов. Запустите сидер DocumentTypeSeeder.');
            return Command::FAILURE;
        }

        // Массив статусов для тестирования (берем разные статусы)
        $testStatuses = [
            DocumentStatus::DRAFT,
            DocumentStatus::PRE_GENERATED,
            DocumentStatus::FULL_GENERATED,
            DocumentStatus::IN_REVIEW,
            DocumentStatus::APPROVED,
            DocumentStatus::REJECTED,
        ];

        $createdCount = 0;

        // Создаем по 1-2 документа для каждого типа с разными статусами
        foreach ($documentTypes as $index => $documentType) {
            $documentsToCreate = rand(1, 2); // 1-2 документа на тип
            
            for ($i = 0; $i < $documentsToCreate; $i++) {
                $status = $testStatuses[($index * $documentsToCreate + $i) % count($testStatuses)];
                
                $document = Document::factory()->create([
                    'user_id' => $user->id,
                    'document_type_id' => $documentType->id,
                    'title' => "Тестовый {$documentType->name} №" . ($i + 1),
                    'status' => $status,
                ]);

                // Обновляем тему в структуре
                $structure = $document->structure;
                $structure['topic'] = "Тестовая тема для {$documentType->name} №" . ($i + 1);
                $document->update(['structure' => $structure]);

                $createdCount++;
                
                $this->line("✅ Создан документ: {$document->title} (Статус: {$status->getLabel()})");
            }
        }

        $this->line('');
        $this->info("🎉 Успешно создано документов: {$createdCount}");
        $this->line('');
        
        // Показать статистику по статусам
        $this->info('📊 Статистика по статусам:');
        foreach ($testStatuses as $status) {
            $count = Document::where('user_id', $user->id)
                ->where('status', $status)
                ->count();
            if ($count > 0) {
                $this->line("   {$status->getLabel()}: {$count}");
            }
        }

        return Command::SUCCESS;
    }
}
