# API Reference - Система генерации документов

## Базовые endpoints

### Документы

```http
GET    /documents                      # Список документов
POST   /documents                      # Создание документа
GET    /documents/{id}                 # Просмотр документа
PUT    /documents/{id}                 # Редактирование документа
DELETE /documents/{id}                 # Удаление документа
GET    /documents/{id}/edit            # Форма редактирования
```

### Статус и генерация

```http
GET    /documents/{id}/status                    # Получение статуса
POST   /documents/{id}/generate-full             # Запуск полной генерации
GET    /documents/{id}/generation-progress       # Прогресс генерации
POST   /documents/{id}/download-word             # Скачивание Word
```

## Схемы ответов

### Статус документа

```typescript
interface DocumentStatusResponse {
    document_id: number
    status: DocumentStatus
    status_label: string
    status_color: string
    status_icon: string
    is_final: boolean
    is_generating: boolean
    can_start_full_generation: boolean
    is_fully_generated: boolean
    title: string
    updated_at: string
    has_contents: boolean
    has_objectives: boolean
    has_detailed_contents: boolean
    has_introduction: boolean
    has_conclusion: boolean
    structure_complete: boolean
}
```

### Прогресс генерации

```typescript
interface GenerationProgressResponse {
    document_id: number
    status: DocumentStatus
    status_label: string
    is_generating: boolean
    can_start_full_generation: boolean
    is_fully_generated: boolean
    progress: {
        has_basic_structure: boolean
        has_detailed_contents: boolean
        has_introduction: boolean
        has_conclusion: boolean
        completion_percentage: number
    }
}
```

### Документ

```typescript
interface Document {
    id: number
    user_id: number
    document_type_id: number
    title: string
    status: DocumentStatus
    structure: DocumentStructure
    content: string | null
    pages_num: number | null
    gpt_settings: GptSettings | null
    created_at: string
    updated_at: string
}
```

### Структура документа

```typescript
interface DocumentStructure {
    topic: string
    theses?: string
    objectives: string[]
    contents: ContentSection[]
    references?: Reference[]
    
    // Добавляется при полной генерации
    introduction?: string
    conclusion?: string
    detailed_objectives?: DetailedObjective[]
    detailed_contents?: DetailedContentSection[]
}

interface ContentSection {
    title: string
    subtopics: Subtopic[]
}

interface Subtopic {
    title: string
    content?: string
}

interface DetailedObjective {
    title: string
    description: string
    success_criteria: string
}

interface DetailedContentSection {
    title: string
    introduction: string
    subtopics: DetailedSubtopic[]
    summary: string
}

interface DetailedSubtopic {
    title: string
    content: string
    examples: string[]
    key_points: string[]
}
```

### Настройки GPT

```typescript
interface GptSettings {
    service: 'openai' | 'anthropic'
    model: string
    temperature: number
    max_tokens?: number
}
```

## Типы статусов

```typescript
type DocumentStatus = 
    | 'draft'
    | 'pre_generating'
    | 'pre_generated'
    | 'pre_generation_failed'
    | 'full_generating'
    | 'full_generated'
    | 'full_generation_failed'
    | 'in_review'
    | 'approved'
    | 'rejected'
```

## Коды ошибок

| Код | Описание |
|-----|----------|
| 200 | Успех |
| 201 | Создано |
| 400 | Неверный запрос |
| 401 | Не авторизован |
| 403 | Доступ запрещен |
| 404 | Не найдено |
| 422 | Ошибка валидации |
| 500 | Внутренняя ошибка сервера |

## Примеры curl

### Создание документа

```bash
curl -X POST http://localhost/documents \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "document_type_id": 1,
    "topic": "Планирование проекта"
  }'
```

### Проверка статуса

```bash
curl -X GET http://localhost/documents/1/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Запуск полной генерации

```bash
curl -X POST http://localhost/documents/1/generate-full \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Получение прогресса

```bash
curl -X GET http://localhost/documents/1/generation-progress \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## JavaScript SDK

### Базовый пример

```javascript
import { apiClient } from '@/composables/api'

// Создание документа
const createDocument = async (data) => {
    return await apiClient.post('/documents', data)
}

// Проверка статуса
const checkStatus = async (documentId) => {
    return await apiClient.get(`/documents/${documentId}/status`)
}

// Запуск полной генерации
const startFullGeneration = async (documentId) => {
    return await apiClient.post(`/documents/${documentId}/generate-full`)
}
```

### Использование композабла

```javascript
import { useDocumentStatus } from '@/composables/documentStatus'

const {
    status,
    isGenerating,
    canStartFullGeneration,
    startPolling,
    stopPolling
} = useDocumentStatus(
    () => documentId,
    {
        autoStart: true,
        pollInterval: 3000,
        onComplete: (status) => {
            console.log('Базовая генерация завершена:', status)
        },
        onFullComplete: (status) => {
            console.log('Полная генерация завершена:', status)
            stopPolling()
        },
        onError: (error) => {
            console.error('Ошибка:', error)
        }
    }
)
```

## Rate Limiting

API имеет ограничения:
- 60 запросов в минуту на пользователя
- 10 запросов генерации в час на пользователя
- 1 активная генерация на пользователя одновременно

## Webhooks (планируется)

```typescript
interface WebhookPayload {
    event: 'document.generated' | 'document.failed' | 'document.approved'
    document_id: number
    status: DocumentStatus
    timestamp: string
    data: any
}
``` 