<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Jobs\StartGenerateDocument;
use App\Jobs\StartFullGenerateDocument;
use Illuminate\Console\Command;

class DemoFullSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:full-system {--user-id=1} {--topic=Тема по умолчанию}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Полная демонстрация новой системы генерации документов с базовой и полной генерацией';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $topic = $this->option('topic');
        
        // Проверяем пользователя
        $user = User::find($userId);
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return self::FAILURE;
        }

        // Получаем тип документа
        $documentType = DocumentType::first();
        if (!$documentType) {
            $this->error('Не найден ни один тип документа');
            return self::FAILURE;
        }

        $this->info('🚀 Демонстрация новой системы генерации документов');
        $this->line('');
        $this->info('📊 Система поддерживает:');
        $this->line('   1. Базовую генерацию (структура + цели)');
        $this->line('   2. Полную генерацию (детальное содержимое)');
        $this->line('   3. Автоматическое отслеживание статусов');
        $this->line('   4. Уведомления в реальном времени');
        $this->line('');

        // 1. Создаем документ
        $this->info('1️⃣ Создание документа...');
        $document = Document::factory()->create([
            'user_id' => $userId,
            'document_type_id' => $documentType->id,
            'title' => $topic,
            'status' => DocumentStatus::DRAFT,
            'structure' => [
                'topic' => $topic,
            ],
        ]);

        $this->line("   📄 Документ создан: {$document->title} (ID: {$document->id})");
        $this->line("   📊 Статус: {$document->status->getLabel()}");
        $this->line('');

        // 2. Запускаем базовую генерацию
        $this->info('2️⃣ Запуск базовой генерации...');
        StartGenerateDocument::dispatch($document);
        $this->line('   ✅ Job базовой генерации добавлен в очередь');
        $this->line('');

        // Показываем команды для мониторинга
        $this->info('📋 Команды для мониторинга и управления:');
        $this->line('');
        $this->line('🔄 Запуск воркера очереди:');
        $this->line('   php artisan queue:work-documents');
        $this->line('');
        $this->line('📊 Проверка статуса:');
        $this->line("   curl -X GET http://localhost/documents/{$document->id}/status");
        $this->line('');
        $this->line('🚀 Запуск полной генерации (после завершения базовой):');
        $this->line("   php artisan test:full-generation {$document->id}");
        $this->line('');
        $this->line('✅ Утверждение документа:');
        $this->line("   php artisan document:approve {$document->id}");
        $this->line('');
        $this->line('🌐 Веб-интерфейс:');
        $this->line("   http://localhost/documents/{$document->id}");
        $this->line('');

        // Информация о новых статусах
        $this->info('📈 Новые статусы системы:');
        $statuses = [
            DocumentStatus::DRAFT => 'Черновик (начальное состояние)',
            DocumentStatus::PRE_GENERATING => 'Генерируется базовая структура',
            DocumentStatus::PRE_GENERATED => 'Базовая структура готова',
            DocumentStatus::PRE_GENERATION_FAILED => 'Ошибка базовой генерации',
            DocumentStatus::FULL_GENERATING => 'Генерируется полное содержимое',
            DocumentStatus::FULL_GENERATED => 'Документ полностью готов',
            DocumentStatus::FULL_GENERATION_FAILED => 'Ошибка полной генерации',
            DocumentStatus::IN_REVIEW => 'На проверке',
            DocumentStatus::APPROVED => 'Утвержден',
            DocumentStatus::REJECTED => 'Отклонен',
        ];

        foreach ($statuses as $status => $description) {
            $statusValue = $status instanceof DocumentStatus ? $status->value : $status;
            $this->line("   {$statusValue}: {$description}");
        }

        $this->line('');
        $this->info('🎯 Интеграция с фронтендом:');
        $this->line('   - Автоматическое отслеживание статусов каждые 3 сек');
        $this->line('   - Кнопка "Полная генерация" появляется при статусе pre_generated');
        $this->line('   - Прогресс-бары показывают процент завершенности');
        $this->line('   - Автоматические переадресации при изменении статусов');
        $this->line('');
        
        $this->info("✨ Демонстрация готова! Документ ID: {$document->id}");
        
        return self::SUCCESS;
    }
} 