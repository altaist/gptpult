# Справочник статусов документов

## 📊 Быстрая таблица статусов

| Статус | Русское название | Цвет | Иконка | Финальный | Генерация |
|--------|------------------|------|--------|-----------|-----------|
| `draft` | Черновик | grey | edit | ❌ | ❌ |
| `pre_generating` | Генерируется структура и ссылки... | primary | sync | ❌ | ✅ |
| `pre_generated` | Структура готова | positive | check_circle | ❌ | ❌ |
| `pre_generation_failed` | Ошибка генерации структуры | negative | error | ✅ | ❌ |
| `full_generating` | Генерируется содержимое... | secondary | autorenew | ❌ | ✅ |
| `full_generated` | Полностью готов | green | task_alt | ❌ | ❌ |
| `full_generation_failed` | Ошибка полной генерации | red | error_outline | ✅ | ❌ |
| `in_review` | На проверке | warning | rate_review | ❌ | ❌ |
| `approved` | Утвержден | green-10 | verified | ✅ | ❌ |
| `rejected` | Отклонен | red-8 | cancel | ✅ | ❌ |

## 🔄 Переходы между статусами

### Нормальный flow
```
draft → pre_generating → pre_generated → full_generating → full_generated → in_review → approved
```

### С ошибками
```
draft → pre_generating → pre_generation_failed (финал)
pre_generated → full_generating → full_generation_failed (финал)
in_review → rejected (финал)
```

### Альтернативные пути
```
pre_generated → in_review → approved (без полной генерации)
full_generated → approved (прямое утверждение)
```

## 🎯 Ключевые методы статусов

```php
// Проверки статуса
$status->isFinal()                    // Финальный ли статус
$status->isGenerating()               // Идет ли генерация
$status->canStartFullGeneration()     // Можно ли запустить полную генерацию
$status->isFullyGenerated()           // Завершена ли полная генерация

// Получение метаданных
$status->getLabel()                   // Человекочитаемое название
$status->getColor()                   // Цвет для UI
$status->getIcon()                    // Иконка для UI
```

## 📱 Состояния кнопок и UI

### Кнопка "Полная генерация"
- **Показывается:** при статусе `pre_generated`
- **Скрыта:** во всех остальных случаях
- **Неактивна:** при `full_generating`

### Прогресс-бар генерации
- **Показывается:** при `pre_generating` и `full_generating`
- **Тип:** indeterminate (бесконечный)
- **Цвет:** соответствует цвету статуса

### Прогресс-бар завершенности
- **Базовая структура:** 40% от общего
- **Полная генерация:** 60% от общего
- **pre_generated:** максимум 40%
- **full_generated:** 100%

## 🔍 Отладка статусов

### Проверка через tinker
```php
php artisan tinker

// Получение документа
$doc = App\Models\Document::find(1);

// Проверка статуса
$doc->status;                         // Enum статуса
$doc->status->value;                  // Строковое значение
$doc->status->getLabel();             // Русское название

// Проверка возможностей
$doc->status->canStartFullGeneration();
$doc->status->isGenerating();
$doc->status->isFinal();
```

### Изменение статуса для тестирования
```php
use App\Enums\DocumentStatus;

$doc = App\Models\Document::find(1);
$doc->status = DocumentStatus::PRE_GENERATED;
$doc->save();
```

### API проверка
```bash
# Получение статуса через API
curl -X GET "http://localhost/documents/1/status" | jq '.'

# Только статус
curl -X GET "http://localhost/documents/1/status" | jq '.status'

# Можно ли запустить полную генерацию
curl -X GET "http://localhost/documents/1/status" | jq '.can_start_full_generation'
```

## 📈 Процент завершенности

### Формула расчета
```php
$completionPoints = 0;
$totalPoints = 10;

// Базовая структура (40%)
if (has_contents) $completionPoints += 2;
if (has_objectives) $completionPoints += 2;

// Полная генерация (60%)
if (has_detailed_contents) $completionPoints += 3;
if (has_introduction) $completionPoints += 1.5;
if (has_conclusion) $completionPoints += 1.5;

$percentage = ($completionPoints / $totalPoints) * 100;
```

### Примеры
- **draft:** 0%
- **pre_generated:** 40% (есть contents + objectives)
- **full_generated:** 100% (все компоненты)
- **частично полная:** 70% (есть detailed_contents, но нет introduction/conclusion)

## 🚦 Условия для действий

### Запуск полной генерации
```php
// Условие: статус pre_generated И наличие ссылок
$document->status === DocumentStatus::PRE_GENERATED && 
!empty($document->structure['references'])

// Или через метод
$document->status->canStartFullGenerationWithReferences($document)
```

**Важно:** Полная генерация теперь требует:
1. Статус документа `pre_generated`
2. Наличие сгенерированных ссылок в структуре документа

### Утверждение документа
```php
// Условия (любое из них)
$document->status === DocumentStatus::PRE_GENERATED ||
$document->status === DocumentStatus::FULL_GENERATED
```

### Отображение кнопки скачивания
```php
// Документ должен иметь хотя бы базовую структуру
!empty($document->structure['contents']) && 
!empty($document->structure['objectives'])
```