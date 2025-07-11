# Управление Failed Jobs

## 🔧 Проблема с автоматическим перезапуском failed jobs

По умолчанию Laravel пытается перезапускать failed jobs автоматически, что может приводить к циклическим ошибкам и нагрузке на систему.

## ⚙️ Настройки retry для jobs

### Текущие настройки в классах jobs

#### StartFullGenerateDocument
```php
public $timeout = 600; // 10 минут
public $tries = 3;     // Максимум 3 попытки
public $backoff = [30, 60, 120]; // Задержки между попытками
```

#### AsyncGenerateDocument  
```php
public $timeout = 600; // 10 минут
public $tries = 3;     // Максимум 3 попытки
public $backoff = [30, 60, 120]; // Экспоненциальный backoff
```

## 📋 Команды для управления failed jobs

### Просмотр failed jobs
```bash
# Показать все failed jobs
php artisan queue:failed

# Показать детали конкретной failed job
php artisan queue:failed-show {id}

# Показать статистику
php artisan jobs:status
```

### Управление failed jobs
```bash
# Повторить все failed jobs
php artisan queue:retry all

# Повторить конкретную failed job
php artisan queue:retry {id}

# Удалить все failed jobs
php artisan queue:flush

# Удалить конкретную failed job
php artisan queue:forget {id}
```

### Очистка зависших jobs
```bash
# Очистить все активные jobs
php artisan queue:clear

# Перезапустить все воркеры
php artisan queue:restart
```

## 🚫 Предотвращение автоматического перезапуска

### 1. Настройка параметров tries и backoff

Для предотвращения бесконечных попыток убедитесь, что в ваших job классах установлены ограничения:

```php
class YourJob implements ShouldQueue
{
    public $tries = 3;                    // Максимум 3 попытки
    public $maxExceptions = 3;           // Максимум исключений 
    public $backoff = [60, 300, 900];   // Задержки: 1мин, 5мин, 15мин
    public $timeout = 600;               // Таймаут 10 минут
}
```

### 2. Отключение автоматического retry для воркеров

```bash
# Запуск воркера БЕЗ автоматического retry
php artisan queue:work --tries=1 --backoff=0

# Запуск воркера с ограниченным retry
php artisan queue:work --tries=2 --backoff=60
```

### 3. Настройка в config/queue.php

```php
'connections' => [
    'database' => [
        'driver' => 'database',
        'retry_after' => 300,  // 5 минут до считания job "мертвой"
        'after_commit' => false,
    ],
],
```

## 🔍 Мониторинг и отладка

### Создание команды для мониторинга
```bash
# Создать новую команду
php artisan make:command MonitorFailedJobs
```

### Отслеживание проблемных документов
```bash
# Найти документы с failed статусом
php artisan tinker
>>> \App\Models\Document::whereIn('status', ['pre_generation_failed', 'full_generation_failed'])->count()
```

### Логирование failed jobs
```php
// В config/logging.php добавить канал для failed jobs
'failed_jobs' => [
    'driver' => 'single',
    'path' => storage_path('logs/failed-jobs.log'),
    'level' => 'error',
],
```

## 🛠️ Рекомендуемая стратегия

### 1. Для production окружения

```bash
# Запуск воркеров с ограниченными попытками
nohup php artisan queue:work --queue=document_creates --tries=2 --timeout=300 --backoff=60 > worker.log 2>&1 &
```

### 2. Регулярная очистка failed jobs

Создайте cron задачу для автоматической очистки старых failed jobs:

```bash
# В crontab
# Очистка failed jobs старше 7 дней каждый день в 2:00
0 2 * * * cd /path/to/project && php artisan queue:prune-failed --hours=168
```

### 3. Мониторинг через команду

```bash
# Создайте команду для регулярной проверки
php artisan make:command CheckFailedJobs

# Добавьте в cron для уведомлений
*/30 * * * * cd /path/to/project && php artisan queue:check-failed
```

## 🚨 Быстрое решение текущих проблем

### Если много зависших jobs прямо сейчас:

```bash
# 1. Остановить всех воркеров
pkill -f "queue:work"

# 2. Очистить активные jobs
php artisan queue:clear

# 3. Проверить failed jobs
php artisan queue:failed

# 4. Очистить failed jobs (если нужно)
php artisan queue:flush

# 5. Перезапустить воркеров с ограничениями
php artisan queue:work --queue=document_creates --tries=2 --timeout=300
```

### Если нужно проанализировать ошибки:

```bash
# Экспорт failed jobs в файл для анализа
php artisan queue:failed > failed_jobs_$(date +%Y%m%d).txt

# Поиск конкретных типов ошибок
php artisan queue:failed | grep "StartFullGenerateDocument"
```

## 📊 Настройка алертов

### Создание команды для уведомлений

```php
// app/Console/Commands/AlertOnFailedJobs.php
class AlertOnFailedJobs extends Command
{
    protected $signature = 'queue:alert-failed {--threshold=10}';
    
    public function handle()
    {
        $threshold = $this->option('threshold');
        $failedCount = DB::table('failed_jobs')->count();
        
        if ($failedCount >= $threshold) {
            // Отправить уведомление админу
            Log::channel('telegram')->error("Критическое количество failed jobs: {$failedCount}");
        }
    }
}
```

### Добавление в cron
```bash
# Проверка каждые 15 минут
*/15 * * * * cd /path/to/project && php artisan queue:alert-failed --threshold=5
``` 