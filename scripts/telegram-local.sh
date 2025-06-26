#!/bin/bash

# Скрипт для локального запуска Telegram бота
# Использование: ./scripts/telegram-local.sh [команда]

PROJECT_DIR=$(dirname "$(dirname "$(realpath "$0")")")
cd "$PROJECT_DIR"

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функция для вывода с цветом
print_colored() {
    echo -e "${1}${2}${NC}"
}

# Функция для проверки .env файла
check_env() {
    if [ ! -f .env ]; then
        print_colored $RED "❌ Файл .env не найден!"
        print_colored $YELLOW "💡 Создайте .env файл на основе .env.example"
        exit 1
    fi

    # Проверяем наличие токена бота
    if ! grep -q "TELEGRAM_BOT_TOKEN=" .env || grep -q "TELEGRAM_BOT_TOKEN=$" .env; then
        print_colored $RED "❌ TELEGRAM_BOT_TOKEN не настроен в .env файле!"
        print_colored $YELLOW "💡 Получите токен у @BotFather и добавьте в .env:"
        print_colored $YELLOW "   TELEGRAM_BOT_TOKEN=your_bot_token_here"
        exit 1
    fi

    if ! grep -q "TELEGRAM_BOT_USERNAME=" .env || grep -q "TELEGRAM_BOT_USERNAME=$" .env; then
        print_colored $YELLOW "⚠️  TELEGRAM_BOT_USERNAME не настроен в .env файле"
    fi
}

# Функция для показа помощи
show_help() {
    print_colored $BLUE "🤖 Telegram Bot - Локальный запуск"
    echo ""
    print_colored $GREEN "Доступные команды:"
    echo "  start         - Запустить бота локально (long polling)"
    echo "  test          - Запустить в тестовом режиме"
    echo "  status        - Проверить статус бота"
    echo "  webhook       - Установить webhook для продакшена"
    echo "  test-notify   - Протестировать уведомления"
    echo "  debug         - Полная диагностика бота"
    echo "  help          - Показать эту справку"
    echo ""
    print_colored $YELLOW "Примеры использования:"
    echo "  ./scripts/telegram-local.sh start"
    echo "  ./scripts/telegram-local.sh test"
    echo "  ./scripts/telegram-local.sh status"
    echo "  ./scripts/telegram-local.sh debug"
    echo ""
    print_colored $BLUE "Настройка .env файла:"
    echo "  TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather"
    echo "  TELEGRAM_BOT_USERNAME=your_bot_username"
}

# Функция для запуска бота
start_bot() {
    print_colored $GREEN "🚀 Запуск Telegram бота локально..."
    print_colored $YELLOW "⚠️  Webhook будет автоматически отключен для локального режима"
    print_colored $BLUE "💡 Для остановки нажмите Ctrl+C"
    echo ""
    
    php artisan telegram:run-local
}

# Функция для тестового режима
test_mode() {
    print_colored $GREEN "🧪 Запуск в тестовом режиме..."
    php artisan telegram:run-local --test-mode
}

# Функция для проверки статуса
check_status() {
    print_colored $GREEN "🔍 Проверка статуса бота..."
    php artisan telegram:status
}

# Функция для установки webhook
set_webhook() {
    print_colored $GREEN "🔗 Установка webhook..."
    php artisan telegram:set-webhook
}

# Функция для тестирования уведомлений
test_notifications() {
    print_colored $GREEN "📨 Тестирование уведомлений..."
    php artisan telegram:test
}

# Функция для диагностики
run_debug() {
    print_colored $GREEN "🔍 Полная диагностика бота..."
    php artisan telegram:debug
}

# Основная логика
case "${1:-help}" in
    "start")
        check_env
        start_bot
        ;;
    "test")
        check_env
        test_mode
        ;;
    "status")
        check_env
        check_status
        ;;
    "webhook")
        check_env
        set_webhook
        ;;
    "test-notify")
        check_env
        test_notifications
        ;;
    "debug")
        check_env
        run_debug
        ;;
    "help"|*)
        show_help
        ;;
esac 