#!/bin/bash

# Скрипт для комплексного тестирования параллельных сценариев
# Использование: ./test_parallel_scenarios.sh [document_id]

set -e

DOCUMENT_ID=${1:-29}
LOG_FILE="storage/logs/debug_generation.log"
RESULTS_DIR="parallel_test_results"

echo "🚀 КОМПЛЕКСНОЕ ТЕСТИРОВАНИЕ ПАРАЛЛЕЛЬНЫХ СЦЕНАРИЕВ"
echo "=================================================="
echo "Документ ID: $DOCUMENT_ID"
echo "Лог файл: $LOG_FILE"
echo "Результаты: $RESULTS_DIR"
echo ""

# Создаем директорию для результатов
mkdir -p "$RESULTS_DIR"

# Функция для вывода разделителя
print_separator() {
    echo ""
    echo "=================================================="
    echo "$1"
    echo "=================================================="
}

# Функция для сохранения результатов
save_test_results() {
    local test_name="$1"
    local timestamp=$(date +"%Y%m%d_%H%M%S")
    local result_file="$RESULTS_DIR/${test_name}_${timestamp}.log"
    
    if [[ -f "$LOG_FILE" ]]; then
        cp "$LOG_FILE" "$result_file"
        echo "Результаты сохранены в: $result_file"
    fi
}

# Функция для очистки лог файла
clear_log() {
    if [[ -f "$LOG_FILE" ]]; then
        > "$LOG_FILE"
        echo "Лог файл очищен"
    fi
}

# Функция для анализа результатов
analyze_results() {
    local test_name="$1"
    
    if [[ ! -f "$LOG_FILE" ]]; then
        echo "❌ Лог файл не найден"
        return 1
    fi
    
    echo "📊 Анализ результатов теста: $test_name"
    echo "----------------------------------------"
    
    # Подсчитываем различные метрики
    local total_requests=$(grep -c "Attempting to add message" "$LOG_FILE" 2>/dev/null || echo "0")
    local successful_requests=$(grep -c "Message added successfully" "$LOG_FILE" 2>/dev/null || echo "0")
    local failed_requests=$(grep -c "Failed to add message" "$LOG_FILE" 2>/dev/null || echo "0")
    local active_run_errors=$(grep -c "while a run.*is active" "$LOG_FILE" 2>/dev/null || echo "0")
    local race_conditions=$(grep -c "race condition\|concurrent" "$LOG_FILE" 2>/dev/null || echo "0")
    
    echo "Всего запросов: $total_requests"
    echo "Успешных: $successful_requests"
    echo "Неудачных: $failed_requests"
    echo "Ошибок активных run: $active_run_errors"
    echo "Race conditions: $race_conditions"
    
    # Вычисляем процент успеха
    if [[ $total_requests -gt 0 ]]; then
        local success_rate=$(echo "scale=2; $successful_requests * 100 / $total_requests" | bc -l)
        echo "Процент успеха: ${success_rate}%"
        
        # Оценка результатов
        if (( $(echo "$success_rate < 50" | bc -l) )); then
            echo "❌ Критически низкий процент успеха!"
        elif (( $(echo "$success_rate < 80" | bc -l) )); then
            echo "⚠️ Низкий процент успеха"
        else
            echo "✅ Хороший процент успеха"
        fi
    else
        echo "⚠️ Нет данных о запросах"
    fi
    
    echo ""
}

# Функция для проверки документа
check_document() {
    print_separator "📋 Проверка документа #$DOCUMENT_ID"
    
    if php artisan tinker --execute="
        \$doc = App\\Models\\Document::find($DOCUMENT_ID);
        if (\$doc) {
            echo 'Документ найден: ' . \$doc->title . PHP_EOL;
            echo 'Статус: ' . \$doc->status->value . PHP_EOL;
            echo 'Thread ID: ' . (\$doc->thread_id ?: 'НЕТ') . PHP_EOL;
            echo 'Структура: ' . (!empty(\$doc->structure) ? 'ЕСТЬ' : 'НЕТ') . PHP_EOL;
        } else {
            echo 'Документ не найден!' . PHP_EOL;
            exit(1);
        }
    "; then
        echo "✅ Документ готов для тестирования"
    else
        echo "❌ Ошибка при проверке документа"
        exit 1
    fi
}

