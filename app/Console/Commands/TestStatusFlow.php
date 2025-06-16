<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Console\Command;

class TestStatusFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:status-flow {--user-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Демонстрирует полный цикл изменения статусов документа';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        
        // Проверяем пользователя
        $user = User::find($userId);
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }

        // Получаем тип документа
        $documentType = DocumentType::first();
        if (!$documentType) {
            $this->error('Не найден ни один тип документа');
            return 1;
        }

        $this->info('🚀 Демонстрация цикла статусов документа');
        $this->line('');

        // 1. Создаем документ в статусе DRAFT
        $this->info('1️⃣ Создание документа...');
        $document = Document::factory()->create([
            'user_id' => $userId,
            'document_type_id' => $documentType->id,
            'title' => 'Тестовый документ для демонстрации статусов',
            'status' => DocumentStatus::DRAFT,
            'structure' => [
                'topic' => 'Демонстрация системы статусов',
            ],
        ]);

        $this->displayStatus($document, 'Документ создан');

        // 2. Имитация начала базовой генерации
        $this->info('2️⃣ Начало базовой генерации...');
        sleep(1);
        $document->update(['status' => DocumentStatus::PRE_GENERATING]);
        $this->displayStatus($document, 'Запущена базовая генерация');

        // 3. Имитация успешной базовой генерации
        $this->info('3️⃣ Завершение базовой генерации...');
        sleep(2);
        $document->update([
            'status' => DocumentStatus::PRE_GENERATED,
            'structure' => array_merge($document->structure, [
                'contents' => [
                    ['title' => 'Раздел 1', 'subtopics' => [['title' => 'Подраздел 1.1']]]
                ],
                'objectives' => ['Цель 1', 'Цель 2']
            ])
        ]);
        $this->displayStatus($document, 'Базовая генерация завершена');

        // 4. Имитация полной генерации
        $this->info('4️⃣ Начало полной генерации...');
        sleep(1);
        $document->update(['status' => DocumentStatus::FULL_GENERATING]);
        $this->displayStatus($document, 'Запущена полная генерация');

        // 5. Имитация успешной полной генерации
        $this->info('5️⃣ Завершение полной генерации...');
        sleep(3);
        $structure = $document->structure;
        $structure['detailed_contents'] = [
            [
                'title' => 'Раздел 1',
                'introduction' => 'Подробное введение к разделу',
                'subtopics' => [
                    [
                        'title' => 'Подраздел 1.1',
                        'content' => 'Детальное содержание подраздела с примерами и объяснениями...',
                        'examples' => ['Пример 1', 'Пример 2'],
                        'key_points' => ['Ключевой момент 1', 'Ключевой момент 2']
                    ]
                ],
                'summary' => 'Краткое резюме раздела'
            ]
        ];
        $structure['introduction'] = 'Подробное введение к документу...';
        $structure['conclusion'] = 'Заключение документа...';
        
        $document->update([
            'status' => DocumentStatus::FULL_GENERATED,
            'structure' => $structure
        ]);
        $this->displayStatus($document, 'Полная генерация завершена');

        // 6. Отправка на проверку
        $this->info('6️⃣ Отправка на проверку...');
        sleep(1);
        $document->update(['status' => DocumentStatus::IN_REVIEW]);
        $this->displayStatus($document, 'Отправлен на проверку');

        // 7. Утверждение
        $this->info('7️⃣ Утверждение документа...');
        sleep(1);
        $document->update(['status' => DocumentStatus::APPROVED]);
        $this->displayStatus($document, 'Документ утвержден');

        $this->line('');
        $this->info('✅ Демонстрация завершена!');
        $this->info("ID документа: {$document->id}");
        $this->info('Можете проверить изменения через API: /documents/' . $document->id . '/status');

        return 0;
    }

    /**
     * Отобразить текущий статус документа
     */
    private function displayStatus(Document $document, string $action): void
    {
        $statusEnum = $document->status;
        
        $this->line("   📄 {$action}");
        $this->line("   📊 Статус: {$statusEnum->value} ({$statusEnum->getLabel()})");
        $this->line("   🎨 Цвет: {$statusEnum->getColor()}");
        $this->line("   🔗 Иконка: {$statusEnum->getIcon()}");
        $this->line("   🏁 Финальный: " . ($statusEnum->isFinal() ? 'Да' : 'Нет'));
        $this->line('');
    }
} 