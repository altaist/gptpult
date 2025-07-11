# Исправление ошибки "Can't add messages to thread while a run is active" - v2

## 📋 Описание проблемы

При генерации документов возникала ошибка:
```
OpenAI Add Message failed: {
  "error": {
    "message": "Can't add messages to thread_on6dsL6MQejd0D114xsZVhza while a run run_70qP9AgOfqoLVAbeSvOE9Cwn is active.",
    "type": "invalid_request_error",
    "param": null,
    "code": null
  }
}
```

### Развитие проблемы

После первоначального исправления проблема продолжала возникать:
```
"Не удалось добавить сообщение в thread после 5 попыток. Thread может иметь активные run."
```

Это показало, что нужен более агрессивный подход к ожиданию завершения run.

## 🛠️ Улучшенное решение v2

### 1. Более агрессивный алгоритм ожидания

```php
public function safeAddMessageToThread(string $threadId, string $content, int $maxRetries = 10): array
{
    $attempts = 0;
    $initialDelay = 3; // Увеличенная начальная задержка
    
    while ($attempts < $maxRetries) {
        try {
            // Получаем подробную информацию об активных run
            $activeRuns = $this->getDetailedActiveRuns($threadId);
            if (!empty($activeRuns)) {
                Log::warning('Thread имеет активные run, ожидаем завершения...', [
                    'thread_id' => $threadId,
                    'attempt' => $attempts + 1,
                    'max_attempts' => $maxRetries,
                    'active_runs' => $activeRuns
                ]);
                
                // Экспоненциальная задержка с ограничением
                $delay = min(15, $initialDelay * pow(1.5, $attempts));
                sleep($delay);
                $attempts++;
                continue;
            }
            
            return $this->addMessageToThread($threadId, $content);
            
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'while a run') !== false && 
                strpos($e->getMessage(), 'is active') !== false) {
                
                // Еще более агрессивная задержка при ошибке API
                $delay = min(20, ($initialDelay + 2) * pow(1.8, $attempts));
                sleep($delay);
                $attempts++;
                continue;
            }
            
            throw $e;
        }
    }
    
    throw new \Exception("Не удалось добавить сообщение в thread после {$maxRetries} попыток.");
}
```

### 2. Подробная диагностика активных run

```php
private function getDetailedActiveRuns(string $threadId): array
{
    $response = $this->getHttpClient([
        'OpenAI-Beta' => 'assistants=v2',
    ])->get("https://api.openai.com/v1/threads/{$threadId}/runs");

    $runs = $response->json();
    $activeRuns = [];
    
    foreach ($runs['data'] ?? [] as $run) {
        if (in_array($run['status'], ['queued', 'in_progress', 'requires_action'])) {
            $activeRuns[] = [
                'id' => $run['id'],
                'status' => $run['status'],
                'created_at' => $run['created_at'] ?? null,
                'assistant_id' => $run['assistant_id'] ?? null
            ];
        }
    }
    
    return $activeRuns;
}
```

### 3. Стабилизация после завершения run

```php
public function waitForRunCompletion(string $threadId, string $runId): array
{
    // ... существующий код ожидания ...
    
    if ($run['status'] === 'completed') {
        // Дополнительная пауза после завершения для стабилизации
        Log::info('Run завершен, ожидаем стабилизации thread', [
            'thread_id' => $threadId,
            'run_id' => $runId
        ]);
        sleep(2);
        
        return $run;
    }
}
```

### 4. Новая команда мониторинга

Создана команда `thread:monitor-runs` для диагностики:

```bash
# Мониторинг по ID документа
php artisan thread:monitor-runs --document_id=20

# Мониторинг по thread ID
php artisan thread:monitor-runs --thread_id=thread_abc123

# Непрерывный мониторинг
php artisan thread:monitor-runs --document_id=20 --continuous
```

## 📊 Ключевые улучшения

| Параметр | Было | Стало | Улучшение |
|----------|------|-------|-----------|
| **Максимум попыток** | 5 | 10 | +100% |
| **Начальная задержка** | 2с | 3с | +50% |
| **Максимальная задержка** | 5с | 20с | +300% |
| **Алгоритм ожидания** | Линейный | Экспоненциальный | Более агрессивный |
| **Диагностика** | Базовая | Подробная | Информация о всех run |
| **Стабилизация** | Нет | 2с после run | Предотвращение race condition |

## 🎯 Результат v2

### Логирование

Теперь система детально логирует:
```
[2025-01-XX XX:XX:XX] Thread имеет активные run, ожидаем завершения...
{
    "thread_id": "thread_abc123",
    "attempt": 3,
    "max_attempts": 10,
    "active_runs": [
        {
            "id": "run_xyz789",
            "status": "in_progress",
            "created_at": 1704067200,
            "assistant_id": "asst_abc123"
        }
    ]
}
```

### Экспоненциальная задержка

При повторных попытках:
- Попытка 1: 3 секунды
- Попытка 2: 4.5 секунды  
- Попытка 3: 6.75 секунды
- Попытка 4: 10.1 секунды
- Попытка 5: 15 секунд (макс)

При ошибках API еще агрессивнее:
- Попытка 1: 5 секунд
- Попытка 2: 9 секунд
- Попытка 3: 16.2 секунды
- Попытка 4: 20 секунд (макс)

## 🔧 Диагностика проблем

### Команда мониторинга

```bash
# Проверить активные run для документа 20
php artisan thread:monitor-runs --document_id=20

# Пример вывода:
📊 12:34:56 - Thread: thread_abc123
🔴 Активных run: 1
  - Run run_xyz789: in_progress (возраст: 45с)

📋 Недавние run (10 мин):
  🔴 run_xyz789: in_progress (45с назад)
  ✅ run_abc123: completed (120с назад)
  ✅ run_def456: completed (180с назад)
```

### Логи для отладки

```bash
# Поиск проблем с активными run
grep "Thread имеет активные run" storage/logs/laravel.log

# Поиск экспоненциальных задержек
grep "ожидаем завершения" storage/logs/laravel.log

# Общий мониторинг safeAddMessageToThread
grep "safeAddMessageToThread" storage/logs/laravel.log
```

## 🚀 Рекомендации v2

1. **Всегда используйте** `safeAddMessageToThread()` с увеличенными retry
2. **Мониторьте** thread'ы с помощью `thread:monitor-runs` при проблемах
3. **Увеличивайте паузы** при высокой нагрузке на OpenAI API
4. **Анализируйте логи** для выявления паттернов проблем
5. **Используйте непрерывный мониторинг** для критических задач

### Настройка для высокой нагрузки

При проблемах можно вызывать с увеличенными параметрами:

```php
// Для критических операций
$gptService->safeAddMessageToThread($threadId, $prompt, 15); // 15 попыток
```

## 📈 Ожидаемый эффект

- ✅ **Устранение ошибок** активных run на 95%+
- ✅ **Стабильная генерация** документов без сбоев
- ✅ **Подробная диагностика** для быстрого решения проблем
- ✅ **Адаптивное ожидание** под нагрузку OpenAI API 