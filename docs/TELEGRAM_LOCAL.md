# Локальный запуск Telegram бота

Этот документ описывает как запустить Telegram бота локально для разработки и тестирования.

## 🚀 Быстрый старт

### 1. Настройка .env файла

Добавьте в ваш `.env` файл следующие переменные:

```env
# Основные настройки бота
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook

# Локальные настройки (опционально)
TELEGRAM_LOCAL_MODE=false
```

### 2. Получение токена бота

1. Найдите [@BotFather](https://t.me/BotFather) в Telegram
2. Отправьте команду `/newbot`
3. Следуйте инструкциям для создания бота
4. Сохраните полученный токен в переменную `TELEGRAM_BOT_TOKEN`
5. Сохраните username бота (без @) в переменную `TELEGRAM_BOT_USERNAME`

### 3. Запуск бота

Используйте удобный скрипт для запуска:

```bash
# Сделать скрипт исполняемым
chmod +x scripts/telegram-local.sh

# Запустить бота локально
./scripts/telegram-local.sh start
```

Или напрямую через artisan:

```bash
php artisan telegram:run-local
```

## 📋 Доступные команды

### Управление ботом

```bash
# Запуск локально (long polling)
php artisan telegram:run-local

# Запуск в тестовом режиме
php artisan telegram:run-local --test-mode

# Проверка статуса бота
php artisan telegram:status

# Установка webhook (для продакшена)
php artisan telegram:set-webhook
```

### Тестирование

```bash
# Интерактивное тестирование
php artisan telegram:test

# Создать тестового пользователя
php artisan telegram:test --create-test-user

# Тестировать уведомления для конкретного пользователя
php artisan telegram:test --user-id=1

# Тестировать конкретное уведомление
php artisan telegram:test --user-id=1 --notification=document_ready
```

### Использование скрипта

```bash
# Показать помощь
./scripts/telegram-local.sh help

# Запустить бота
./scripts/telegram-local.sh start

# Тестовый режим
./scripts/telegram-local.sh test

# Проверить статус
./scripts/telegram-local.sh status

# Установить webhook
./scripts/telegram-local.sh webhook

# Тестировать уведомления
./scripts/telegram-local.sh test-notify
```

## 🧪 Тестовый режим

Тестовый режим позволяет симулировать сообщения от пользователей без реального Telegram API:

```bash
php artisan telegram:run-local --test-mode
```

В этом режиме:
- Автоматически создаются тестовые сообщения
- Можно указать ID пользователя для тестирования
- Все логи выводятся в консоль
- Не требуется реальное взаимодействие с Telegram

## 🔧 Настройка разработки

### Локальный режим vs Webhook

**Локальный режим (Long Polling):**
- ✅ Простая настройка
- ✅ Не требует HTTPS
- ✅ Отлично для разработки
- ❌ Менее производительный
- ❌ Не подходит для продакшена

**Webhook режим:**
- ✅ Высокая производительность
- ✅ Подходит для продакшена
- ❌ Требует HTTPS
- ❌ Сложнее настройка

### Переключение между режимами

```bash
# Для локальной разработки
php artisan telegram:run-local

# Для продакшена
php artisan telegram:set-webhook
```

## 📨 Тестирование уведомлений

### Создание тестового пользователя

```bash
php artisan telegram:test --create-test-user
```

Команда попросит ввести:
- Ваш Telegram ID (получите у [@userinfobot](https://t.me/userinfobot))
- Ваш username в Telegram

### Тестирование различных уведомлений

```bash
# Интерактивное меню
php artisan telegram:test

# Быстрое тестирование
php artisan telegram:test --user-id=1 --notification=document_ready
```

Доступные типы уведомлений:
- `document_started` - Начало генерации документа
- `document_ready` - Готовность документа
- `document_error` - Ошибка генерации
- `balance_topup` - Пополнение баланса

## 🐛 Отладка

### Логи

Все события бота логируются в:
- `storage/logs/laravel.log`
- Консольный вывод при локальном запуске

### Проверка статуса

```bash
php artisan telegram:status
```

Эта команда покажет:
- Статус подключения к Telegram API
- Информацию о боте
- Статус webhook'а
- Настройки из .env

### Частые проблемы

**"TELEGRAM_BOT_TOKEN не настроен"**
- Проверьте наличие токена в `.env` файле
- Убедитесь что токен правильный

**"Ошибка при подключении к Telegram API"**
- Проверьте интернет соединение
- Проверьте правильность токена
- Убедитесь что бот не заблокирован

**"Webhook уже установлен"**
- Для локального режима webhook автоматически удаляется
- Если проблема сохраняется, удалите webhook вручную

## 🔄 Workflow разработки

### Рекомендуемый процесс:

1. **Настройка окружения:**
   ```bash
   # Скопировать пример конфигурации
   cp .env.example .env
   
   # Добавить токен бота в .env
   vim .env
   ```

2. **Локальная разработка:**
   ```bash
   # Запустить бота локально
   ./scripts/telegram-local.sh start
   ```

3. **Тестирование:**
   ```bash
   # Создать тестового пользователя
   php artisan telegram:test --create-test-user
   
   # Протестировать уведомления
   ./scripts/telegram-local.sh test-notify
   ```

4. **Деплой в продакшен:**
   ```bash
   # Установить webhook
   php artisan telegram:set-webhook
   ```

## 💡 Полезные советы

- Используйте отдельных ботов для разработки и продакшена
- Логи помогают отладить проблемы - следите за ними
- Тестовый режим ускоряет разработку
- Всегда проверяйте статус бота перед началом работы

## 🆘 Поддержка

Если возникли проблемы:

1. Проверьте логи в `storage/logs/laravel.log`
2. Запустите `php artisan telegram:status`
3. Убедитесь что все переменные в `.env` настроены правильно
4. Попробуйте тестовый режим для изоляции проблемы 