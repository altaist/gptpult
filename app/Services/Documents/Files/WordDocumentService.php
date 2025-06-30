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
            ['size' => 14, 'name' => 'Times New Roman'],
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
            ['size' => 12, 'bold' => true, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
        );
        
        $section->addText(
            '[Наименование института/факультета]',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );
        
        $section->addText(
            'Кафедра [наименование кафедры]',
            ['size' => 12, 'name' => 'Times New Roman'],
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
        $decodedTitle = $this->decodeUnicodeString($document->title);
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
        // Создаем невидимую таблицу для правильного выравнивания
        $table = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 0,
            'width' => 100 * 50, // 100% ширины
            'unit' => 'pct'
        ]);
        
        // Первая строка - пустая для отступа слева
        $table->addRow();
        $table->addCell(7000)->addText('', ['size' => 12, 'name' => 'Times New Roman']); // Левая пустая часть
        $rightCell = $table->addCell(6000); // Правая часть для информации
        
        // Сведения об исполнителе
        $rightCell->addText(
            'Выполнил:',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 120]
        );
        
        $rightCell->addText(
            'студент [курс] курса группы [номер группы]',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 120]
        );
        
        $rightCell->addText(
            '___________________ [Фамилия И.О.]',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 200]
        );
        
        // Сведения о руководителе
        $rightCell->addText(
            'Руководитель:',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 120]
        );
        
        $rightCell->addText(
            '[ученая степень, ученое звание]',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 120]
        );
        
        $rightCell->addText(
            '___________________ [Фамилия И.О.]',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 200]
        );
        
        // Оценка (если нужно)
        $rightCell->addText(
            'Оценка: ___________________',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 120]
        );
        
        $rightCell->addText(
            'Дата: _____________________',
            ['size' => 12, 'name' => 'Times New Roman'],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 200]
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
        
        // Если текст уже нормально отображается (содержит кириллицу), возвращаем как есть
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $text) && strpos($text, '\u') === false) {
            return $text;
        }
        
        // Основной метод декодирования Unicode escape-последовательностей
        $decoded = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            $codepoint = hexdec($matches[1]);
            return mb_chr($codepoint, 'UTF-8');
        }, $text);
        
        // Если декодирование не сработало, пробуем json_decode
        if ($decoded === $text && strpos($text, '\u') !== false) {
            $jsonDecoded = json_decode('"' . $text . '"', true);
            if ($jsonDecoded !== null && $jsonDecoded !== $text) {
                $decoded = $jsonDecoded;
            }
        }
        
        // Убираем лишние escape-символы
        $decoded = stripslashes($decoded);
        
        return $decoded;
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
        
        // Сохраняем документ с правильными настройками
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');

        $fullSavePath = storage_path('app/public/' . $fullPath);
        $objWriter->save($fullSavePath);

        return $fullSavePath;
    }
} 