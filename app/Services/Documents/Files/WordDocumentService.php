<?php

namespace App\Services\Documents\Files;

use App\Models\Document;
use App\Services\Files\FilesService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\LineSpacingRule;
use Illuminate\Support\Facades\Storage;

class WordDocumentService
{
    private PhpWord $phpWord;
    private FilesService $filesService;

    public function __construct(FilesService $filesService)
    {
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
        try {
            // Логируем начало генерации
            \Illuminate\Support\Facades\Log::info('Начало генерации Word документа', [
                'document_id' => $document->id,
                'document_title' => $document->title,
                'has_content' => !empty($document->content),
                'has_structure' => !empty($document->structure),
                'content_topics_count' => isset($document->content['topics']) ? count($document->content['topics']) : 0
            ]);
            
            // Создаем новый экземпляр PhpWord для каждой генерации
            $this->phpWord = new PhpWord();
            
            // Устанавливаем правильную кодировку и настройки для русского языка
            $this->phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('ru-RU'));
            
            // Устанавливаем дополнительные настройки документа
            $this->phpWord->getDocInfo()->setTitle($this->decodeUnicodeString($document->title));
            $this->phpWord->getDocInfo()->setCreator($document->user->name);
            $this->phpWord->getDocInfo()->setDescription('Документ сгенерирован системой GPTPult');
            
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

            // Определяем расширение и mime-type файла
            $isTextFile = str_ends_with($filePath, '.txt');
            $filename = $document->title . ($isTextFile ? '.txt' : '.docx');
            $mimeType = $isTextFile ? 'text/plain' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

            // Создание записи о файле
            $file = $this->filesService->createFileFromPath(
                $filePath,
                $document->user,
                $filename,
                $document->id
            );
            
            \Illuminate\Support\Facades\Log::info('Документ успешно сгенерирован', [
                'document_id' => $document->id,
                'file_id' => $file->id,
                'file_type' => $isTextFile ? 'text' : 'word',
                'file_size' => file_exists($filePath) ? filesize($filePath) : 0
            ]);
            
            return $file;
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при генерации Word документа', [
                'document_id' => $document->id,
                'error_message' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function setupStyles(): void
    {
        // Стиль для заголовков глав
        $this->phpWord->addTitleStyle(1, [
            'bold' => true, 
            'size' => 16, 
            'color' => '000000',
            'name' => 'Times New Roman'
        ], [
            'spaceAfter' => 240,
            'spaceBefore' => 240,
            'alignment' => Jc::LEFT
        ]);
        
        // Стиль для заголовков подглав
        $this->phpWord->addTitleStyle(2, [
            'bold' => true, 
            'size' => 14, 
            'color' => '000000',
            'name' => 'Times New Roman'
        ], [
            'spaceAfter' => 200,
            'spaceBefore' => 200,
            'alignment' => Jc::LEFT
        ]);
        
        // Стиль для подразделов
        $this->phpWord->addTitleStyle(3, [
            'bold' => true, 
            'size' => 12, 
            'color' => '000000',
            'name' => 'Times New Roman'
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
            'spaceAfter' => 120,
            'spaceBefore' => 0,
            'hangingIndent' => 567, // 1 см в твипах для висячего отступа
            'lineSpacing' => 1.0,
            'lineSpacingRule' => LineSpacingRule::AUTO
        ]);

        // Стиль для описаний источников
        $this->phpWord->addParagraphStyle('ReferenceDescription', [
            'alignment' => Jc::BOTH,
            'spaceAfter' => 160,
            'leftIndent' => 1134, // 2 см отступ слева
            'lineSpacing' => 1.0,
            'lineSpacingRule' => LineSpacingRule::AUTO
        ]);
    }

    /**
     * Создает титульный лист по ГОСТ 7.32-2017 и ГОСТ 2.105-95
     * Содержит заглушки для заполнения пользователем:
     * - Наименование университета
     * - Институт/факультет и кафедра
     * - Курс и группа студента
     * - ФИО студента и руководителя
     * - Ученая степень и звание руководителя
     * - Город
     */
    private function createTitlePage(Document $document): void
    {
        $section = $this->phpWord->addSection([
            'breakType' => 'nextPage',
            'marginTop' => 850,    // 3 см сверху в твипах (1 см = 567 твипов)
            'marginBottom' => 850, // 3 см снизу
            'marginLeft' => 850,   // 3 см слева
            'marginRight' => 567   // 2 см справа (по ГОСТ)
        ]);
        
        // Добавляем footer с городом и годом (только для титульного листа)
        $footer = $section->addFooter();
        $year = date('Y');
        $footer->addText(
            '[Город] ' . $year,
            ['size' => 14, 'name' => 'Times New Roman', 'color' => '0066CC'],
            ['alignment' => Jc::CENTER]
        );
        
        // Верхняя часть - наименование министерства и организации
        $section->addText(
            'МИНИСТЕРСТВО НАУКИ И ВЫСШЕГО ОБРАЗОВАНИЯ РОССИЙСКОЙ ФЕДЕРАЦИИ',
            ['size' => 12, 'bold' => true, 'allCaps' => true, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );
        
        $section->addText(
            'Федеральное государственное бюджетное образовательное учреждение',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );
        
        $section->addText(
            'высшего образования',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );
        
        $section->addText(
            '«[НАИМЕНОВАНИЕ УНИВЕРСИТЕТА]»',
            ['size' => 12, 'bold' => true, 'name' => 'Times New Roman', 'color' => '0066CC'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );
        
        $section->addText(
            '[Наименование института/факультета]',
            ['size' => 12, 'name' => 'Times New Roman', 'color' => '0066CC'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );
        
        $section->addText(
            'Кафедра [наименование кафедры]',
            ['size' => 12, 'name' => 'Times New Roman', 'color' => '0066CC'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );
        
        // Отступ перед основной частью
        $section->addTextBreak(2);
        
        // Тип работы
        $documentType = $document->documentType->name ?? 'КУРСОВАЯ РАБОТА';
        $section->addText(
            strtoupper($documentType),
            ['size' => 16, 'bold' => true, 'allCaps' => true, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );
        
        // Тема работы
        $documentTitle = $document->structure['document_title'] ?? $document->title;
        $decodedTitle = $this->decodeUnicodeString($documentTitle);
        $section->addText(
            'по теме:',
            ['size' => 14, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );
        
        $section->addText(
            '«' . $decodedTitle . '»',
            ['size' => 14, 'bold' => true, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 300]
        );
        
        // Отступ перед нижней частью
        $section->addTextBreak(3);
        
        // Правая часть - сведения об исполнителе и руководителе
        // Используем стиль с правым выравниванием
        $rightAlignStyle = [
            'alignment' => Jc::RIGHT,
            'spaceAfter' => 120,
            'leftIndent' => 7000 // Отступ слева для размещения справа
        ];
        
        // Сведения об исполнителе
        $section->addText(
            'Выполнил:',
            ['size' => 12, 'name' => 'Times New Roman'],
            $rightAlignStyle
        );
        
        $section->addText(
            'студент [курс] курса группы [номер группы]',
            ['size' => 12, 'name' => 'Times New Roman', 'color' => '0066CC'],
            $rightAlignStyle
        );
        
        $section->addText(
            '___________________ [Фамилия И.О.]',
            ['size' => 12, 'name' => 'Times New Roman', 'color' => '0066CC'],
            array_merge($rightAlignStyle, ['spaceAfter' => 200])
        );
        
        // Сведения о руководителе
        $section->addText(
            'Руководитель:',
            ['size' => 12, 'name' => 'Times New Roman'],
            $rightAlignStyle
        );
        
        $section->addText(
            '[ученая степень, ученое звание]',
            ['size' => 12, 'name' => 'Times New Roman', 'color' => '0066CC'],
            $rightAlignStyle
        );
        
        $section->addText(
            '___________________ [Фамилия И.О.]',
            ['size' => 12, 'name' => 'Times New Roman', 'color' => '0066CC'],
            array_merge($rightAlignStyle, ['spaceAfter' => 200])
        );
        
        // Оценка (если нужно)
        $section->addText(
            'Оценка: ___________________',
            ['size' => 12, 'name' => 'Times New Roman'],
            $rightAlignStyle
        );
        
        $section->addText(
            'Дата: _____________________',
            ['size' => 12, 'name' => 'Times New Roman'],
            array_merge($rightAlignStyle, ['spaceAfter' => 200])
        );
    }

    private function createTableOfContents(Document $document): void
    {
        $section = $this->phpWord->addSection([
            'breakType' => 'nextPage'
        ]);
        
        // Добавляем footer с номером страницы
        $footer = $section->addFooter();
        $footer->addPreserveText(
            '{PAGE}',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER]
        );
        
        // Заголовок содержания
        $section->addText(
            'СОДЕРЖАНИЕ',
            ['bold' => true, 'size' => 16, 'allCaps' => true, 'name' => 'Times New Roman'],
            'TOCHeading'
        );
        
        // Получаем структуру документа из поля structure
        $contentData = $document->structure['contents'] ?? [];
        
        if (!empty($contentData)) {
            $pageNumber = 3; // Начинаем с 3 страницы (1-титул, 2-содержание, 3-начало текста)
            
            foreach ($contentData as $index => $topic) {
                // Основная глава
                $chapterNumber = $index + 1;
                $decodedTopicTitle = $this->decodeUnicodeString($topic['title'] ?? '');
                $tocLine = "{$chapterNumber}. {$decodedTopicTitle}";
                
                $section->addText(
                    $tocLine,
                    ['size' => 12, 'bold' => true, 'name' => 'Times New Roman'],
                    'TOCItem'
                );
                
                // Подглавы
                if (!empty($topic['subtopics'])) {
                    foreach ($topic['subtopics'] as $subIndex => $subtopic) {
                        $subNumber = $subIndex + 1;
                        $decodedSubtopicTitle = $this->decodeUnicodeString($subtopic['title'] ?? '');
                        $subTocLine = "  {$chapterNumber}.{$subNumber}. {$decodedSubtopicTitle}";
                        
                        $section->addText(
                            $subTocLine,
                            ['size' => 11, 'name' => 'Times New Roman'],
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
        
        // Добавляем footer с номером страницы
        $footer = $section->addFooter();
        $footer->addPreserveText(
            '{PAGE}',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER]
        );
        
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
                ['size' => 12, 'name' => 'Times New Roman'],
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
            
            // Декодируем заголовок главы
            $chapterTitle = $this->decodeUnicodeString($topic['title'] ?? '');
            
            // Заголовок главы (H1)
            $section->addTitle(
                "{$chapterNumber}. {$chapterTitle}",
                1
            );
            $section->addTextBreak(1);
            
            // Подглавы
            if (!empty($topic['subtopics'])) {
                foreach ($topic['subtopics'] as $subIndex => $subtopic) {
                    $subNumber = $subIndex + 1;
                    
                    // Декодируем заголовок подглавы
                    $subtopicTitle = $this->decodeUnicodeString($subtopic['title'] ?? '');
                    
                    // Заголовок подглавы (H2)
                    $section->addTitle(
                        "{$chapterNumber}.{$subNumber}. {$subtopicTitle}",
                        2
                    );
                    $section->addTextBreak(1);
                    
                    // Содержимое подглавы
                    if (isset($subtopic['content']) && !empty($subtopic['content'])) {
                        // Декодируем содержимое
                        $content = $this->decodeUnicodeString($subtopic['content']);
                        
                        // Разбиваем длинный текст на абзацы
                        $paragraphs = $this->splitTextIntoParagraphs($content);
                        
                        foreach ($paragraphs as $paragraph) {
                            if (trim($paragraph)) {
                                $section->addText(
                                    trim($paragraph),
                                    ['size' => 12, 'name' => 'Times New Roman'],
                                    'Normal'
                                );
                                $section->addTextBreak(1);
                            }
                        }
                    } else {
                        // Fallback если контент не сгенерирован
                        $section->addText(
                            'Содержимое данного раздела будет добавлено позже.',
                            ['size' => 12, 'italic' => true, 'name' => 'Times New Roman'],
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
        
        // Добавляем footer с номером страницы
        $footer = $section->addFooter();
        $footer->addPreserveText(
            '{PAGE}',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER]
        );
        
        // Заголовок списка источников согласно ГОСТ
        $section->addTitle('СПИСОК ИСПОЛЬЗОВАННЫХ ИСТОЧНИКОВ', 1);
        $section->addTextBreak(2);
        
        // Получаем источники из структуры документа
        $references = $document->structure['references'] ?? [];
        
        if (!empty($references)) {
            foreach ($references as $index => $reference) {
                $referenceNumber = $index + 1;
                
                // Декодируем Unicode escape-последовательности в данных источника
                $decodedReference = $this->decodeUnicodeReference($reference);
                
                // Формируем библиографическое описание по ГОСТ
                $referenceText = $this->formatReferenceByGost($referenceNumber, $decodedReference);
                
                // Основная ссылка
                $section->addText(
                    trim($referenceText),
                    ['size' => 12, 'name' => 'Times New Roman'],
                    'References'
                );
                
                // Описание релевантности (если есть) - добавляем как отдельный абзац с отступом
                if (isset($decodedReference['description']) && !empty($decodedReference['description'])) {
                    $section->addText(
                        trim($decodedReference['description']),
                        ['size' => 11, 'italic' => true, 'color' => '666666', 'name' => 'Times New Roman'],
                        'ReferenceDescription'
                    );
                }
                
                // Добавляем небольшой отступ между источниками
                $section->addTextBreak(1);
            }
        } else {
            // Если источников нет, добавляем пример оформления по ГОСТ
            $exampleReferences = $this->getExampleReferences();
            
            foreach ($exampleReferences as $index => $reference) {
                $referenceNumber = $index + 1;
                
                // Основная ссылка
            $section->addText(
                    $reference,
                    ['size' => 12, 'name' => 'Times New Roman'],
                    'References'
                );
                
                // Добавляем небольшой отступ между источниками
                $section->addTextBreak(1);
            }
        }
    }

    /**
     * Декодирует Unicode escape-последовательности в данных источника
     */
    private function decodeUnicodeReference(array $reference): array
    {
        $decoded = [];
        
        foreach ($reference as $key => $value) {
            if (is_string($value)) {
                // Декодируем Unicode escape-последовательности
                $decoded[$key] = $this->decodeUnicodeString($value);
            } elseif (is_array($value)) {
                // Рекурсивно обрабатываем массивы
                $decoded[$key] = $this->decodeUnicodeReference($value);
            } else {
                $decoded[$key] = $value;
            }
        }
        
        return $decoded;
    }

    /**
     * Декодирует Unicode escape-последовательности в строке
     */
    private function decodeUnicodeString(string $text): string
    {
        if (empty($text)) {
            return $text;
        }
        
        // Если в тексте нет Unicode escape-последовательностей, просто очищаем и возвращаем
        if (strpos($text, '\u') === false) {
            return $this->sanitizeForXml($text);
        }
        
        $decoded = $text;
        
        // Пробуем декодировать через json_decode (самый надёжный способ)
        if (strpos($text, '\u') !== false) {
            $jsonDecoded = json_decode('"' . addslashes($text) . '"', true);
            if ($jsonDecoded !== null && $jsonDecoded !== $text) {
                $decoded = $jsonDecoded;
            }
        }
        
        // Если json_decode не сработал, пробуем простую замену
        if ($decoded === $text && strpos($text, '\u') !== false) {
            $decoded = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
                $codepoint = hexdec($matches[1]);
                // Проверяем, что код символа корректный
                if ($codepoint >= 0 && $codepoint <= 0x10FFFF) {
                    return mb_chr($codepoint, 'UTF-8');
                }
                return ''; // Убираем некорректные символы
            }, $text);
        }
        
        // Убираем лишние escape-символы
        $decoded = stripslashes($decoded);
        
        // Очищаем от символов, которые могут вызвать проблемы с XML
        return $this->sanitizeForXml($decoded);
    }

    /**
     * Очищает текст от символов, которые могут вызвать ошибки XML парсинга
     */
    private function sanitizeForXml(string $text): string
    {
        if (empty($text)) {
            return $text;
        }
        
        // Убираем проблемные управляющие символы (кроме табуляции \t, перевода строки \n и возврата каретки \r)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Убираем только явно проблемные символы, не используя Unicode ranges
        $text = str_replace(["\xEF\xBF\xBE", "\xEF\xBF\xBF"], '', $text); // FFFE и FFFF
        
        // Заменяем основные XML символы на безопасные эквиваленты
        $replacements = [
            '&' => '&amp;',
            '<' => '&lt;', 
            '>' => '&gt;',
            '"' => '&quot;',
            "'" => '&apos;'
        ];
        
        // Применяем замены только если символы не являются частью уже экранированных последовательностей
        foreach ($replacements as $char => $replacement) {
            if ($char === '&') {
                // Для амперсанда проверяем, что он не является частью уже экранированной последовательности
                $text = preg_replace('/&(?!(?:amp|lt|gt|quot|apos);)/', $replacement, $text);
            } else {
                $text = str_replace($char, $replacement, $text);
            }
        }
        
        // Убираем множественные пробелы, но сохраняем переносы строк
        $text = preg_replace('/[ \t]+/', ' ', $text);
        // Нормализуем переносы строк - убираем множественные, но сохраняем структуру
        $text = preg_replace('/\n{3,}/', "\n\n", $text); // Не более двух переносов подряд
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text); // Убираем лишние пробелы между переносами
        
        return trim($text);
    }

    /**
     * Форматирует библиографическое описание источника по ГОСТ
     */
    private function formatReferenceByGost(int $number, array $reference): string
    {
        $type = $reference['type'] ?? 'website';
        
        switch ($type) {
            case 'book':
                return $this->formatBookByGost($number, $reference);
            case 'article':
                return $this->formatArticleByGost($number, $reference);
            case 'website':
            case 'pdf':
            default:
                return $this->formatElectronicResourceByGost($number, $reference);
        }
    }

    /**
     * Форматирует описание книги по ГОСТ
     * Формат: Автор, И. О. Название книги : сведения об издании / И. О. Автор. — Место издания : Издательство, Год. — Количество страниц с. — ISBN. — Текст : непосредственный.
     */
    private function formatBookByGost(int $number, array $reference): string
    {
        $result = "{$number}. ";
        
        // Автор (фамилия, инициалы) - только если указан
        $hasAuthor = !empty($reference['author']) && 
                     $reference['author'] !== 'не указан' && 
                     $reference['author'] !== 'не указана' &&
                     !stripos($reference['author'], 'не указан');
        
        if ($hasAuthor) {
            $author = $this->formatAuthorName($reference['author']);
            if (!empty($author)) {
                $result .= $author . ' ';
            }
        }
        
        // Основное заглавие
        if (!empty($reference['title'])) {
            $result .= $reference['title'];
            
            // Сведения, относящиеся к заглавию (подзаголовок)
            if (!empty($reference['subtitle'])) {
                $result .= ' : ' . $reference['subtitle'];
            }
            
            // Добавляем сведения об ответственности только если есть автор
            if ($hasAuthor) {
                $authorName = $this->formatAuthorName($reference['author']);
                if (!empty($authorName)) {
                    $result .= ' / ' . $authorName;
                }
            }
            
            $result .= '. — ';
        }
        
        // Место издания
        $place = $reference['place'] ?? $reference['city'] ?? 'М.';
        if ($place !== 'не указан' && $place !== 'не указана') {
            $result .= $place . ' : ';
        } else {
            $result .= 'М. : ';
        }
        
        // Издательство
        $publisher = $this->extractPublisherFromReference($reference);
        $result .= $publisher . ', ';
        
        // Год издания
        $year = $this->extractYear($reference);
        $result .= $year . '. — ';
        
        // Количество страниц
        if (!empty($reference['pages']) && $reference['pages'] !== 'не указан') {
            $pages = $reference['pages'];
            if (!str_contains($pages, 'с.')) {
                $pages .= ' с.';
            }
            $result .= $pages . ' — ';
        }
        
        // ISBN
        if (!empty($reference['isbn']) && $reference['isbn'] !== 'не указан') {
            $result .= 'ISBN ' . $reference['isbn'] . '. — ';
        }
        
        // Обозначение материала
        $result .= 'Текст : непосредственный.';
        
        return $result;
    }

    /**
     * Извлекает год из различных полей источника
     */
    private function extractYear(array $reference): string
    {
        $year = $reference['year'] ?? $reference['publication_date'] ?? '';
        
        if (empty($year) || $year === 'не указан' || $year === 'не указана') {
            return date('Y');
        }
        
        // Извлекаем год из даты, если это полная дата
        if (preg_match('/(\d{4})/', $year, $matches)) {
            return $matches[1];
        }
        
        return date('Y');
    }

    /**
     * Пытается извлечь издательство из данных источника
     */
    private function extractPublisherFromReference(array $reference): string
    {
        // Если издательство указано явно
        if (!empty($reference['publisher']) && 
            $reference['publisher'] !== 'не указан' && 
            $reference['publisher'] !== 'не указана') {
            return $reference['publisher'];
        }
        
        // Пытаемся извлечь из URL
        $url = $reference['url'] ?? '';
        if (strpos($url, 'cyberleninka.ru') !== false) {
            return 'КиберЛенинка';
        } elseif (strpos($url, 'elibrary.ru') !== false) {
            return 'eLIBRARY';
        } elseif (strpos($url, 'djvu.online') !== false) {
            return 'DJVU Online';
        } elseif (strpos($url, 'studylib.ru') !== false) {
            return 'StudyLib';
        } elseif (strpos($url, 'studfile.net') !== false) {
            return 'StudFile';
        } elseif (strpos($url, 'arxiv.org') !== false) {
            return 'arXiv';
        } elseif (strpos($url, 'researchgate.net') !== false) {
            return 'ResearchGate';
        } elseif (strpos($url, 'academia.edu') !== false) {
            return 'Academia.edu';
        } elseif (strpos($url, 'scholar.google') !== false) {
            return 'Google Scholar';
        }
        
        // Попытка извлечь из site_name
        if (!empty($reference['site_name']) && 
            $reference['site_name'] !== 'не указан' && 
            $reference['site_name'] !== 'не указана') {
            return $reference['site_name'];
        }
        
        // По умолчанию
        return '[б. и.]'; // без издательства по ГОСТ
    }

    /**
     * Форматирует описание статьи по ГОСТ
     * Формат: Автор, И. О. Название статьи / И. О. Автор // Название журнала. — Год. — № номер. — С. страницы. — Текст : непосредственный.
     */
    private function formatArticleByGost(int $number, array $reference): string
    {
        $result = "{$number}. ";
        
        // Автор
        $hasAuthor = !empty($reference['author']) && 
                     $reference['author'] !== 'не указан' && 
                     $reference['author'] !== 'не указана' &&
                     !stripos($reference['author'], 'не указан');
        
        if ($hasAuthor) {
            $author = $this->formatAuthorName($reference['author']);
            if (!empty($author)) {
                $result .= $author . ' ';
            }
        }
        
        // Название статьи
        if (!empty($reference['title'])) {
            $result .= $reference['title'];
            
            // Автор после названия
            if ($hasAuthor) {
                $authorName = $this->formatAuthorName($reference['author']);
                if (!empty($authorName)) {
                    $result .= ' / ' . $authorName;
                }
            }
            
            $result .= ' // ';
        }
        
        // Название журнала/сборника
        $journal = $reference['journal'] ?? $reference['site_name'] ?? '';
        if (!empty($journal) && $journal !== 'не указан' && $journal !== 'не указана') {
            $result .= $journal . '. — ';
        } else {
            $result .= '[Журнал не указан]. — ';
        }
        
        // Год
        $year = $this->extractYear($reference);
        $result .= $year . '. — ';
        
        // Том
        if (!empty($reference['volume']) && $reference['volume'] !== 'не указан') {
            $result .= 'Т. ' . $reference['volume'] . '. — ';
        }
        
        // Номер выпуска
        if (!empty($reference['issue']) && $reference['issue'] !== 'не указан') {
            $result .= '№ ' . $reference['issue'] . '. — ';
        }
        
        // Страницы
        if (!empty($reference['pages']) && $reference['pages'] !== 'не указан') {
            $pages = $reference['pages'];
            if (!str_contains($pages, 'С.') && !str_contains($pages, 'с.')) {
                $pages = 'С. ' . $pages;
            }
            $result .= $pages . '. — ';
        }
        
        // Обозначение материала
        $result .= 'Текст : непосредственный.';
        
        return $result;
    }

    /**
     * Форматирует описание электронного ресурса по ГОСТ
     * Формат: Автор, И. О. Название / И. О. Автор. — Текст : электронный // Название сайта. — URL: адрес (дата обращения: ДД.ММ.ГГГГ).
     */
    private function formatElectronicResourceByGost(int $number, array $reference): string
    {
        $result = "{$number}. ";
        
        // Автор
        $hasAuthor = !empty($reference['author']) && 
                     $reference['author'] !== 'не указан' && 
                     $reference['author'] !== 'не указана' &&
                     !stripos($reference['author'], 'не указан');
        
        if ($hasAuthor) {
            $author = $this->formatAuthorName($reference['author']);
            if (!empty($author)) {
                $result .= $author . ' ';
            }
        }
        
        // Название
        if (!empty($reference['title'])) {
            $result .= $reference['title'];
            
            // Автор после названия (если есть)
            if ($hasAuthor) {
                $authorName = $this->formatAuthorName($reference['author']);
                if (!empty($authorName)) {
                    $result .= ' / ' . $authorName;
                }
            }
            
            $result .= '. — ';
        }
        
        // Обозначение материала для электронных ресурсов
        $result .= 'Текст : электронный';
        
        // Название сайта/источника
        $siteName = $reference['site_name'] ?? $this->extractSiteNameFromUrl($reference['url'] ?? '');
        
        if (!empty($siteName) && $siteName !== 'не указан' && $siteName !== 'не указана') {
            $result .= ' // ' . $siteName . '. — ';
        } else {
            $result .= '. — ';
        }
        
        // URL
        if (!empty($reference['url'])) {
            $result .= 'URL: ' . $reference['url'];
            
            // Дата обращения
            $accessDate = $reference['access_date'] ?? date('d.m.Y');
            // Проверяем формат даты
            if (!preg_match('/\d{2}\.\d{2}\.\d{4}/', $accessDate)) {
                $accessDate = date('d.m.Y');
            }
            $result .= ' (дата обращения: ' . $accessDate . ').';
        } else {
            $result .= 'URL не указан.';
        }
        
        return $result;
    }

    /**
     * Извлекает название сайта из URL
     */
    private function extractSiteNameFromUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return '';
        }
        
        // Убираем www. если есть
        $host = preg_replace('/^www\./', '', $host);
        
        // Специальные случаи для известных сайтов
        $knownSites = [
            'cyberleninka.ru' => 'КиберЛенинка',
            'elibrary.ru' => 'eLIBRARY',
            'scholar.google.com' => 'Google Scholar',
            'scholar.google.ru' => 'Google Scholar',
            'researchgate.net' => 'ResearchGate',
            'academia.edu' => 'Academia.edu',
            'arxiv.org' => 'arXiv',
            'studylib.ru' => 'StudyLib',
            'studfile.net' => 'StudFile',
            'djvu.online' => 'DJVU Online'
        ];
        
        return $knownSites[$host] ?? ucfirst(str_replace(['.ru', '.com', '.org', '.net'], '', $host));
    }

    /**
     * Форматирует имя автора для библиографического описания
     * Преобразует "Фамилия И.О." в "Фамилия, И. О." согласно ГОСТ
     */
    private function formatAuthorName(string $author): string
    {
        // Обработка пустых или невалидных значений
        if (empty($author) || 
            $author === 'не указан' || 
            $author === 'не указана' ||
            stripos($author, 'не указан') !== false) {
            return '';
        }
        
        // Если автор уже в правильном формате, возвращаем как есть
        if (strpos($author, ',') !== false && preg_match('/[А-ЯA-Z]\.\s+[А-ЯA-Z]\./', $author)) {
            return $author;
        }
        
        // Обработка случая с несколькими авторами через запятую или точку с запятой
        if (preg_match('/[,;]/', $author) && !preg_match('/[А-ЯA-Z]\.\s+[А-ЯA-Z]\./', $author)) {
            $authors = preg_split('/[,;]\s*/', $author);
            $formattedAuthors = [];
            foreach ($authors as $singleAuthor) {
                $formatted = $this->formatSingleAuthor(trim($singleAuthor));
                if (!empty($formatted)) {
                    $formattedAuthors[] = $formatted;
                }
            }
            
            // Для ГОСТ при нескольких авторах берем только первого, остальных указываем как [и др.]
            if (count($formattedAuthors) > 1) {
                return $formattedAuthors[0] . ' [и др.]';
            }
            
            return implode(', ', $formattedAuthors);
        }
        
        return $this->formatSingleAuthor($author);
    }
    
    /**
     * Форматирует одного автора
     */
    private function formatSingleAuthor(string $author): string
    {
        $author = trim($author);
        
        if (empty($author)) {
            return '';
        }
        
        // Разбиваем на части по пробелам
        $parts = preg_split('/\s+/', $author);
        
        if (count($parts) < 2) {
            return $author;
        }
        
        $surname = array_shift($parts);
        $restNames = implode(' ', $parts);
        
        // Преобразуем имена в инициалы
        $initials = $this->convertNamesToInitials($restNames);
        
        if (!empty($initials)) {
            return $surname . ', ' . $initials;
        }
        
        return $surname;
    }

    /**
     * Преобразует имена в инициалы согласно ГОСТ
     */
    private function convertNamesToInitials(string $names): string
    {
        // Если уже есть инициалы, обрабатываем их
        if (preg_match_all('/[А-ЯЁA-Z]/u', $names, $matches)) {
            $initials = [];
            foreach ($matches[0] as $initial) {
                $initials[] = $initial . '.';
            }
            return implode(' ', $initials);
        }
        
        // Если полные имена, берем первые буквы
        $nameParts = preg_split('/\s+/', trim($names));
        $initials = [];
        
        foreach ($nameParts as $namePart) {
            if (!empty($namePart)) {
                $firstChar = mb_substr($namePart, 0, 1, 'UTF-8');
                if (preg_match('/[А-ЯЁA-Z]/u', $firstChar)) {
                    $initials[] = $firstChar . '.';
                }
            }
        }
        
        return implode(' ', $initials);
    }

    /**
     * Возвращает примеры оформления источников по ГОСТ для демонстрации
     */
    private function getExampleReferences(): array
    {
        return [
            '1. Иванов, И. И. Основы научного исследования : учебное пособие / И. И. Иванов. — М. : Академия, 2023. — 256 с. — ISBN 978-5-7695-9876-5. — Текст : непосредственный.',
            '2. Петрова, А. С. Методика написания курсовых работ / А. С. Петрова // Вестник образования. — 2023. — № 12. — С. 45-52. — Текст : непосредственный.',
            '3. Сидоров, В. П. Современные технологии обучения / В. П. Сидоров. — Текст : электронный // Образовательный портал. — URL: https://edu-portal.ru/technologies (дата обращения: ' . date('d.m.Y') . ').',
            '4. Козлова, Е. Н. Анализ данных в научных исследованиях / Е. Н. Козлова // КиберЛенинка. — 2023. — Т. 15. — № 3. — С. 128-145. — Текст : непосредственный.',
            '5. Новиков, А. В. Практическое руководство по написанию работ / А. В. Новиков. — Текст : электронный // ResearchGate. — URL: https://researchgate.net/publication/example (дата обращения: ' . date('d.m.Y') . ').'
        ];
    }

    /**
     * Разбивает текст на абзацы
     */
    private function splitTextIntoParagraphs(string $text): array
    {
        // Убираем лишние пробелы и переносы
        $text = trim($text);
        
        // Обрабатываем специальные символы форматирования
        $text = $this->processFormattingMarkers($text);
        
        // Очищаем текст от проблемных символов
        $text = $this->sanitizeForXml($text);
        
        // Сначала разбиваем по двойным переносам строк (четкие границы абзацев)
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        // Обрабатываем каждый блок текста
        $finalParagraphs = [];
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                continue;
            }
            
            // Если в абзаце есть одиночные переходы на новую строку, обрабатываем их
            if (strpos($paragraph, "\n") !== false) {
                // Разбиваем по одиночным переходам на новую строку
                $lines = explode("\n", $paragraph);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && strlen($line) > 3) {
                        $finalParagraphs[] = $line;
                    }
                }
            } else {
                // Если абзац длинный и без переносов, разбиваем по точкам
                if (strlen($paragraph) > 500) {
                    $sentences = preg_split('/\.\s+/', $paragraph);
                    $currentParagraph = '';
                    $sentenceCount = 0;
                    
                    foreach ($sentences as $sentence) {
                        $sentence = trim($sentence);
                        if (empty($sentence)) continue;
                        
                        $currentParagraph .= $sentence . '. ';
                        $sentenceCount++;
                        
                        // Создаем новый абзац каждые 3-4 предложения или когда достигаем 400 символов
                        if ($sentenceCount >= 3 || strlen($currentParagraph) >= 400) {
                            $finalParagraphs[] = trim($currentParagraph);
                            $currentParagraph = '';
                            $sentenceCount = 0;
                        }
                    }
                    
                    // Добавляем остаток
                    if (!empty(trim($currentParagraph))) {
                        $finalParagraphs[] = trim($currentParagraph);
                    }
                } else {
                    // Короткий абзац добавляем как есть
                    $finalParagraphs[] = $paragraph;
                }
            }
        }
        
        // Фильтруем пустые абзацы и возвращаем результат
        return array_filter($finalParagraphs, function($p) {
            return !empty(trim($p)) && strlen(trim($p)) > 3;
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
        
        $fullSavePath = storage_path('app/public/' . $fullPath);
        
        try {
            // Сохраняем документ с правильными настройками
            $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
            $objWriter->save($fullSavePath);
            
            \Illuminate\Support\Facades\Log::info('Word документ успешно сохранен', [
                'document_id' => $document->id,
                'file_path' => $fullSavePath,
                'file_size' => file_exists($fullSavePath) ? filesize($fullSavePath) : 0
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при сохранении Word документа', [
                'document_id' => $document->id,
                'error_message' => $e->getMessage(),
                'error_type' => get_class($e),
                'file_path' => $fullSavePath,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Если это ошибка XML парсинга или ZIP архива, попробуем создать упрощенную версию
            if (strpos($e->getMessage(), 'SAXParseException') !== false || 
                strpos($e->getMessage(), 'fastparser') !== false ||
                strpos($e->getMessage(), 'WriterFilter') !== false ||
                strpos($e->getMessage(), 'Could not close zip file') !== false ||
                strpos($e->getMessage(), 'zip file') !== false) {
                
                \Illuminate\Support\Facades\Log::warning('Обнаружена ошибка XML парсинга или ZIP архива, создаем упрощенную версию документа', [
                    'document_id' => $document->id,
                    'error_type' => get_class($e)
                ]);
                
                return $this->createSimplifiedDocument($document, $fullSavePath);
            }
            
            throw $e;
        }

        return $fullSavePath;
    }
    
    /**
     * Создает упрощенную версию документа в случае ошибок XML парсинга
     */
    private function createSimplifiedDocument(Document $document, string $fullSavePath): string
    {
        try {
            // Создаем новый простой документ
            $phpWord = new PhpWord();
            $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('ru-RU'));
            
            // Базовые стили
            $phpWord->addParagraphStyle('Normal', [
                'spaceAfter' => 120,
                'lineSpacing' => 1.5,
                'alignment' => Jc::BOTH
            ]);
            
            $section = $phpWord->addSection();
            
            // Добавляем заголовок документа (максимально безопасно)
            $title = $this->safeSanitizeText($document->title);
            if (!empty($title)) {
                $section->addText(
                    $title,
                    ['bold' => true, 'size' => 16, 'name' => 'Times New Roman'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 300]
                );
            }
            
            // Добавляем основной контент простым способом
            $contentData = $document->content['topics'] ?? [];
            
            if (!empty($contentData) && is_array($contentData)) {
                foreach ($contentData as $index => $topic) {
                    if (!is_array($topic)) continue;
                    
                    $chapterNumber = $index + 1;
                    $chapterTitle = $this->safeSanitizeText($topic['title'] ?? '');
                    
                    // Заголовок главы
                    if (!empty($chapterTitle)) {
                        $section->addText(
                            "{$chapterNumber}. {$chapterTitle}",
                            ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
                            'Normal'
                        );
                        $section->addTextBreak(1);
                    }
                    
                    // Подглавы
                    if (!empty($topic['subtopics']) && is_array($topic['subtopics'])) {
                        foreach ($topic['subtopics'] as $subIndex => $subtopic) {
                            if (!is_array($subtopic)) continue;
                            
                            $subNumber = $subIndex + 1;
                            $subtopicTitle = $this->safeSanitizeText($subtopic['title'] ?? '');
                            
                            if (!empty($subtopicTitle)) {
                                $section->addText(
                                    "{$chapterNumber}.{$subNumber}. {$subtopicTitle}",
                                    ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
                                    'Normal'
                                );
                                $section->addTextBreak(1);
                            }
                            
                            // Содержимое подглавы
                            if (isset($subtopic['content']) && !empty($subtopic['content'])) {
                                $content = $this->safeSanitizeText($subtopic['content']);
                                
                                if (!empty($content)) {
                                    // Улучшенное разделение на абзацы с поддержкой переходов на новую строку
                                    $paragraphs = $this->splitTextIntoParagraphsSimple($content);
                                    foreach ($paragraphs as $paragraph) {
                                        $paragraph = trim($paragraph);
                                        if (!empty($paragraph) && strlen($paragraph) > 3) {
                                            $section->addText(
                                                $paragraph,
                                                ['size' => 12, 'name' => 'Times New Roman'],
                                                'Normal'
                                            );
                                            $section->addTextBreak(1);
                                        }
                                    }
                                }
                            }
                            
                            $section->addTextBreak(1);
                        }
                    }
                    
                    $section->addTextBreak(2);
                }
            } else {
                // Если нет контента, добавляем заглушку
                $section->addText(
                    'Содержимое документа будет добавлено после завершения генерации.',
                    ['size' => 12, 'italic' => true, 'name' => 'Times New Roman'],
                    'Normal'
                );
            }
            
            // Сохраняем упрощенный документ
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($fullSavePath);
            
            \Illuminate\Support\Facades\Log::info('Упрощенный Word документ успешно создан', [
                'document_id' => $document->id,
                'file_path' => $fullSavePath
            ]);
            
            return $fullSavePath;
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при создании упрощенного документа', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            
            // Если и упрощенный документ не удается создать, создаем минимальный документ
            return $this->createMinimalDocument($document, $fullSavePath);
        }
    }
    
    /**
     * Простое разделение текста на абзацы для упрощенного документа
     */
    private function splitTextIntoParagraphsSimple(string $text): array
    {
        if (empty($text)) {
            return [];
        }
        
        // Убираем лишние пробелы
        $text = trim($text);
        
        // Обрабатываем специальные символы форматирования
        $text = $this->processFormattingMarkers($text);
        
        // Сначала разбиваем по двойным переносам строк
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        $finalParagraphs = [];
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                continue;
            }
            
            // Если в абзаце есть одиночные переходы на новую строку, разбиваем их
            if (strpos($paragraph, "\n") !== false) {
                $lines = explode("\n", $paragraph);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && strlen($line) > 3) {
                        $finalParagraphs[] = $line;
                    }
                }
            } else {
                // Добавляем абзац как есть
                if (strlen($paragraph) > 3) {
                    $finalParagraphs[] = $paragraph;
                }
            }
        }
        
        return $finalParagraphs;
    }

    /**
     * Обрабатывает специальные маркеры форматирования в тексте
     */
    private function processFormattingMarkers(string $text): string
    {
        // Обрабатываем различные варианты маркеров перехода на новую строку
        $newlineMarkers = [
            '\\n',           // Экранированный \n
            '<br>',          // HTML тег
            '<br/>',         // HTML тег с закрытием
            '<br />',        // HTML тег с пробелом
            '\r\n',          // Windows перевод строки
            '\r',            // Mac перевод строки
            '[новая строка]', // Текстовый маркер
            '[перевод строки]', // Текстовый маркер
            '[break]',       // Английский маркер
        ];
        
        // Заменяем все маркеры на обычный перевод строки
        foreach ($newlineMarkers as $marker) {
            $text = str_replace($marker, "\n", $text);
        }
        
        // Обрабатываем маркеры абзацев
        $paragraphMarkers = [
            '\\n\\n',        // Двойной экранированный \n
            '<p>',           // HTML тег параграфа
            '</p>',          // Закрывающий тег параграфа
            '[новый абзац]', // Текстовый маркер
            '[абзац]',       // Текстовый маркер
            '[paragraph]',   // Английский маркер
        ];
        
        // Заменяем маркеры абзацев на двойной перевод строки
        foreach ($paragraphMarkers as $marker) {
            $text = str_replace($marker, "\n\n", $text);
        }
        
        return $text;
    }

    /**
     * Максимально безопасная очистка текста
     */
    private function safeSanitizeText(string $text): string
    {
        if (empty($text)) {
            return '';
        }
        
        // Простая очистка без сложных регулярных выражений
        $text = trim($text);
        
        // Убираем явно проблемные символы
        $text = str_replace(["\0", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08"], '', $text);
        $text = str_replace(["\x0B", "\x0C", "\x0E", "\x0F", "\x10", "\x11", "\x12", "\x13", "\x14"], '', $text);
        $text = str_replace(["\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C", "\x1D"], '', $text);
        $text = str_replace(["\x1E", "\x1F", "\x7F"], '', $text);
        
        // Заменяем XML символы
        $text = str_replace('&', '&amp;', $text);
        $text = str_replace('<', '&lt;', $text);
        $text = str_replace('>', '&gt;', $text);
        $text = str_replace('"', '&quot;', $text);
        $text = str_replace("'", '&apos;', $text);
        
        // Убираем множественные пробелы
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Создает минимальный документ в случае критических ошибок
     */
    private function createMinimalDocument(Document $document, string $fullSavePath): string
    {
        try {
            // Создаем самый простой документ без сложных элементов
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            
            // Добавляем только заголовок документа
            $title = mb_substr($document->title, 0, 100, 'UTF-8'); // Ограничиваем длину
            $title = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $title); // Убираем все кроме букв, цифр, пробелов, дефисов и точек
            
            if (!empty($title)) {
                $section->addText($title, ['bold' => true, 'size' => 14]);
            }
            
            $section->addTextBreak(2);
            
            // Добавляем простое сообщение
            $section->addText(
                'Документ создан с упрощенной структурой из-за технических ограничений.',
                ['size' => 12]
            );
            
            $section->addTextBreak(1);
            $section->addText(
                'Для получения полной версии документа обратитесь к администратору.',
                ['size' => 12, 'italic' => true]
            );
            
            // Сохраняем минимальный документ
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($fullSavePath);
            
            \Illuminate\Support\Facades\Log::info('Минимальный Word документ создан', [
                'document_id' => $document->id,
                'file_path' => $fullSavePath
            ]);
            
            return $fullSavePath;
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Критическая ошибка: не удалось создать даже минимальный документ', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            
            // Если PhpWord полностью не работает, создаем текстовый файл как последний fallback
            return $this->createTextFallbackDocument($document, $fullSavePath);
        }
    }

    /**
     * Создает простой текстовый файл как последний fallback
     */
    private function createTextFallbackDocument(Document $document, string $fullSavePath): string
    {
        try {
            // Меняем расширение на .txt
            $textFilePath = str_replace('.docx', '.txt', $fullSavePath);
            
            // Подготавливаем содержимое текстового файла
            $title = mb_substr($document->title, 0, 100, 'UTF-8');
            $title = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $title);
            
            $content = "=== " . $title . " ===\n\n";
            $content .= "Документ создан с упрощенной структурой из-за технических ограничений.\n";
            $content .= "Для получения полной версии документа обратитесь к администратору.\n\n";
            $content .= "Дата создания: " . now()->format('d.m.Y H:i') . "\n";
            $content .= "ID документа: " . $document->id . "\n";
            
            // Если есть содержимое документа, добавляем его
            if (!empty($document->content)) {
                $content .= "\n=== СОДЕРЖИМОЕ ===\n\n";
                // Очищаем содержимое от HTML тегов и markdown
                $cleanContent = strip_tags($document->content);
                $cleanContent = preg_replace('/[^\p{L}\p{N}\s\-\.\,\!\?\:\;\(\)]/u', '', $cleanContent);
                $content .= mb_substr($cleanContent, 0, 5000, 'UTF-8') . "\n";
            }
            
            // Записываем файл
            file_put_contents($textFilePath, $content);
            
            \Illuminate\Support\Facades\Log::warning('Создан текстовый fallback файл вместо Word документа', [
                'document_id' => $document->id,
                'file_path' => $textFilePath,
                'file_size' => filesize($textFilePath)
            ]);
            
            return $textFilePath;
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Критическая ошибка: не удалось создать даже текстовый файл', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            
            throw new \Exception('Критическая ошибка при создании любого типа документа: ' . $e->getMessage());
        }
    }
} 