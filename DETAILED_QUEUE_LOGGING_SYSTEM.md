# Система детального логгирования очереди

## Обзор

Создана комплексная система логгирования для отслеживания всех операций с очередью и выявления причин повторных генераций документов. Система включает:

1. **Специальный канал логгирования** (`queue_operations`)
2. **Слушатель событий очереди** (`QueueEventListener`)
3. **Детальное логгирование в сервисах** (`DocumentJobService`)
4. **Логгирование HTTP запросов** (`QueueOperationsLogger` middleware)
5. **Команды мониторинга** (`QueueMonitorRealtime`, `CleanDuplicateJobs`)

## Компоненты системы

### 1. Канал логгирования

**Файл:** `config/logging.php`

```php
'queue_operations' => [
    'driver' => 'single',
    'path' => storage_path('logs/queue_operations.log'),
    'level' => 'debug',
    'replace_placeholders' => true,
],
```

**Назначение:** Централизованное логгирование всех операций с очередью в отдельный файл для удобного анализа.

### 2. Слушатель событий очереди

**Файл:** `app/Listeners/QueueEventListener.php`

**События:**
- `JobQueued` - Задача добавлена в очередь
- `JobProcessing` - Задача начала выполняться
- `JobProcessed` - Задача выполнена успешно
- `JobFailed` - Задача завершилась с ошибкой
- `JobExceptionOccurred` - Исключение в задаче
- `JobRetryRequested` - Запрос на повторное выполнение
- `JobTimedOut` - Задача превысила timeout

**Логгируемая информация:**
- Временные метки с миллисекундами
- ID задачи и класс
- ID документа (если есть)
- Количество попыток
- Использование памяти
- Process ID
- Детали ошибок

### 3. Детальное логгирование в сервисах

**Файл:** `app/Services/Documents/DocumentJobService.php`

**Логгируемые этапы:**
- Начало процесса запуска генерации
- Проверка статуса документа
- Проверка активных задач (hasActiveJob)
- Дополнительная проверка StartFullGenerateDocument
- Обновление статуса документа
- Добавление задачи в очередь
- Успешное завершение или ошибки

**Ключевые события:**
- `start_full_generation_begin`
- `start_full_generation_check_active_jobs`
- `start_full_generation_dispatch_job`
- `start_full_generation_job_dispatched`

### 4. Логгирование HTTP запросов

**Файл:** `app/Http/Middleware/QueueOperationsLogger.php`

**Отслеживаемые запросы:**
- `documents/*/generate-full`
- `documents/*/generation-progress`
- `documents/*/start-generation`

**Логгируемая информация:**
- Начало и окончание запроса
- Время выполнения
- Размер запроса/ответа
- Заголовки
- Статус ответа
- Медленные запросы (>1 сек)

### 5. Команды мониторинга

#### QueueMonitorRealtime

**Команда:** `php artisan queue:monitor-realtime`

**Опции:**
- `--interval=5` - Интервал обновления в секундах
- `--document-id=30` - Фильтр по ID документа

**Функции:**
- Отображение активных задач в реальном времени
- Статистика по очередям
- Последние события из логов
- Системная информация
- Информация о воркерах

#### CleanDuplicateJobs

**Команда:** `php artisan queue:clean-duplicates`

**Опции:**
- `--dry-run` - Только показать что будет удалено
- `--document-id=30` - Фильтр по ID документа

**Функции:**
- Поиск дублирующих задач
- Безопасное удаление дублей
- Детальная информация о найденных дублях

## Структура логов

### Формат записи

```json
{
  "event": "start_full_generation_begin",
  "timestamp": "2025-07-12 14:30:45.123",
  "document_id": 30,
  "document_title": "Название документа",
  "current_status": "pre_generated",
  "process_id": 12345,
  "memory_usage": 33554432
}
```

### Ключевые события

| Событие | Описание |
|---------|----------|
| `job_queued` | Задача добавлена в очередь |
| `job_processing_started` | Начало выполнения задачи |
| `job_processed_successfully` | Задача выполнена успешно |
| `job_failed` | Задача завершилась с ошибкой |
| `start_full_generation_begin` | Начало запуска полной генерации |
| `start_full_generation_check_active_jobs` | Проверка активных задач |
| `start_full_generation_dispatch_job` | Добавление задачи в очередь |
| `api_start_full_generation_request` | HTTP запрос на запуск генерации |
| `http_request_start` | Начало HTTP запроса |
| `http_request_end` | Окончание HTTP запроса |

