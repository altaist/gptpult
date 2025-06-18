<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Documents\DocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillDocumentWithDefaults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:fill-defaults {id : ID документа для заполнения} {--force : Перезаписать существующие данные}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Заполняет структуру и содержимое документа значениями по умолчанию из фабрики';

    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        parent::__construct();
        $this->documentService = $documentService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentId = $this->argument('id');
        $force = $this->option('force');

        // Проверяем существование документа
        $document = Document::find($documentId);
        if (!$document) {
            $this->error("Документ с ID {$documentId} не найден.");
            return Command::FAILURE;
        }

        $this->info("Найден документ: {$document->title}");
        $this->info("Статус: {$document->status->getLabel()} ({$document->status->value})");
        $this->info("Пользователь: {$document->user->name}");

        // Проверяем нужно ли подтверждение перезаписи
        if (!$force && $this->hasContent($document)) {
            if (!$this->confirm('Документ уже содержит данные. Перезаписать?')) {
                $this->info('Операция отменена.');
                return Command::SUCCESS;
            }
        }

        try {
            DB::beginTransaction();

            // Генерируем данные из фабрики
            $factoryData = $this->generateFactoryData();

            // Обновляем документ
            $this->documentService->update($document, [
                'structure' => $factoryData['structure'],
                'content' => $factoryData['content'] ?? null,
            ]);

            DB::commit();

            $this->info("✅ Структура и содержимое документа успешно заполнены данными по умолчанию!");
            $this->displayDocumentInfo($document->fresh());

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Ошибка при заполнении документа: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Проверяет есть ли у документа содержимое
     */
    private function hasContent(Document $document): bool
    {
        $structure = $document->structure ?? [];
        
        return !empty($structure['theses']) ||
               !empty($structure['objectives']) ||
               !empty($structure['contents']) ||
               !empty($structure['references']);
    }

    /**
     * Генерирует данные из фабрики
     */
    private function generateFactoryData(): array
    {
        $faker = \Faker\Factory::create('ru_RU');

        return [
            'structure' => [
                'topic' => $faker->sentence(5),
                'theses' => $faker->paragraphs(2, true),
                'objectives' => [
                    $faker->sentence(6),
                    $faker->sentence(7),
                    $faker->sentence(5),
                ],
                'contents' => [
                    [
                        'title' => $faker->sentence(3),
                        'subtopics' => [
                            [
                                'title' => $faker->sentence(4),
                                'content' => $faker->paragraph(2),
                            ],
                            [
                                'title' => $faker->sentence(4),
                                'content' => $faker->paragraph(2),
                            ],
                        ],
                    ],
                    [
                        'title' => $faker->sentence(3),
                        'subtopics' => [
                            [
                                'title' => $faker->sentence(4),
                                'content' => $faker->paragraph(2),
                            ],
                            [
                                'title' => $faker->sentence(4),
                                'content' => $faker->paragraph(2),
                            ],
                        ],
                    ],
                    [
                        'title' => $faker->sentence(3),
                        'subtopics' => [
                            [
                                'title' => $faker->sentence(4),
                                'content' => $faker->paragraph(2),
                            ],
                            [
                                'title' => $faker->sentence(4),
                                'content' => $faker->paragraph(2),
                            ],
                        ],
                    ],
                ],
                'references' => [
                    [
                        'title' => $faker->sentence(4),
                        'author' => $faker->name,
                        'year' => $faker->year,
                        'url' => $faker->url,
                    ],
                    [
                        'title' => $faker->sentence(3),
                        'author' => $faker->name,
                        'year' => $faker->year,
                        'url' => $faker->url,
                    ],
                ],
            ],
            'content' => $faker->paragraphs(5, true), // Добавляем содержимое документа
        ];
    }

    /**
     * Отображает информацию о документе
     */
    private function displayDocumentInfo(Document $document): void
    {
        $structure = $document->structure ?? [];
        
        $this->line('');
        $this->line('<fg=cyan>📄 Информация о документе:</fg=cyan>');
        $this->line("ID: {$document->id}");
        $this->line("Заголовок: {$document->title}");
        $this->line("Тема: " . ($structure['topic'] ?? 'Не указана'));
        $this->line("Количество целей: " . count($structure['objectives'] ?? []));
        $this->line("Количество разделов содержания: " . count($structure['contents'] ?? []));
        $this->line("Количество источников: " . count($structure['references'] ?? []));
        $this->line("Статус: {$document->status->getLabel()} ({$document->status->value})");
        $this->line("Обновлен: {$document->updated_at}");
    }
}
