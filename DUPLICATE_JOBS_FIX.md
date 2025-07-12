# Исправление проблемы дублирования задач полной генерации

## Проблема

В процессе полной генерации документов была обнаружена проблема повторного запуска одной и той же задачи. В логах было видно:

```
[2025-07-12 07:15:13] ШАГ 1: Начало полной генерации документа (process_id=217149)
[2025-07-12 07:16:44] ШАГ 1: Начало полной генерации документа (process_id=217145)
```

Один и тот же job (job_id=76) выполнялся двумя разными процессами одновременно, что приводило к конфликтам в OpenAI API:

```
Thread thread_sBBUou34ch8Hf92FODmJe4L6 already has an active run run_tSxf2LOMHPB7icYx1hHLRT8Z
```

## Причина

Несоответствие настроек timeout в job и retry_after в конфигурации очереди:

- **Job timeout**: 600 секунд (10 минут)
- **Queue retry_after**: 300 секунд (5 минут)

Когда job выполняется дольше 5 минут, Laravel считает его "зависшим" и запускает повторно, хотя первый процесс все еще работает.

## Решение

### 1. Исправление настроек очереди

В `config/queue.php` увеличен `retry_after` для очереди `document_creates`:

```php
'document_creates' => [
    'driver' => 'database',
    'connection' => env('DB_QUEUE_CONNECTION'),
    'table' => env('DB_QUEUE_TABLE', 'jobs'),
    'queue' => 'document_creates',
    'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 900), // 15 минут - больше чем timeout job (10 минут)
    'after_commit' => false,
],
```

### 2. Защита от дублирования в Job

В `app/Jobs/StartFullGenerateDocument.php` добавлена проверка на дублирующие задачи:

```php
// Проверяем, не выполняется ли уже такая же задача для этого документа
$activeJobsCount = DB::table('jobs')
    ->where('payload', 'like', '%"document_id":' . $this->document->id . '%')
    ->where('payload', 'like', '%StartFullGenerateDocument%')
    ->count();

if ($activeJobsCount > 1) {
    // Прерываем выполнение, чтобы избежать дублирования
    Log::channel('queue')->warning('Прервано выполнение дублирующей задачи полной генерации');
    return;
}
```

### 3. Защита в DocumentJobService

В `app/Services/Documents/DocumentJobService.php` добавлена дополнительная проверка перед запуском:

```php
// Дополнительная проверка конкретно для StartFullGenerateDocument
$activeFullGenerationJobs = DB::table('jobs')
    ->where('payload', 'like', '%"document_id":' . $document->id . '%')
    ->where('payload', 'like', '%StartFullGenerateDocument%')
    ->count();
    
if ($activeFullGenerationJobs > 0) {
    throw new \Exception('Для этого документа уже запущена задача полной генерации');
}
```

### 4. Команда для очистки дублей

Создана команда `app/Console/Commands/CleanDuplicateJobs.php` для очистки дублирующих задач:

```bash
# Проверить наличие дублей (без удаления)
php artisan queue:clean-duplicates --dry-run

# Проверить дубли для конкретного документа
php artisan queue:clean-duplicates --document-id=30 --dry-run

# Удалить дубли
php artisan queue:clean-duplicates

# Удалить дубли для конкретного документа
php artisan queue:clean-duplicates --document-id=30
```

## Логика работы защиты

1. **Настройки времени**: `retry_after` (15 мин) > `timeout` (10 мин) - предотвращает ложные срабатывания
2. **Проверка в Job**: При запуске job проверяет количество активных задач для того же документа
3. **Проверка в Service**: Перед добавлением в очередь проверяется наличие активных задач
4. **Graceful exit**: Дублирующие задачи завершаются без ошибок, записывая предупреждение в лог

## Мониторинг

Для отслеживания дублирования задач используйте:

```bash
# Проверить активные задачи для документа
php artisan queue:clean-duplicates --document-id=30 --dry-run

# Мониторинг логов
grep -i "дублирующей задачи" storage/logs/queue.log
grep -i "активных задач" storage/logs/queue.log
```

## Результат

- Исключено дублирование задач полной генерации
- Предотвращены конфликты в OpenAI API
- Добавлено детальное логгирование для мониторинга
- Созданы инструменты для диагностики и очистки дублей

Проблема с ошибкой "Thread already has an active run" должна быть решена. 