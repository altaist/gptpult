# Настройка Google reCAPTCHA v3

Эта инструкция поможет вам настроить Google reCAPTCHA v3 для защиты форм от ботов.

## 1. Получение ключей reCAPTCHA

1. Перейдите на https://www.google.com/recaptcha/admin/create
2. Войдите в Google аккаунт
3. Заполните форму:
   - **Label**: Название вашего сайта (например, "GPTPult")
   - **reCAPTCHA type**: Выберите "reCAPTCHA v3"
   - **Domains**: Добавьте ваши домены:
     - `localhost` (для разработки)
     - `127.0.0.1` (для разработки)
     - Ваш продакшн домен (например, `gptpult.ru`)
4. Согласитесь с условиями использования
5. Нажмите "Submit"

После создания вы получите:
- **Site Key** - для использования на фронтенде
- **Secret Key** - для проверки на бэкенде

## 2. Настройка переменных окружения

Добавьте следующие переменные в ваш `.env` файл:

```env
# Google reCAPTCHA v3 настройки
RECAPTCHA_SITE_KEY=your_site_key_here
RECAPTCHA_SECRET_KEY=your_secret_key_here
RECAPTCHA_ENABLED=true
```

### Описание переменных:

- `RECAPTCHA_SITE_KEY` - Публичный ключ для фронтенда
- `RECAPTCHA_SECRET_KEY` - Секретный ключ для бэкенда
- `RECAPTCHA_ENABLED` - Включить/выключить reCAPTCHA (true/false)

## 3. Где используется reCAPTCHA

### 3.1 Создание документа (NewDocument.vue)
- **Действие**: `document_create`
- **Минимальный скор**: 0.5
- **Описание**: Проверяется при нажатии кнопки "Создать работу"

### 3.2 Страница входа (Login.vue)
- **Действие**: `login_page`
- **Минимальный скор**: 0.5
- **Описание**: Проверяется при загрузке страницы логина

## 4. Как работает защита

### reCAPTCHA v3
- Работает **невидимо** для пользователя
- Анализирует поведение пользователя на сайте
- Возвращает **скор от 0.0 до 1.0**:
  - 1.0 = очень вероятно человек
  - 0.0 = очень вероятно бот
- Мы используем минимальный скор **0.5**

### Логика проверки
1. Фронтенд генерирует токен при выполнении действия
2. Токен отправляется на сервер вместе с формой
3. Сервер проверяет токен через Google API
4. Если проверка не пройдена - возвращается ошибка

## 5. Отключение reCAPTCHA

Для отключения установите в `.env`:
```env
RECAPTCHA_ENABLED=false
```

При отключении:
- Скрипты reCAPTCHA не загружаются
- Проверки пропускаются
- Формы работают без ограничений

## 6. Тестирование

### Тестовые ключи Google (для разработки)
```env
# НЕ используйте в продакшене!
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

Эти ключи всегда возвращают успешный результат.

### Проверка работы
1. Откройте страницу создания документа
2. Заполните форму и отправьте
3. Проверьте логи Laravel: `tail -f storage/logs/laravel.log`
4. Найдите записи с `reCAPTCHA verification result`

## 7. Решение проблем

### Ошибка "reCAPTCHA not configured"
- Проверьте наличие ключей в `.env`
- Убедитесь, что кеш конфига очищен: `php artisan config:clear`

### Ошибка "Invalid domain"
- Добавьте ваш домен в настройки reCAPTCHA
- Для localhost используйте `127.0.0.1` или `localhost`

### Низкий скор reCAPTCHA
- Проверьте, не блокируют ли ad-блокеры скрипты Google
- Убедитесь, что пользователь взаимодействует со страницей

### Скрипт reCAPTCHA не загружается
- Проверьте консоль браузера на ошибки
- Убедитесь, что site_key корректный
- Проверьте доступность `www.google.com/recaptcha/api.js`

## 8. Безопасность

### Важные моменты:
- **Никогда не публикуйте Secret Key** в открытом коде
- Используйте HTTPS в продакшене
- Регулярно обновляйте ключи при подозрении на компрометацию
- Мониторьте логи на подозрительную активность

### Логирование:
Все проверки reCAPTCHA логируются с уровнем INFO:
```php
Log::info('reCAPTCHA verification result', [
    'success' => true/false,
    'score' => 0.0-1.0,
    'action' => 'document_create',
    'hostname' => 'yourdomain.com'
]);
```

## 9. Дополнительная информация

- [Документация reCAPTCHA v3](https://developers.google.com/recaptcha/docs/v3)
- [Руководство по интеграции](https://developers.google.com/recaptcha/docs/verify)
- [FAQ по reCAPTCHA](https://developers.google.com/recaptcha/docs/faq) 