# Сценарий 1: Умеренное параллельное тестирование
test_moderate_parallel() {
    print_separator "🔄 СЦЕНАРИЙ 1: Умеренное параллельное тестирование"
    
    echo "Параметры:"
    echo "- Количество воркеров: 3"
    echo "- Продолжительность: 30 секунд"
    echo "- Задержка между запросами: 2 секунды"
    echo ""
    
    clear_log
    
    if php artisan debug:parallel-workers $DOCUMENT_ID --workers=3 --duration=30 --delay=2; then
        echo "✅ Умеренное параллельное тестирование завершено"
        analyze_results "moderate_parallel"
        save_test_results "moderate_parallel"
    else
        echo "❌ Ошибка при умеренном параллельном тестировании"
        save_test_results "moderate_parallel_failed"
    fi
}

# Сценарий 2: Интенсивное параллельное тестирование
test_intensive_parallel() {
    print_separator "⚡ СЦЕНАРИЙ 2: Интенсивное параллельное тестирование"
    
    echo "Параметры:"
    echo "- Количество воркеров: 5"
    echo "- Продолжительность: 45 секунд"
    echo "- Задержка между запросами: 1 секунда"
    echo ""
    
    clear_log
    
    if php artisan debug:parallel-workers $DOCUMENT_ID --workers=5 --duration=45 --delay=1; then
        echo "✅ Интенсивное параллельное тестирование завершено"
        analyze_results "intensive_parallel"
        save_test_results "intensive_parallel"
    else
        echo "❌ Ошибка при интенсивном параллельном тестировании"
        save_test_results "intensive_parallel_failed"
    fi
}

# Сценарий 3: Стресс-тестирование с небольшой нагрузкой
test_light_stress() {
    print_separator "🔥 СЦЕНАРИЙ 3: Легкое стресс-тестирование"
    
    echo "Параметры:"
    echo "- Количество воркеров: 3"
    echo "- Итераций на воркер: 5"
    echo "- Задержка: 1 секунда"
    echo ""
    
    clear_log
    
    if php artisan debug:stress-test $DOCUMENT_ID --workers=3 --iterations=5; then
        echo "✅ Легкое стресс-тестирование завершено"
        analyze_results "light_stress"
        save_test_results "light_stress"
    else
        echo "❌ Ошибка при легком стресс-тестировании"
        save_test_results "light_stress_failed"
    fi
}

# Сценарий 4: Экстремальное стресс-тестирование
test_extreme_stress() {
    print_separator "💥 СЦЕНАРИЙ 4: Экстремальное стресс-тестирование"
    
    echo "⚠️ ВНИМАНИЕ: Этот тест может вызвать много ошибок и потратить токены!"
    echo "Параметры:"
    echo "- Количество воркеров: 5"
    echo "- Итераций на воркер: 10"
    echo "- Без задержек между запросами"
    echo ""
    
    echo "Хотите запустить экстремальное стресс-тестирование? (y/N)"
    read -r response
    
    if [[ "$response" =~ ^[Yy]$ ]]; then
        clear_log
        
        if php artisan debug:stress-test $DOCUMENT_ID --workers=5 --iterations=10 --no-delay; then
            echo "✅ Экстремальное стресс-тестирование завершено"
            analyze_results "extreme_stress"
            save_test_results "extreme_stress"
        else
            echo "❌ Ошибка при экстремальном стресс-тестировании"
            save_test_results "extreme_stress_failed"
        fi
    else
        echo "🔶 Экстремальное стресс-тестирование пропущено"
    fi
}

# Сценарий 5: Тестирование последовательности
test_sequence() {
    print_separator "🔄 СЦЕНАРИЙ 5: Тестирование последовательности операций"
    
    echo "Этот тест проверяет как система работает при последовательных операциях"
    echo "после параллельного тестирования"
    echo ""
    
    clear_log
    
    if php artisan debug:single-subtopic $DOCUMENT_ID; then
        echo "✅ Тестирование последовательности завершено"
        analyze_results "sequence_test"
        save_test_results "sequence_test"
    else
        echo "❌ Ошибка при тестировании последовательности"
        save_test_results "sequence_test_failed"
    fi
}

