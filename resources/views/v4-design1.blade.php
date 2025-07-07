<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPT Пульт - Твой ИИ для учебы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto+Slab:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/v4.css') }}" rel="stylesheet">
    
    <style>
        /* Дизайн 1: Модерн с большими карточками */
        :root {
            --primary: #6366f1;
            --primary-light: #a5b4fc;
            --secondary: #f1f5f9;
            --dark: #0f172a;
            --gray: #64748b;
        }

        .hero-design1 {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            display: flex;
            align-items: center;
            padding: 80px 0;
            overflow: hidden;
        }

        .hero-design1::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: float 20s ease-in-out infinite;
        }

        .hero-content-design1 {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .hero-badge-design1 {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 12px 24px;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .hero-title-design1 {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle-design1 {
            font-size: 1.4rem;
            margin-bottom: 4rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .steps-grid-design1 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .step-card-design1 {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .step-card-design1:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .step-card-design1::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b, #d97706);
        }

        .step-number-design1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .step-title-design1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: white;
        }

        .step-description-design1 {
            font-size: 0.95rem;
            opacity: 0.8;
            line-height: 1.6;
        }

        .cta-button-design1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #1f2937;
            padding: 20px 50px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(251, 191, 36, 0.4);
        }

        .cta-button-design1:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(251, 191, 36, 0.5);
            color: #1f2937;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .hero-title-design1 {
                font-size: 2.5rem;
            }
            
            .steps-grid-design1 {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .step-card-design1 {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar (используем существующий) -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('gptpult.png') }}" alt="GPT Пульт" class="navbar-logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Возможности</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Цены</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Контакты</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section - Дизайн 1 -->
    <section class="hero-design1">
        <div class="container">
            <div class="hero-content-design1">
                <div class="hero-badge-design1">
                    <i class="fas fa-sparkles me-2"></i>
                    Новый способ создания работ
                </div>
                
                <h1 class="hero-title-design1">
                    Конструктор учебных работ нового поколения
                </h1>
                
                <p class="hero-subtitle-design1">
                    Создавай качественные работы за 10 минут с помощью ИИ. 
                    Просто, быстро и эффективно.
                </p>
                
                <div class="steps-grid-design1">
                    <div class="step-card-design1">
                        <div class="step-number-design1">01</div>
                        <h3 class="step-title-design1">Опиши задачу</h3>
                        <p class="step-description-design1">
                            Расскажи о своей работе: тип, тема, требования. Чем детальнее - тем лучше результат.
                        </p>
                    </div>
                    
                    <div class="step-card-design1">
                        <div class="step-number-design1">02</div>
                        <h3 class="step-title-design1">Получи структуру</h3>
                        <p class="step-description-design1">
                            ИИ создаст план работы и основные тезисы. Проверь и внеси правки при необходимости.
                        </p>
                    </div>
                    
                    <div class="step-card-design1">
                        <div class="step-number-design1">03</div>
                        <h3 class="step-title-design1">Подготовься к сдаче</h3>
                        <p class="step-description-design1">
                            Получи готовую работу с проверкой уникальности и подготовься к успешной защите.
                        </p>
                    </div>
                </div>
                
                <a href="/new" class="cta-button-design1">
                    <i class="fas fa-rocket"></i>
                    Создать работу сейчас
                </a>
            </div>
        </div>
    </section>

    <!-- Остальные секции остаются прежними -->
    <!-- ... -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 