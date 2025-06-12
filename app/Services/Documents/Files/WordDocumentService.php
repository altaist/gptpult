<?php

namespace App\Services\Documents\Files;

use App\Models\Document;
use App\Services\Files\FilesService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\LineSpacingRule;

class WordDocumentService
{
    private PhpWord $phpWord;
    private FilesService $filesService;

    public function __construct(FilesService $filesService)
    {
        $this->phpWord = new PhpWord();
        $this->filesService = $filesService;
    }

    /**
     * Генерирует Word-документ из модели Document
     *
     * @param Document $document
     * @return \App\Models\File
     */
    public function generate(Document $document): \App\Models\File
    {
        // Настройка стилей
        $this->setupStyles();

        // Создание титульного листа
        $this->createTitlePage($document);

        // Создание содержания
        $this->createTableOfContents($document);

        // Создание пустой страницы
        $this->createEmptyPage();

        // Создание основного содержимого
        $this->createMainContent($document);

        // Создание списка источников
        $this->createReferences($document);

        // Сохранение документа
        $filePath = $this->saveDocument($document);

        // Создание записи о файле
        return $this->filesService->createFileFromPath(
            $filePath,
            $document->user,
            $document->title . '.docx',
            $document->id,
            'documents'
        );
    }

    private function setupStyles(): void
    {
        // Стиль для заголовков
        $this->phpWord->addTitleStyle(1, ['bold' => true, 'size' => 16], ['spaceAfter' => 240]);
        $this->phpWord->addTitleStyle(2, ['bold' => true, 'size' => 14], ['spaceAfter' => 240]);
        $this->phpWord->addTitleStyle(3, ['bold' => true, 'size' => 12], ['spaceAfter' => 240]);

        // Стиль для обычного текста
        $this->phpWord->addParagraphStyle('Normal', [
            'spaceAfter' => 120,
            'lineSpacing' => 1.5,
            'lineSpacingRule' => LineSpacingRule::AUTO
        ]);
    }

    private function createTitlePage(Document $document): void
    {
        $section = $this->phpWord->addSection();
        
        // Добавление отступов для центрирования
        $section->addTextBreak(10);
        
        // Заголовок
        $section->addText(
            $document->title,
            ['bold' => true, 'size' => 20],
            ['alignment' => Jc::CENTER]
        );
        
        $section->addTextBreak(2);
        
        // Тема
        if (isset($document->structure['topic'])) {
            $section->addText(
                $document->structure['topic'],
                ['size' => 14],
                ['alignment' => Jc::CENTER]
            );
        }
        
        $section->addTextBreak(10);
        
        // Информация об авторе
        $section->addText(
            'Автор: ' . $document->user->name,
            ['size' => 12],
            ['alignment' => Jc::CENTER]
        );
        
        $section->addTextBreak(2);
        
        // Дата
        $section->addText(
            'Дата: ' . now()->format('d.m.Y'),
            ['size' => 12],
            ['alignment' => Jc::CENTER]
        );
    }

    private function createTableOfContents(Document $document): void
    {
        $section = $this->phpWord->addSection();
        $section->addTextBreak(1);
        
        $section->addText(
            'Содержание',
            ['bold' => true, 'size' => 16],
            ['alignment' => Jc::CENTER]
        );
        
        $section->addTextBreak(2);
        
        // Добавление разделов из структуры документа
        if (isset($document->structure['contents'])) {
            foreach ($document->structure['contents'] as $index => $content) {
                $section->addText(
                    ($index + 1) . '. ' . $content['title'],
                    ['size' => 12],
                    ['alignment' => Jc::LEFT]
                );
            }
        }
    }

    private function createEmptyPage(): void
    {
        $this->phpWord->addSection();
    }

    private function createMainContent(Document $document): void
    {
        $section = $this->phpWord->addSection();
        
        // Добавление целей
        if (isset($document->structure['objectives'])) {
            $section->addText('Цели:', ['bold' => true, 'size' => 14]);
            foreach ($document->structure['objectives'] as $objective) {
                $section->addText('• ' . $objective, ['size' => 12], 'Normal');
            }
            $section->addTextBreak(2);
        }
        
        // Добавление основного содержимого
        if (isset($document->structure['contents'])) {
            foreach ($document->structure['contents'] as $index => $content) {
                $section->addText(
                    ($index + 1) . '. ' . $content['title'],
                    ['bold' => true, 'size' => 14]
                );
                
                foreach ($content['subtopics'] as $subtopic) {
                    $section->addText(
                        $subtopic['title'],
                        ['bold' => true, 'size' => 12]
                    );
                    $section->addText($subtopic['content'], ['size' => 12], 'Normal');
                }
            }
        }
    }

    private function createReferences(Document $document): void
    {
        $section = $this->phpWord->addSection();
        $section->addTextBreak(2);
        
        $section->addText(
            'Список источников',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER]
        );
        
        $section->addTextBreak(2);
        
        if (isset($document->structure['references'])) {
            foreach ($document->structure['references'] as $index => $reference) {
                $text = sprintf(
                    '%d. %s. %s. %s. URL: %s',
                    $index + 1,
                    $reference['author'],
                    $reference['title'],
                    $reference['year'],
                    $reference['url']
                );
                $section->addText($text, ['size' => 12], 'Normal');
            }
        }
    }

    private function saveDocument(Document $document): string
    {
        $filename = storage_path('app/public/' . $document->id . '_' . time() . '.docx');
        
        // Создаем директорию, если она не существует
        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        
        // Сохраняем документ напрямую в файл
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($filename);

        return $filename;
    }
} 