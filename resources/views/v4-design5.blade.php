<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPT Пульт - Твой ИИ для учебы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Clash+Display:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/v4.css') }}" rel="stylesheet">
    
    <style>
        /* Дизайн 5: Асимметричный современный */
        .hero-design5 {
            min-height: 100vh;
            background: #0a0a0a;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            font-family: 'Clash Display', sans-serif;
        }

        /* Геометрические фигуры фона */
        .geometric-bg-design5 {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .geometric-shape-design5 {
            position: absolute;
            background: linear-gradient(45deg, #ff0080, #7928ca);
            clip-path: polygon(0 0, 100% 0, 80% 100%, 0% 100%);
            animation: geometricMove 20s ease-in-out infinite;
        }

        .geometric-shape-design5:nth-child(1) {
            width: 300px;
            height: 600px;
            top: -100px;
            right: 20%;
            background: linear-gradient(45deg, #00d4ff, #090979);
            animation-delay: 0s;
        }

        .geometric-shape-design5:nth-child(2) {
            width: 200px;
            height: 400px;
            bottom: -50px;
            left: 10%;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            clip-path: polygon(20% 0%, 100% 0, 100% 80%, 0% 100%);
            animation-delay: -10s;
        }

        .geometric-shape-design5:nth-child(3) {
            width: 150px;
            height: 300px;
            top: 30%;
            left: 60%;
            background: linear-gradient(45deg, #ffeaa7, #fab1a0);
            clip-path: polygon(0 20%, 100% 0, 80% 100%, 0% 80%);
            animation-delay: -5s;
        }

        @keyframes geometricMove {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }
            25% {
                transform: translateY(-30px) rotate(5deg);
                opacity: 0.9;
            }
            50% {
                transform: translateY(-10px) rotate(-3deg);
                opacity: 0.6;
            }
            75% {
                transform: translateY(-20px) rotate(8deg);
                opacity: 0.8;
            }
        }

        .hero-container-design5 {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 6rem;
            align-items: center;
        }

        /* Левая колонка с асимметричным контентом */
        .hero-main-content-design5 {
            color: white;
            position: relative;
        }

        .hero-badge-design5 {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            position: absolute;
            left: -60px;
            top: 0;
            background: linear-gradient(135deg, #ff0080, #7928ca);
            color: white;
            padding: 20px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .hero-title-design5 {
            font-size: 5rem;
            font-weight: 800;
            line-height: 0.9;
            margin-bottom: 2rem;
            position: relative;
            background: linear-gradient(135deg, #ffffff 0%, #00d4ff 50%, #ff0080 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-title-design5::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 120px;
            height: 6px;
            background: linear-gradient(90deg, #ff0080, #00d4ff);
            border-radius: 3px;
        }

        .hero-subtitle-design5 {
            font-size: 1.3rem;
            color: #b8b8b8;
            margin-bottom: 3rem;
            line-height: 1.5;
            max-width: 500px;
            position: relative;
            font-weight: 400;
        }

        .hero-subtitle-design5::before {
            content: '"';
            position: absolute;
            left: -30px;
            top: -10px;
            font-size: 4rem;
            color: #ff0080;
            opacity: 0.3;
        }

        .cta-group-design5 {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .cta-primary-design5 {
            background: linear-gradient(135deg, #ff0080 0%, #7928ca 100%);
            color: white;
            padding: 18px 36px;
            border: none;
            border-radius: 0;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            clip-path: polygon(0 0, calc(100% - 15px) 0, 100% 100%, 15px 100%);
        }

        .cta-primary-design5:hover {
            transform: translateX(5px);
            color: white;
            text-decoration: none;
        }

        .cta-secondary-design5 {
            color: #00d4ff;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .cta-secondary-design5::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #00d4ff;
            transition: width 0.3s ease;
        }

        .cta-secondary-design5:hover::after {
            width: 100%;
        }

        .cta-secondary-design5:hover {
            color: #00d4ff;
            text-decoration: none;
        }

        /* Статистики с необычным дизайном */
        .stats-design5 {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }

        .stat-item-design5 {
            text-align: left;
        }

        .stat-number-design5 {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #00d4ff 0%, #ff0080 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            line-height: 1;
        }

        .stat-label-design5 {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        /* Правая колонка с процессом */
        .hero-sidebar-design5 {
            position: relative;
        }

        .process-container-design5 {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0;
            padding: 3rem 2rem;
            clip-path: polygon(0 0, calc(100% - 20px) 0, 100% 20px, 100% 100%, 20px 100%, 0 calc(100% - 20px));
        }

        .process-title-design5 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .process-steps-design5 {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .process-step-design5 {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .process-step-design5:last-child {
            border-bottom: none;
        }

        .step-number-design5 {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #00d4ff 0%, #ff0080 100%);
            color: white;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.1rem;
            flex-shrink: 0;
            clip-path: polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px));
        }

        .step-content-design5 {
            color: #b8b8b8;
        }

        .step-title-design5 {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .step-desc-design5 {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Адаптивность */
        @media (max-width: 1200px) {
            .hero-container-design5 {
                grid-template-columns: 1fr;
                gap: 4rem;
                text-align: center;
            }
            
            .hero-badge-design5 {
                writing-mode: horizontal-tb;
                text-orientation: initial;
                position: static;
                display: inline-block;
                margin-bottom: 2rem;
            }
            
            .hero-subtitle-design5::before {
                display: none;
            }
            
            .stats-design5 {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .hero-title-design5 {
                font-size: 3rem;
            }
            
            .cta-group-design5 {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-design5 {
                flex-direction: column;
                gap: 1rem;
            }
            
            .geometric-shape-design5 {
                display: none;
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

    <!-- Hero Section - Дизайн 5 -->
    <section class="hero-design5">
        <!-- Геометрический фон -->
        <div class="geometric-bg-design5">
            <div class="geometric-shape-design5"></div>
            <div class="geometric-shape-design5"></div>
            <div class="geometric-shape-design5"></div>
        </div>
        
        <div class="hero-container-design5">
            <!-- Основной контент -->
            <div class="hero-main-content-design5">
                <div class="hero-badge-design5">
                    AI • POWERED
                </div>
                
                <h1 class="hero-title-design5">
                    БУДУЩЕЕ<br>
                    УЧЕБЫ<br>
                    СЕГОДНЯ
                </h1>
                
                <p class="hero-subtitle-design5">
                    Революционный подход к созданию учебных работ. 
                    Искусственный интеллект нового поколения превращает 
                    твои идеи в профессиональные документы.
                </p>
                
                <div class="cta-group-design5">
                    <a href="/new" class="cta-primary-design5">
                        НАЧАТЬ СЕЙЧАС
                    </a>
                    
                    <a href="#features" class="cta-secondary-design5">
                        УЗНАТЬ БОЛЬШЕ
                    </a>
                </div>
                
                <div class="stats-design5">
                    <div class="stat-item-design5">
                        <span class="stat-number-design5">10</span>
                        <div class="stat-label-design5">минут</div>
                    </div>
                    <div class="stat-item-design5">
                        <span class="stat-number-design5">99%</span>
                        <div class="stat-label-design5">уникальность</div>
                    </div>
                    <div class="stat-item-design5">
                        <span class="stat-number-design5">24/7</span>
                        <div class="stat-label-design5">поддержка</div>
                    </div>
                </div>
            </div>
            
            <!-- Боковая панель с процессом -->
            <div class="hero-sidebar-design5">
                <div class="process-container-design5">
                    <h3 class="process-title-design5">Процесс</h3>
                    
                    <div class="process-steps-design5">
                        <div class="process-step-design5">
                            <div class="step-number-design5">01</div>
                            <div class="step-content-design5">
                                <div class="step-title-design5">Описание</div>
                                <div class="step-desc-design5">
                                    Расскажи о своей работе и требованиях
                                </div>
                            </div>
                        </div>
                        
                        <div class="process-step-design5">
                            <div class="step-number-design5">02</div>
                            <div class="step-content-design5">
                                <div class="step-title-design5">Генерация</div>
                                <div class="step-desc-design5">
                                    ИИ создает структуру и содержание
                                </div>
                            </div>
                        </div>
                        
                        <div class="process-step-design5">
                            <div class="step-number-design5">03</div>
                            <div class="step-content-design5">
                                <div class="step-title-design5">Результат</div>
                                <div class="step-desc-design5">
                                    Получи готовую работу высокого качества
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 