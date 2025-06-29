<?php

namespace App\Services\Documents\Files;

use App\Models\Document;
use App\Services\Files\FilesService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\LineSpacingRule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        // Создание титульного листа (главная страница)
        $this->createTitlePage($document);

        // Создание страницы с содержанием
        $this->createTableOfContents($document);

        // Создание основного содержимого с заголовками
        $this->createMainContent($document);

        // Создание списка источников
        $this->createReferences($document);

        // Сохранение документа
        $filePath = $this->saveDocument($document);

        // Создаем безопасное имя файла на основе названия документа
        $fileName = $this->generateSafeFileName($document->title);

        // Создание записи о файле
        return $this->filesService->createFileFromPath(
            $filePath,
            $document->user,
            $fileName,
            $document->id,
            'documents'
        );
    }

    private function setupStyles(): void
    {
        // Стиль для заголовков глав
        $this->phpWord->addTitleStyle(1, [
            'bold' => true, 
            'size' => 16, 
            'color' => '000000'
        ], [
            'spaceAfter' => 240,
            'spaceBefore' => 240,
            'alignment' => Jc::LEFT
        ]);
        
        // Стиль для заголовков подглав
        $this->phpWord->addTitleStyle(2, [
            'bold' => true, 
            'size' => 14, 
            'color' => '000000'
        ], [
            'spaceAfter' => 200,
            'spaceBefore' => 200,
            'alignment' => Jc::LEFT
        ]);
        
        // Стиль для подразделов
        $this->phpWord->addTitleStyle(3, [
            'bold' => true, 
            'size' => 12, 
            'color' => '000000'
        ], [
            'spaceAfter' => 160,
            'spaceBefore' => 160,
            'alignment' => Jc::LEFT
        ]);

        // Стиль для обычного текста
        $this->phpWord->addParagraphStyle('Normal', [
            'spaceAfter' => 120,
            'lineSpacing' => 1.5,
            'lineSpacingRule' => LineSpacingRule::AUTO,
            'alignment' => Jc::BOTH
        ]);

        // Стиль для заголовка титульной страницы
        $this->phpWord->addParagraphStyle('TitleMain', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 400
        ]);

        // Стиль для подзаголовка титульной страницы
        $this->phpWord->addParagraphStyle('TitleSub', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 200
        ]);

        // Стиль для информации об авторе
        $this->phpWord->addParagraphStyle('AuthorInfo', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 120
        ]);

        // Стиль для содержания
        $this->phpWord->addParagraphStyle('TOCHeading', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 300,
            'spaceBefore' => 200
        ]);

        // Стиль для пунктов содержания
        $this->phpWord->addParagraphStyle('TOCItem', [
            'alignment' => Jc::LEFT,
            'spaceAfter' => 80,
            'hangingIndent' => 280
        ]);

        // Стиль для списка источников
        $this->phpWord->addParagraphStyle('References', [
            'alignment' => Jc::BOTH,
            'spaceAfter' => 80,
            'hangingIndent' => 360,
            'lineSpacing' => 1.0
        ]);

        // Стиль для описаний источников
        $this->phpWord->addParagraphStyle('ReferenceDescription', [
            'alignment' => Jc::BOTH,
            'spaceAfter' => 120,
            'leftIndent' => 720,
            'lineSpacing' => 1.0
        ]);
    }

    private function createTitlePage(Document $document): void
    {
        $section = $this->phpWord->addSection([
            'breakType' => 'nextPage'
        ]);
        
        // Добавление отступов для центрирования по вертикали
        $section->addTextBreak(8);
        
        // Основной заголовок
        $section->addText(
            strtoupper($document->title),
            ['bold' => true, 'size' => 20, 'allCaps' => true],
            'TitleMain'
        );
        
        // Тема (если есть)
        if (isset($document->structure['topic']) && $document->structure['topic'] !== $document->title) {
            $section->addText(
                $document->structure['topic'],
                ['size' => 16],
                'TitleSub'
            );
        }
        
        // Большой отступ
        $section->addTextBreak(12);
        
        // Информация об авторе
        $section->addText(
            'Автор: ' . $document->user->name,
            ['size' => 14],
            'AuthorInfo'
        );
        
        // Дата создания
        $section->addText(
            'Дата: ' . $document->created_at->format('d.m.Y'),
            ['size' => 14],
            'AuthorInfo'
        );
    }

    private function createTableOfContents(Document $document): void
    {
        $section = $this->phpWord->addSection([
            'breakType' => 'nextPage'
        ]);
        
        // Заголовок содержания
        $section->addText(
            'СОДЕРЖАНИЕ',
            ['bold' => true, 'size' => 16, 'allCaps' => true],
            'TOCHeading'
        );
        
        // Получаем структуру документа из поля structure
        $contentData = $document->structure['contents'] ?? [];
        
        if (!empty($contentData)) {
            $pageNumber = 3; // Начинаем с 3 страницы (1-титул, 2-содержание, 3-начало текста)
            
            foreach ($contentData as $index => $topic) {
                // Основная глава
                $chapterNumber = $index + 1;
                $tocLine = "{$chapterNumber}. {$topic['title']}";
                
                $section->addText(
                    $tocLine,
                    ['size' => 12, 'bold' => true],
                    'TOCItem'
                );
                
                // Подглавы
                if (!empty($topic['subtopics'])) {
                    foreach ($topic['subtopics'] as $subIndex => $subtopic) {
                        $subNumber = $subIndex + 1;
                        $subTocLine = "  {$chapterNumber}.{$subNumber}. {$subtopic['title']}";
                        
                        $section->addText(
                            $subTocLine,
                            ['size' => 11],
                            'TOCItem'
                        );
                    }
                }
                
                $section->addTextBreak(1);
            }
        }
    }

    private function createMainContent(Document $document): void
    {
        $section = $this->phpWord->addSection([
            'breakType' => 'nextPage'
        ]);
        
        // Отладочная информация
        \Illuminate\Support\Facades\Log::info('WordDocumentService: обработка content поля', [
            'document_id' => $document->id,
            'content_type' => gettype($document->content),
            'content_value' => $document->content,
            'has_topics' => isset($document->content['topics']) ? 'yes' : 'no'
        ]);
        
        // Получаем контент документа из поля content
        $contentData = $document->content['topics'] ?? [];
        
        if (empty($contentData)) {
            \Illuminate\Support\Facades\Log::warning('WordDocumentService: content data пуста', [
                'document_id' => $document->id,
                'content_data' => $contentData
            ]);
            
            $section->addText(
                'Содержимое документа пока не сгенерировано.',
                ['size' => 12],
                'Normal'
            );
            return;
        }

        \Illuminate\Support\Facades\Log::info('WordDocumentService: найдено topics', [
            'document_id' => $document->id,
            'topics_count' => count($contentData)
        ]);

        // Генерируем основной текст с заголовками
        foreach ($contentData as $index => $topic) {
            $chapterNumber = $index + 1;
            
            // Заголовок главы (H1)
            $section->addTitle(
                "{$chapterNumber}. {$topic['title']}",
                1
            );
            $section->addTextBreak(1);
            
            // Подглавы
            if (!empty($topic['subtopics'])) {
                foreach ($topic['subtopics'] as $subIndex => $subtopic) {
                    $subNumber = $subIndex + 1;
                    
                    // Заголовок подглавы (H2)
                    $section->addTitle(
                        "{$chapterNumber}.{$subNumber}. {$subtopic['title']}",
                        2
                    );
                    $section->addTextBreak(1);
                    
                    // Содержимое подглавы
                    if (isset($subtopic['content']) && !empty($subtopic['content'])) {
                        $content = $subtopic['content'];
                        
                        // Разбиваем длинный текст на абзацы
                        $paragraphs = $this->splitTextIntoParagraphs($content);
                        
                        foreach ($paragraphs as $paragraph) {
                            if (trim($paragraph)) {
                                $section->addText(
                                    trim($paragraph),
                                    ['size' => 12],
                                    'Normal'
                                );
                                $section->addTextBreak(1);
                            }
                        }
                    } else {
                        // Fallback если контент не сгенерирован
                        $section->addText(
                            'Содержимое данного раздела будет добавлено позже.',
                            ['size' => 12, 'italic' => true],
                            'Normal'
                        );
                        $section->addTextBreak(1);
                    }
                    
                    $section->addTextBreak(1);
                }
            }
            
            // Добавляем разрыв между главами
            $section->addTextBreak(2);
        }
    }

    private function createReferences(Document $document): void
    {
        $section = $this->phpWord->addSection([
            'breakType' => 'nextPage'
        ]);
        
        // Заголовок списка источников
        $section->addTitle('Список источников', 1);
        $section->addTextBreak(2);
        
        // Получаем источники из структуры документа
        $references = $document->structure['references'] ?? [];
        
        if (!empty($references)) {
            foreach ($references as $index => $reference) {
                $referenceNumber = $index + 1;
                
                // Формируем строку источника по академическим стандартам
                $referenceText = "{$referenceNumber}. ";
                
                // Автор (если есть)
                if (isset($reference['author']) && !empty($reference['author'])) {
                    $referenceText .= $reference['author'] . '. ';
                }
                
                // Название (обязательное поле)
                if (isset($reference['title']) && !empty($reference['title'])) {
                    $referenceText .= $reference['title'];
                    
                    // Добавляем тип ресурса в скобках для ясности
                    if (isset($reference['type']) && !empty($reference['type'])) {
                        $typeLabels = [
                            'article' => 'статья',
                            'pdf' => 'PDF',
                            'book' => 'книга',
                            'website' => 'веб-сайт',
                            'research_paper' => 'научная работа',
                            'other' => 'ресурс'
                        ];
                        $typeLabel = $typeLabels[$reference['type']] ?? 'ресурс';
                        $referenceText .= " [{$typeLabel}]";
                    }
                    
                    $referenceText .= '. ';
                }
                
                // Дата публикации (если есть) - поддерживаем как новое поле publication_date, так и старое year
                $publicationDate = $reference['publication_date'] ?? $reference['year'] ?? null;
                if (!empty($publicationDate)) {
                    $referenceText .= $publicationDate . '. ';
                }
                
                // URL
                if (isset($reference['url']) && !empty($reference['url'])) {
                    $referenceText .= 'URL: ' . $reference['url'];
                }
                
                // Основная ссылка
                $section->addText(
                    trim($referenceText),
                    ['size' => 12],
                    'References'
                );
                
                // Описание релевантности (если есть) - добавляем как отдельный абзац с отступом
                if (isset($reference['description']) && !empty($reference['description'])) {
                    $section->addText(
                        trim($reference['description']),
                        ['size' => 11, 'italic' => true, 'color' => '666666'],
                        'ReferenceDescription'
                    );
                }
                
                // Добавляем небольшой отступ между источниками
                $section->addTextBreak(1);
            }
        } else {
            // Если источников нет, добавляем заглушку
            $section->addText(
                'Список источников будет добавлен позже.',
                ['size' => 12, 'italic' => true],
                'Normal'
            );
        }
    }

    /**
     * Разбивает текст на абзацы
     */
    private function splitTextIntoParagraphs(string $text): array
    {
        // Убираем лишние пробелы и переносы
        $text = trim($text);
        
        // Разбиваем по двойным переносам строк
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        // Если абзацев мало, разбиваем по точкам (но не более чем на 5 абзацев)
        if (count($paragraphs) <= 1 && strlen($text) > 500) {
            $sentences = preg_split('/\.\s+/', $text);
            $paragraphs = [];
            $currentParagraph = '';
            $sentenceCount = 0;
            
            foreach ($sentences as $sentence) {
                $currentParagraph .= $sentence . '. ';
                $sentenceCount++;
                
                // Создаем новый абзац каждые 3-4 предложения или когда достигаем 400 символов
                if ($sentenceCount >= 3 || strlen($currentParagraph) >= 400) {
                    $paragraphs[] = trim($currentParagraph);
                    $currentParagraph = '';
                    $sentenceCount = 0;
                }
            }
            
            // Добавляем остаток
            if (!empty(trim($currentParagraph))) {
                $paragraphs[] = trim($currentParagraph);
            }
        }
        
        return array_filter($paragraphs, function($p) {
            return !empty(trim($p));
        });
    }

    private function saveDocument(Document $document): string
    {
        // Определяем путь для сохранения
        $baseDirectory = 'documents/' . date('Y/m/d');
        $filename = $document->id . '_' . time() . '.docx';
        $fullPath = $baseDirectory . '/' . $filename;
        
        // Создаем директорию, если она не существует
        Storage::disk('public')->makeDirectory($baseDirectory);
        
        // Сохраняем документ
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save(storage_path('app/public/' . $fullPath));

        return storage_path('app/public/' . $fullPath);
    }

    /**
     * Генерирует безопасное имя файла на основе названия документа
     */
    private function generateSafeFileName(string $title): string
    {
        // Убираем лишние пробелы и приводим к нижнему регистру
        $title = trim($title);
        
        // Заменяем кириллицу на латиницу (транслитерация)
        $transliterated = $this->transliterate($title);
        
        // Создаем slug (безопасное имя файла)
        $slug = Str::slug($transliterated, '_');
        
        // Ограничиваем длину имени файла (максимум 50 символов + расширение)
        if (strlen($slug) > 50) {
            $slug = substr($slug, 0, 50);
        }
        
        // Если slug пустой или содержит только подчеркивания, используем fallback
        if (empty($slug) || preg_match('/^_+$/', $slug)) {
            $slug = 'document_' . time();
        }
        
        return $slug . '.docx';
    }

    /**
     * Транслитерация кириллицы в латиницу
     */
    private function transliterate(string $text): string
    {
        $translitMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];
        
        return strtr($text, $translitMap);
    }
} 