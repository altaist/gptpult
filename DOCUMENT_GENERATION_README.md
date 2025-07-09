# Система генерации документов

## Описание

Была добавлена система автоматической генерации документов через OpenAI GPT. Система включает в себя:

- **Job** `StartGenerateDocument` для асинхронной генерации документов
- **Специальную очередь** `document_creates` для обработки заданий генерации
- **Автоматический запуск** генерации при создании документа через `quickCreate`
- **Логирование** всех операций в `queue.log`
- **События** для уведомления о завершении (успешном или неуспешном)

## Компоненты

### 1. Job: StartGenerateDocument

Основной Job для генерации документов:
- Принимает модель `Document`
- Использует настройки из поля `gpt_settings` документа
- Парсит ответ GPT и извлекает `contents` и `objectives`
- Обновляет структуру документа
- Генерирует события `GptRequestCompleted` или `GptRequestFailed`

### 2. Специальная очередь

Настроена очередь `document_creates` для:
- Изоляции задач генерации документов
- Увеличенного таймаута (300 сек)
- Отдельного мониторинга и управления

### 3. Команды

#### Запуск воркера генерации документов:
```bash
php artisan queue:work-documents
# или
php artisan queue:work --queue=document_creates
```

#### Тестирование системы:
```bash
php artisan test:document-generation --user-id=1 --topic="Тема для тестирования"
```

## Использование

### Создание документа с автоматической генерацией

```php
use App\Services\Documents\DocumentService;
use App\Services\Documents\DocumentJobService;

$documentService = app(DocumentService::class);
$jobService = app(DocumentJobService::class);

// Создаем документ
$document = $documentService->create([
    'user_id' => auth()->id(),
    'document_type_id' => 1,
    'title' => 'Тема исследования',
    'structure' => ['topic' => 'Тема исследования'],
    'gpt_settings' => [
        'service' => 'openai',
        'model' => 'gpt-4',
        'temperature' => 0.7
    ]
]);

// Запускаем генерацию
$jobService->startBaseGeneration($document);
```

### Мониторинг статуса генерации

```php
// Проверка статуса
$status = $document->status; // draft, pre_generating, generated, failed

// Проверка наличия контента
$hasContent = !empty($document->content);
$hasTopics = isset($document->content['topics']);
```

## Устранение ошибок

### Ошибка XML парсинга (SAXParseException)

**Симптомы:**
- Ошибка `SAXParseException: [word/document.xml line 2]: EntityRef: expecting ';'`
- Ошибка fastparser или WriterFilter

**Причины:**
- Некорректные Unicode символы в тексте
- Неэкранированные XML символы (&, <, >, ", ')
- Управляющие символы в тексте

**Исправление:**
Система автоматически обрабатывает такие ошибки:

1. **Автоматическая очистка текста** - все тексты проходят через `sanitizeForXml()`
2. **Fallback механизм** - при ошибке создается упрощенная версия документа
3. **Подробное логирование** - все ошибки записываются в лог для диагностики

**Проверка логов:**
```bash
# Логи генерации документов
tail -f storage/logs/laravel.log | grep "Word документ"

# Поиск ошибок XML
grep -i "saxparse\|xml\|fastparser" storage/logs/laravel.log
```

### Проблемы с кодировкой

**Симптомы:**
- Кракозябры в документе
- Неправильное отображение русских символов

**Исправление:**
- Проверьте кодировку базы данных (должна быть utf8mb4)
- Убедитесь, что в `gpt_settings` правильно настроена кодировка

### Большие документы

**Симптомы:**
- Таймаут при генерации
- Превышение лимитов памяти

**Исправление:**
```bash
# Увеличьте таймаут для очереди
php artisan queue:work --queue=document_creates --timeout=600

# В config/queue.php увеличьте timeout
'timeout' => 600,
```

### Проблемы с правами доступа

**Симптомы:**
- Ошибка записи файла
- Permission denied

**Исправление:**
```bash
# Проверьте права на папку storage
chmod -R 775 storage/
chown -R www-data:www-data storage/

# Очистите кеш
php artisan cache:clear
php artisan config:clear
```

### Отладка генерации

**Включение подробного логирования:**
```php
// В .env добавьте
LOG_LEVEL=debug

// В config/logging.php для канала queue
'level' => 'debug',
```

**Тестирование генерации:**
```bash
# Создание тестового документа
php artisan tinker
>>> $doc = \App\Models\Document::factory()->create(['user_id' => 1]);
>>> $service = app(\App\Services\Documents\Files\WordDocumentService::class);
>>> $file = $service->generate($doc);
```

**Проверка структуры документа:**
```bash
# Просмотр содержимого документа
php artisan tinker
>>> $doc = \App\Models\Document::find(1);
>>> dd($doc->content);
>>> dd($doc->structure);
```

## Конфигурация

### Настройка очереди для документов

В `config/queue.php`:
```php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'document_creates',
        'retry_after' => 300,
        'after_commit' => false,
    ],
],
```

### Настройка логирования

В `config/logging.php`:
```php
'channels' => [
    'queue' => [
        'driver' => 'daily',
        'path' => storage_path('logs/queue.log'),
        'level' => 'info',
        'days' => 14,
    ],
], 