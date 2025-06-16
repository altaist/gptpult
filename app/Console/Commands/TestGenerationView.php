<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Console\Command;

class TestGenerationView extends Command
{
    protected $signature = 'test:generation-view {action=create : Действие (create, set-generating, set-completed)}';
    protected $description = 'Тестирует компонент отображения генерации';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'create':
                $this->createTestDocument();
                break;
            case 'set-generating':
                $this->setGeneratingStatus();
                break;
            case 'set-completed':
                $this->setCompletedStatus();
                break;
            default:
                $this->error('Неизвестное действие. Доступные: create, set-generating, set-completed');
        }
    }

    private function createTestDocument()
    {
        $document = Document::factory()->create([
            'title' => 'Тестовый документ для компонента генерации',
            'status' => DocumentStatus::PRE_GENERATING,
        ]);

        $this->info("✅ Создан тестовый документ:");
        $this->line("   ID: {$document->id}");
        $this->line("   Статус: {$document->status->value} ({$document->status->getLabel()})");
        $this->line("   URL: /documents/{$document->id}");
        $this->line("");
        $this->info("🎯 Компонент генерации должен отображаться на весь экран!");
    }

    private function setGeneratingStatus()
    {
        $documents = Document::whereIn('status', [
            DocumentStatus::DRAFT->value,
            DocumentStatus::PRE_GENERATED->value,
            DocumentStatus::FULL_GENERATED->value
        ])->get();

        if ($documents->isEmpty()) {
            $this->error('Нет документов для изменения статуса. Создайте документ командой: test:generation-view create');
            return;
        }

        $document = $documents->first();
        $document->update(['status' => DocumentStatus::FULL_GENERATING]);

        $this->info("✅ Статус документа {$document->id} изменен на генерирующийся:");
        $this->line("   Статус: {$document->status->value} ({$document->status->getLabel()})");
        $this->line("   URL: /documents/{$document->id}");
        $this->line("");
        $this->info("🎯 Компонент генерации должен отображаться!");
    }

    private function setCompletedStatus()
    {
        $documents = Document::whereIn('status', [
            DocumentStatus::PRE_GENERATING->value,
            DocumentStatus::FULL_GENERATING->value
        ])->get();

        if ($documents->isEmpty()) {
            $this->error('Нет генерирующихся документов. Создайте документ командой: test:generation-view create');
            return;
        }

        $document = $documents->first();
        $document->update(['status' => DocumentStatus::PRE_GENERATED]);

        $this->info("✅ Статус документа {$document->id} изменен на завершенный:");
        $this->line("   Статус: {$document->status->value} ({$document->status->getLabel()})");
        $this->line("   URL: /documents/{$document->id}");
        $this->line("");
        $this->info("🎯 Должен отображаться обычный вид документа!");
    }
} 