## Использование

### Мониторинг в реальном времени

```bash
# Общий мониторинг
php artisan queue:monitor-realtime

# Мониторинг конкретного документа
php artisan queue:monitor-realtime --document-id=30

# Мониторинг с интервалом 2 секунды
php artisan queue:monitor-realtime --interval=2
```

### Анализ логов

```bash
# Просмотр последних событий
tail -f storage/logs/queue_operations.log

# Поиск событий для конкретного документа
grep '"document_id":30' storage/logs/queue_operations.log

# Поиск ошибок
grep -i "error\|failed" storage/logs/queue_operations.log

# Поиск дублирующих задач
grep "ОБНАРУЖЕНО ДУБЛИРОВАНИЕ" storage/logs/queue_operations.log
```

### Очистка дублей

```bash
# Проверка дублей без удаления
php artisan queue:clean-duplicates --dry-run

# Очистка дублей для конкретного документа
php artisan queue:clean-duplicates --document-id=30

# Полная очистка дублей
php artisan queue:clean-duplicates
```

## Диагностика проблем

### Повторные генерации

**Признаки:**
- Несколько записей `job_queued` для одного документа
- Разные `process_id` для одной задачи
- Сообщения "ОБНАРУЖЕНО ДУБЛИРОВАНИЕ"

**Анализ:**
```bash
# Поиск повторных запусков
grep -A 5 -B 5 "job_queued.*StartFullGenerateDocument.*document_id.:30" storage/logs/queue_operations.log

# Проверка временных интервалов
grep "start_full_generation_begin.*document_id.:30" storage/logs/queue_operations.log
```

### Медленные запросы

**Признаки:**
- Записи `SLOW HTTP REQUEST`
- Большое время выполнения (`execution_time_ms`)

**Анализ:**
```bash
# Поиск медленных запросов
grep "SLOW HTTP REQUEST" storage/logs/queue_operations.log

# Анализ времени выполнения
grep "execution_time_ms" storage/logs/queue_operations.log | sort -t':' -k2 -n
```

### Ошибки в задачах

**Признаки:**
- Записи `job_failed` или `job_exception_occurred`
- Детали исключений

**Анализ:**
```bash
# Поиск ошибок в задачах
grep "job_failed\|job_exception_occurred" storage/logs/queue_operations.log

# Детали конкретной ошибки
grep -A 10 "job_failed.*document_id.:30" storage/logs/queue_operations.log
```

## Настройка

### Отключение логгирования

Для отключения детального логгирования:

1. Удалить `QueueOperationsLogger` из middleware
2. Удалить `EventServiceProvider` из providers
3. Закомментировать логгирование в `DocumentJobService`

### Настройка уровня логгирования

В `config/logging.php`:

```php
'queue_operations' => [
    'level' => 'info', // debug, info, warning, error
],
```

### Ротация логов

Для автоматической ротации логов:

```php
'queue_operations' => [
    'driver' => 'daily',
    'path' => storage_path('logs/queue_operations.log'),
    'level' => 'debug',
    'days' => 7,
],
```

## Производительность

### Влияние на производительность

- Минимальное влияние на HTTP запросы (~1-2ms)
- Незначительное влияние на задачи очереди (~0.5-1ms)
- Размер логов: ~10-50KB на задачу

### Оптимизация

1. **Уровень логгирования:** Используйте `info` вместо `debug` в продакшене
2. **Ротация логов:** Настройте автоматическую ротацию
3. **Фильтрация:** Логгируйте только важные события
4. **Мониторинг:** Регулярно очищайте старые логи

## Заключение

Система детального логгирования обеспечивает:

- **Полную видимость** всех операций с очередью
- **Быструю диагностику** проблем с дублированием
- **Мониторинг производительности** в реальном времени
- **Инструменты анализа** для выявления узких мест
- **Автоматическую защиту** от повторных запусков

Используйте эту систему для выявления и устранения проблем с повторными генерациями документов. 