# Функция для создания итогового отчета
create_summary_report() {
    print_separator "📊 СОЗДАНИЕ ИТОГОВОГО ОТЧЕТА"
    
    local report_file="$RESULTS_DIR/summary_report_$(date +"%Y%m%d_%H%M%S").md"
    
    cat > "$report_file" << EOF
# Отчет о параллельном тестировании

## Информация о тестировании

- **Дата**: $(date)
- **Документ ID**: $DOCUMENT_ID
- **Окружение**: $(php -v | head -n 1)
- **Система**: $(uname -a)

## Выполненные сценарии

### Сценарий 1: Умеренное параллельное тестирование
- Воркеры: 3
- Продолжительность: 30с
- Задержка: 2с

### Сценарий 2: Интенсивное параллельное тестирование
- Воркеры: 5
- Продолжительность: 45с
- Задержка: 1с

### Сценарий 3: Легкое стресс-тестирование
- Воркеры: 3
- Итерации: 5
- Задержка: 1с

### Сценарий 4: Экстремальное стресс-тестирование
- Воркеры: 5
- Итерации: 10
- Без задержек

### Сценарий 5: Тестирование последовательности
- Одиночные операции после параллельного тестирования

## Результаты

EOF

    # Добавляем анализ каждого файла результатов
    for result_file in "$RESULTS_DIR"/*.log; do
        if [[ -f "$result_file" ]]; then
            local filename=$(basename "$result_file")
            echo "### Результаты из $filename" >> "$report_file"
            echo "" >> "$report_file"
            
            # Анализируем файл
            local total_requests=$(grep -c "Attempting to add message" "$result_file" 2>/dev/null || echo "0")
            local successful_requests=$(grep -c "Message added successfully" "$result_file" 2>/dev/null || echo "0")
            local active_run_errors=$(grep -c "while a run.*is active" "$result_file" 2>/dev/null || echo "0")
            
            echo "- Всего запросов: $total_requests" >> "$report_file"
            echo "- Успешных: $successful_requests" >> "$report_file"
            echo "- Ошибок активных run: $active_run_errors" >> "$report_file"
            
            if [[ $total_requests -gt 0 ]]; then
                local success_rate=$(echo "scale=2; $successful_requests * 100 / $total_requests" | bc -l)
                echo "- Процент успеха: ${success_rate}%" >> "$report_file"
            fi
            
            echo "" >> "$report_file"
        fi
    done
    
    echo "📄 Итоговый отчет создан: $report_file"
}

# Основной поток выполнения
main() {
    # Проверяем документ
    check_document
    
    # Создаем директорию для результатов
    mkdir -p "$RESULTS_DIR"
    
    # Запускаем все сценарии
    test_moderate_parallel
    sleep 5
    
    test_intensive_parallel
    sleep 5
    
    test_light_stress
    sleep 5
    
    test_extreme_stress
    sleep 5
    
    test_sequence
    
    # Создаем итоговый отчет
    create_summary_report
    
    print_separator "🎉 ВСЕ ТЕСТЫ ЗАВЕРШЕНЫ"
    echo "📄 Результаты сохранены в директории: $RESULTS_DIR"
    echo "📊 Доступные команды для дальнейшего анализа:"
    echo "   tail -f $LOG_FILE"
    echo "   grep -i 'error' $RESULTS_DIR/*.log"
    echo "   grep -i 'active run' $RESULTS_DIR/*.log"
    echo ""
    echo "🔧 Команды для повторного запуска отдельных сценариев:"
    echo "   php artisan debug:parallel-workers $DOCUMENT_ID --workers=3 --duration=30"
    echo "   php artisan debug:stress-test $DOCUMENT_ID --workers=3 --iterations=5"
    echo "   php artisan debug:single-subtopic $DOCUMENT_ID"
    echo ""
    echo "📊 Мониторинг thread:"
    echo "   php artisan debug:thread-monitor [thread_id] --duration=60"
}

# Запуск основной функции
main "$@" 