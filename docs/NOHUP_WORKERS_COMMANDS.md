# Команды для работы с воркерами через nohup

## 📋 Запуск воркеров

### Запуск параллельного воркера (основной метод)
```bash
nohup php artisan queue:work-parallel > worker_parallel.log 2>&1 & echo $! > worker_parallel.pid
```

### Запуск отдельных воркеров (fallback)
```bash
# Воркер 1
nohup php artisan queue:work --queue=document_creates --timeout=300 --tries=3 > worker1.log 2>&1 & echo $! > worker1.pid

# Воркер 2 
nohup php artisan queue:work --queue=document_creates --timeout=300 --tries=3 > worker2.log 2>&1 & echo $! > worker2.pid

# Воркер 3
nohup php artisan queue:work --queue=document_creates --timeout=300 --tries=3 > worker3.log 2>&1 & echo $! > worker3.pid
```

### Запуск воркера по умолчанию
```bash
nohup php artisan queue:work --timeout=300 --tries=3 > worker_default.log 2>&1 & echo $! > worker_default.pid
```

## 🔍 Мониторинг воркеров

### Проверка запущенных процессов
```bash
# Все queue:work процессы
ps aux | grep "queue:work"

# Более детальный вывод
ps aux | grep "queue:work" | grep -v grep

# С нумерацией строк
ps aux | grep "queue:work" | grep -v grep | nl
```

### Проверка PID файлов
```bash
# Проверить какие PID файлы существуют
ls -la *.pid 2>/dev/null

# Содержимое PID файлов
for pid_file in *.pid; do
    if [[ -f "$pid_file" ]]; then
        echo "$pid_file: $(cat $pid_file)"
    fi
done
```

### Проверка активности процессов по PID
```bash
# Проверить один PID
if [[ -f "worker_parallel.pid" ]]; then
    pid=$(cat worker_parallel.pid)
    if ps -p $pid > /dev/null; then
        echo "Воркер $pid активен"
    else
        echo "Воркер $pid не найден"
    fi
fi

# Проверить все PID файлы
for pid_file in worker*.pid; do
    if [[ -f "$pid_file" ]]; then
        pid=$(cat "$pid_file")
        if ps -p $pid > /dev/null; then
            echo "✅ $pid_file: PID $pid активен"
        else
            echo "❌ $pid_file: PID $pid не найден"
        fi
    fi
done
```

## 📊 Просмотр логов

### Просмотр в реальном времени
```bash
# Параллельный воркер
tail -f worker_parallel.log

# Отдельные воркеры
tail -f worker1.log worker2.log worker3.log

# Все логи воркеров
tail -f worker*.log
```

### Просмотр последних записей
```bash
# Последние 50 строк
tail -n 50 worker_parallel.log

# Последние 20 строк всех логов
for log in worker*.log; do
    echo "=== $log ==="
    tail -n 20 "$log"
    echo
done
```

### Поиск в логах
```bash
# Поиск ошибок
grep -i error worker*.log

# Поиск по конкретному тексту
grep "Processing" worker*.log

# Поиск с указанием файла
grep -H "Exception" worker*.log
```

## ⏹️ Остановка воркеров

### Мягкая остановка по PID файлам
```bash
# Остановить параллельный воркер
if [[ -f "worker_parallel.pid" ]]; then
    pid=$(cat worker_parallel.pid)
    kill $pid && rm worker_parallel.pid
    echo "Остановлен воркер PID: $pid"
fi

# Остановить все воркеры через PID файлы
for pid_file in worker*.pid; do
    if [[ -f "$pid_file" ]]; then
        pid=$(cat "$pid_file")
        if kill $pid 2>/dev/null; then
            echo "✅ Остановлен $pid_file: PID $pid"
            rm "$pid_file"
        else
            echo "❌ Не удалось остановить $pid_file: PID $pid"
        fi
    fi
done
```

### Принудительная остановка
```bash
# Найти и убить все queue:work процессы
pkill -f "queue:work"

# Более агрессивная остановка
pkill -9 -f "queue:work"

# Проверить что все остановлены
ps aux | grep "queue:work" | grep -v grep
```

