<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }
        .hero-section {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 100px 0;
            border-radius: 0 0 30px 30px;
            margin-bottom: 50px;
        }
        .price-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .price-card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: #4f46e5;
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            font-size: 1.2rem;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .section-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .desktop-only {
                display: none;
            }
        }
        @media (min-width: 769px) {
            .mobile-only {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">
                <span class="mobile-only">GPT Pult</span>
                <span class="desktop-only">GPT Pult - ИИ для учебы</span>
            </a>
        </div>
    </nav>

    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Подготовься и сдай работу за час</h1>
            <p class="lead mb-5">Пульт управления учебой</p>
            <a href="/new" class="btn btn-primary btn-lg">Создать</a>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-md-8 text-center">
                <h2 class="section-title">Почему выбирают нас?</h2>
                <p class="lead">Сравните варианты  и убедитесь сами</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-4 mb-4">
                <div class="price-card text-center">
                    <h3>У фрилансеров</h3>
                    <p class="display-4 mb-4">от ₽3000</p>
                    <ul class="list-unstyled">
                        <li>Долгое ожидание</li>
                        <li>Риск некачественной работы</li>
                        <li>Нет гарантий</li>
                        <li>Фрилансер не даст знаний</li>
                        <li>Не поможет защитить работу</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="price-card text-center">
                    <h3>Наш сервис</h3>
                    <p class="display-4 mb-4">от ₽290</p>
                    <ul class="list-unstyled">
                        <li>Мгновенный результат</li>
                        <li>Гарантия качества</li>
                        <li>Поддержка 24/7</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4 mb-4">
                <div class="price-card text-center">
                    <i class="fas fa-bolt fa-3x mb-3 text-primary"></i>
                    <h3>Мгновенный результат</h3>
                    <p class="lead">Получите готовую работу за считанные минуты. Не нужно ждать ответа от исполнителя или согласовывать правки.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="price-card text-center">
                    <i class="fas fa-shield-alt fa-3x mb-3 text-primary"></i>
                    <h3>Гарантия качества</h3>
                    <p class="lead">Все работы проходят проверку на уникальность и соответствие требованиям. Мы гарантируем высокое качество.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="price-card text-center">
                    <i class="fas fa-headset fa-3x mb-3 text-primary"></i>
                    <h3>Поддержка 24/7</h3>
                    <p class="lead">Наша команда всегда готова помочь вам с любыми вопросами. Обращайтесь в любое время суток.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html> 