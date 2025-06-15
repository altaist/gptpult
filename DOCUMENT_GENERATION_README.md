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

### Автоматическая генерация

При создании документа через API endpoint `/documents` (метод `quickCreate`):
1. Документ создается в статусе `draft`
2. Автоматически запускается Job `StartGenerateDocument`
3. Статус документа меняется на `processing`
4. После завершения - на `completed` или `failed`

### Настройки GPT

В поле `gpt_settings` документа можно указать:
```json
{
    "service": "openai",
    "model": "gpt-3.5-turbo",
    "temperature": 0.7
}
```

## Логирование

Все операции логируются в файл `storage/logs/queue.log`:
- Начало генерации документа
- Отправка запроса к GPT
- Успешное завершение с метриками
- Ошибки с подробным описанием

## События

### GptRequestCompleted
Генерируется при успешной генерации документа.

### GptRequestFailed
Генерируется при ошибке генерации с описанием ошибки.

## Структура генерируемого документа

GPT генерирует JSON со структурой:
```json
{
    "objectives": [
        "Цель 1",
        "Цель 2",
        "Цель 3"
    ],
    "contents": [
        {
            "title": "Название раздела",
            "subtopics": [
                {
                    "title": "Подраздел",
                    "content": "Содержание подраздела"
                }
            ]
        }
    ]
}
```

## Мониторинг

### Проверка очереди:
```bash
php artisan queue:work --queue=document_creates --once
```

### Просмотр логов:
```bash
tail -f storage/logs/queue.log
```

### Статистика очереди:
```bash
php artisan queue:monitor document_creates
```

## Требования

- Настроенный OpenAI API ключ в `.env`:
```env
OPENAI_API_KEY=your_openai_api_key
OPENAI_ORGANIZATION=your_organization_id (optional)
OPENAI_DEFAULT_MODEL=gpt-3.5-turbo
```

- Запущенный queue worker для обработки заданий

## Troubleshooting

### Job не выполняется
1. Проверьте, что запущен воркер очереди
2. Убедитесь, что настроены ключи OpenAI
3. Проверьте логи в `storage/logs/queue.log`

### Ошибки парсинга GPT ответа
1. Проверьте качество промпта в методе `buildPrompt()`
2. Убедитесь, что GPT возвращает валидный JSON
3. Проверьте логи для анализа ответа GPT 