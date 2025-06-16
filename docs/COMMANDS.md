# Справочник команд - Система генерации документов

## 🚀 Команды демонстрации

### Полная демонстрация системы
```bash
# Создание документа и запуск базовой генерации
php artisan demo:full-system --user-id=1 --topic="Планирование IT-проекта"

# С кастомным пользователем и темой
php artisan demo:full-system --user-id=2 --topic="Внедрение CRM системы"
```

### Демонстрация цикла статусов
```bash
# Показывает полный цикл изменения статусов документа
php artisan test:status-flow --user-id=1
```

## ⚙️ Управление очередями

### Запуск воркеров
```bash
# Запуск воркера для очереди документов
php artisan queue:work-documents

# С кастомным таймаутом (по умолчанию 300 сек)
php artisan queue:work-documents --timeout=600

# Запуск обычного воркера Laravel
php artisan queue:work --queue=document_creates

# Запуск с verbose выводом
php artisan queue:work --queue=document_creates --verbose
```

### Мониторинг очередей
```bash
# Статус очередей
php artisan queue:monitor

# Количество задач в очереди
php artisan queue:size

# Очистка неудачных задач
php artisan queue:flush

# Перезапуск всех неудачных задач
php artisan queue:retry all

# Перезапуск конкретной задачи
php artisan queue:retry 123
```

## 🧪 Тестирование генерации

### Базовая генерация
```bash
# Создание документа с базовой генерацией
php artisan test:document-generation --topic="Тема документа"

# С указанием пользователя
php artisan test:document-generation --topic="Тема" --user-id=2
```

### Полная генерация
```bash
# Тестирование полной генерации существующего документа
php artisan test:full-generation 1

# Если документ не готов, команда предложит изменить статус
php artisan test:full-generation 1
```

## 📋 Управление документами

### Утверждение документов
```bash
# Утверждение документа
php artisan document:approve 1

# Утверждение нескольких документов
php artisan document:approve 1 2 3
```

### Изменение статусов
```bash
# Через tinker
php artisan tinker

>>> $doc = App\Models\Document::find(1)
>>> $doc->status = App\Enums\DocumentStatus::PRE_GENERATED
>>> $doc->save()

>>> # Проверка статуса
>>> $doc->status->getLabel()
>>> $doc->status->canStartFullGeneration()
```

## 🔍 Отладка и логирование

### Просмотр логов
```bash
# Логи очередей в реальном времени
tail -f storage/logs/queue.log

# Поиск ошибок
grep "ERROR" storage/logs/queue.log

# Поиск по конкретному документу
grep "document_id.*123" storage/logs/queue.log

# Последние 100 строк
tail -n 100 storage/logs/queue.log
```

### Логи Laravel
```bash
# Общие логи Laravel
tail -f storage/logs/laravel.log

# Логи с фильтрацией по дате
grep "2024-01-20" storage/logs/laravel.log
```

## 🗄 База данных

### Миграции
```bash
# Выполнение миграций
php artisan migrate

# Откат миграций
php artisan migrate:rollback

# Статус миграций
php artisan migrate:status

# Пересоздание БД
php artisan migrate:fresh --seed
```

### Сидеры
```bash
# Запуск всех сидеров
php artisan db:seed

# Конкретный сидер
php artisan db:seed --class=DocumentTypeSeeder
```

### Работа с данными
```bash
# Через tinker
php artisan tinker

>>> # Получение всех документов
>>> App\Models\Document::all()

>>> # Поиск по статусу
>>> App\Models\Document::where('status', 'pre_generated')->get()

>>> # Подсчет по статусам
>>> App\Models\Document::groupBy('status')->selectRaw('status, count(*) as count')->get()
```

## 🔧 Разработка и отладка

### Очистка кэша
```bash
# Очистка всех кэшей
php artisan optimize:clear

# Конкретные кэши
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Генерация файлов
```bash
# Новая команда
php artisan make:command NewCommand

# Новый Job
php artisan make:job NewJob

# Новый контроллер
php artisan make:controller NewController

# Новая миграция
php artisan make:migration create_new_table
```

### IDE Helper
```bash
# Генерация helper файлов для IDE
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta
```

## 📊 API тестирование

### Curl примеры
```bash
# Проверка статуса документа
curl -X GET "http://localhost/documents/1/status" \
  -H "Accept: application/json"

# Запуск полной генерации
curl -X POST "http://localhost/documents/1/generate-full" \
  -H "Accept: application/json"

# Создание документа
curl -X POST "http://localhost/documents" \
  -H "Content-Type: application/json" \
  -d '{"document_type_id": 1, "topic": "Тест"}'
```

### HTTPie примеры
```bash
# Установка HTTPie
pip install httpie

# Проверка статуса
http GET localhost/documents/1/status

# Запуск генерации
http POST localhost/documents/1/generate-full

# Создание документа
http POST localhost/documents document_type_id:=1 topic="Тест"
```

## 🌐 Frontend разработка

### Node.js команды
```bash
# Установка зависимостей
npm install

# Разработка (hot reload)
npm run dev

# Сборка для продакшена
npm run build

# Проверка типов
npm run type-check

# Форматирование кода
npm run format
```

### Vite команды
```bash
# Запуск dev сервера
npm run dev

# Предпросмотр продакшен сборки
npm run preview

# Анализ бандла
npm run build -- --analyze
```

## 🚀 Продакшен

### Оптимизация
```bash
# Оптимизация для продакшена
php artisan optimize

# Кэширование конфигурации
php artisan config:cache

# Кэширование маршрутов
php artisan route:cache

# Кэширование представлений
php artisan view:cache
```

### Деплой
```bash
# Обновление зависимостей
composer install --optimize-autoloader --no-dev

# Миграции
php artisan migrate --force

# Очистка кэшей
php artisan optimize:clear

# Кэширование
php artisan optimize

# Сборка фронтенда
npm ci
npm run build
```

## 📋 Полезные алиасы

Добавьте в `~/.bashrc` или `~/.zshrc`:

```bash
# Алиасы для GPTPult
alias artisan='php artisan'
alias tinker='php artisan tinker'
alias demo='php artisan demo:full-system'
alias qwork='php artisan queue:work-documents'
alias qmon='php artisan queue:monitor'
alias logs='tail -f storage/logs/queue.log'

# Git алиасы
alias gs='git status'
alias ga='git add'
alias gc='git commit'
alias gp='git push'
alias gl='git log --oneline'
```

## 🔄 Workflow для разработки

```bash
# 1. Создание нового функционала
git checkout -b feature/new-feature

# 2. Тестирование
php artisan demo:full-system --topic="Тест новой функции"
php artisan queue:work-documents

# 3. Проверка логов
tail -f storage/logs/queue.log

# 4. Тестирование API
curl -X GET "http://localhost/documents/1/status"

# 5. Коммит изменений
git add .
git commit -m "feat: добавлена новая функция"
git push origin feature/new-feature
``` 