### Остановка конкретного воркера
```bash
# По названию команды
pkill -f "queue:work-parallel"

# По PID напрямую (если знаете PID)
kill 12345  # замените на реальный PID
```

## 📈 Статистика очередей

### Размер очередей
```bash
# Основная очередь
php artisan queue:size

# Конкретная очередь
php artisan queue:size document_creates

# Все очереди
php artisan queue:monitor
```

### Неудачные задачи
```bash
# Показать неудачные задачи
php artisan queue:failed

# Повторить неудачные задачи
php artisan queue:retry all

# Очистить неудачные задачи
php artisan queue:flush
```

## 🧹 Очистка и обслуживание

### Очистка логов
```bash
# Очистить большие логи (>10MB)
for log in worker*.log; do
    if [[ -f "$log" ]] && [[ $(stat -c%s "$log" 2>/dev/null || echo 0) -gt 10485760 ]]; then
        echo "Очищаю большой лог: $log"
        > "$log"
    fi
done

# Полная очистка всех логов
> worker_parallel.log
> worker1.log
> worker2.log  
> worker3.log
> worker_default.log
```

### Очистка старых PID файлов
```bash
# Удалить PID файлы неактивных процессов
for pid_file in worker*.pid; do
    if [[ -f "$pid_file" ]]; then
        pid=$(cat "$pid_file")
        if ! ps -p $pid > /dev/null; then
            echo "Удаляю старый PID файл: $pid_file"
            rm "$pid_file"
        fi
    fi
done
```

## 🚀 Полезные алиасы

Добавьте в ваш `.bashrc` или `.bash_profile`:

```bash
# Алиасы для воркеров
alias workers-status='ps aux | grep "queue:work" | grep -v grep'
alias workers-stop='for pid_file in worker*.pid; do [[ -f "$pid_file" ]] && kill $(cat "$pid_file") && rm "$pid_file"; done'
alias workers-logs='tail -f worker*.log'
alias queue-size='php artisan queue:size document_creates'

# Запуск воркеров
alias start-parallel='nohup php artisan queue:work-parallel > worker_parallel.log 2>&1 & echo $! > worker_parallel.pid'
alias start-workers='nohup php artisan queue:work --queue=document_creates --timeout=300 --tries=3 > worker1.log 2>&1 & echo $! > worker1.pid'
```

## 🔧 Скрипты автоматизации

### Скрипт проверки статуса (check-workers.sh)
```bash
#!/bin/bash
echo "=== Статус воркеров ==="
ps aux | grep "queue:work" | grep -v grep | nl

echo -e "\n=== PID файлы ==="
for pid_file in worker*.pid; do
    if [[ -f "$pid_file" ]]; then
        pid=$(cat "$pid_file")
        if ps -p $pid > /dev/null; then
            echo "✅ $pid_file: PID $pid активен"
        else
            echo "❌ $pid_file: PID $pid не найден"
        fi
    fi
done

echo -e "\n=== Размер очереди ==="
php artisan queue:size document_creates
```

### Скрипт автозапуска (auto-start.sh)
```bash
#!/bin/bash
# Проверить есть ли воркеры, если нет - запустить

workers_count=$(ps aux | grep "queue:work" | grep -v grep | wc -l)

if [[ $workers_count -eq 0 ]]; then
    echo "Воркеры не найдены. Запускаю..."
    nohup php artisan queue:work-parallel > worker_parallel.log 2>&1 & echo $! > worker_parallel.pid
    echo "Запущен параллельный воркер"
else
    echo "Найдено $workers_count воркеров. Запуск не требуется."
fi
```

## 📝 Тестирование

### Тест параллельной генерации
```bash
# Запустить тест генерации документов
php artisan test:parallel-generation --count=5

# С детальным выводом
php artisan test:parallel-generation --count=3 --verbose
```

### Проверка логики кнопки выхода
```bash
php artisan test:logout-button-logic
```

---

**Примечание**: Замените пути и параметры под ваше окружение. Сохраните этот файл для быстрого доступа к командам. 