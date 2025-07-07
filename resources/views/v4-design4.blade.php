<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPT Пульт - Твой ИИ для учебы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/v4.css') }}" rel="stylesheet">
    
    <style>
        /* Дизайн 4: Карточный дизайн */
        .hero-design4 {
            min-height: 100vh;
            background: #f8fafc;
            position: relative;
            padding: 120px 0 80px;
            font-family: 'Manrope', sans-serif;
        }

        .hero-design4::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.12) 0%, transparent 50%);
            z-index: 1;
        }

        .hero-container-design4 {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Левая колонка с заголовком */
        .hero-text-card-design4 {
            background: white;
            border-radius: 32px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.1);
            position: relative;
            overflow: hidden;
        }

        .hero-text-card-design4::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        }

        .hero-badge-design4 {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            border: 1px solid #a7f3d0;
            border-radius: 50px;
            padding: 8px 20px;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .hero-title-design4 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            color: #0f172a;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #374151 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle-design4 {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .hero-button-design4 {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        }

        .hero-button-design4:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4);
            color: white;
            text-decoration: none;
        }

        /* Правая колонка с карточками шагов */
        .steps-container-design4 {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .step-card-design4 {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .step-card-design4:nth-child(1) {
            animation: slideInRight 0.6s ease-out;
        }

        .step-card-design4:nth-child(2) {
            animation: slideInRight 0.6s ease-out 0.2s both;
        }

        .step-card-design4:nth-child(3) {
            animation: slideInRight 0.6s ease-out 0.4s both;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .step-card-design4:hover {
            transform: translateY(-8px) translateX(-4px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .step-card-design4::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #06b6d4, #3b82f6, #6366f1);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .step-card-design4:hover::before {
            transform: scaleX(1);
        }

        .step-header-design4 {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .step-number-design4 {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .step-title-design4 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .step-description-design4 {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.5;
            margin: 0;
        }

        /* Статистические карточки внизу */
        .stats-section-design4 {
            margin-top: 5rem;
            position: relative;
            z-index: 2;
        }

        .stats-grid-design4 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .stat-card-design4 {
            background: white;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card-design4::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #ef4444, #ec4899);
        }

        .stat-card-design4:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 48px rgba(99, 102, 241, 0.15);
        }

        .stat-number-design4 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #6366f1;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label-design4 {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
        }

        /* Мобильная адаптивность */
        @media (max-width: 1024px) {
            .hero-container-design4 {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
            
            .hero-text-card-design4 {
                order: 1;
                text-align: center;
            }
            
            .steps-container-design4 {
                order: 0;
            }
        }

        @media (max-width: 768px) {
            .hero-design4 {
                padding: 100px 0 60px;
            }
            
            .hero-container-design4 {
                padding: 0 1rem;
                gap: 2rem;
            }
            
            .hero-text-card-design4 {
                padding: 2rem;
            }
            
            .hero-title-design4 {
                font-size: 2.5rem;
            }
            
            .step-card-design4 {
                padding: 1.5rem;
            }
            
            .stats-grid-design4 {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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

    <!-- Hero Section - Дизайн 4 -->
    <section class="hero-design4">
        <div class="hero-container-design4">
            <!-- Левая колонка с основным контентом -->
            <div class="hero-text-card-design4">
                <div class="hero-badge-design4">
                    <i class="fas fa-certificate me-2"></i>
                    Качество гарантировано
                </div>
                
                <h1 class="hero-title-design4">
                    Твой помощник <br>
                    в учебе с ИИ
                </h1>
                
                <p class="hero-subtitle-design4">
                    Создавай качественные учебные работы быстро и легко. 
                    Профессиональный подход к каждому заданию с использованием 
                    современных технологий искусственного интеллекта.
                </p>
                
                <a href="/new" class="hero-button-design4">
                    <i class="fas fa-play"></i>
                    Начать создание
                </a>
            </div>
            
            <!-- Правая колонка с карточками шагов -->
            <div class="steps-container-design4">
                <div class="step-card-design4">
                    <div class="step-header-design4">
                        <div class="step-number-design4">1</div>
                        <h3 class="step-title-design4">Расскажи о задаче</h3>
                    </div>
                    <p class="step-description-design4">
                        Опиши тему, тип работы и основные требования. 
                        Чем подробнее описание, тем качественнее результат.
                    </p>
                </div>
                
                <div class="step-card-design4">
                    <div class="step-header-design4">
                        <div class="step-number-design4">2</div>
                        <h3 class="step-title-design4">Получи структуру</h3>
                    </div>
                    <p class="step-description-design4">
                        ИИ создаст план работы, основные разделы и тезисы. 
                        Ты сможешь внести изменения и дополнения.
                    </p>
                </div>
                
                <div class="step-card-design4">
                    <div class="step-header-design4">
                        <div class="step-number-design4">3</div>
                        <h3 class="step-title-design4">Забери результат</h3>
                    </div>
                    <p class="step-description-design4">
                        Получи готовую работу с проверкой уникальности 
                        и всеми необходимыми элементами оформления.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Статистические карточки -->
        <div class="stats-section-design4">
            <div class="container">
                <div class="stats-grid-design4">
                    <div class="stat-card-design4">
                        <span class="stat-number-design4">10</span>
                        <span class="stat-label-design4">минут до результата</span>
                    </div>
                    
                    <div class="stat-card-design4">
                        <span class="stat-number-design4">99%</span>
                        <span class="stat-label-design4">уникальность текста</span>
                    </div>
                    
                    <div class="stat-card-design4">
                        <span class="stat-number-design4">24/7</span>
                        <span class="stat-label-design4">техническая поддержка</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 