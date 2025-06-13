# GPTpult

Проект на Laravel с Vue.js, Inertia.js и Quasar Framework.

## Требования

- Docker
- Docker Compose

## Установка и запуск

1. Клонируйте репозиторий:
```bash
git clone <repository-url>
cd gptpult
```

2. Скопируйте файл .env.example в .env:
```bash
cp .env.example .env
```

3. Настройте переменные окружения в файле .env:
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=gptpult
DB_USERNAME=gptpult
DB_PASSWORD=your_password
```

4. Запустите контейнеры:
```bash
docker-compose up -d
```

5. Установите зависимости и выполните миграции:
```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

6. Установите зависимости Node.js и соберите фронтенд:
```bash
docker-compose exec app npm install
docker-compose exec app npm run build
```

Приложение будет доступно по адресу: http://localhost:8000

## Команды для разработки

- Запуск контейнеров: `docker-compose up -d`
- Остановка контейнеров: `docker-compose down`
- Просмотр логов: `docker-compose logs -f`
- Выполнение команд в контейнере: `docker-compose exec app <command>`
