<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPT Пульт - Твой ИИ для учебы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/v4.css') }}" rel="stylesheet">
    
    <style>
        /* Дизайн 3: Градиентный с анимациями */
        .hero-design3 {
            min-height: 100vh;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #ffeaa7);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            position: relative;
            display: flex;
            align-items: center;
            padding: 80px 0;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Анимированные частицы */
        .particles-design3 {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle-design3 {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: particleFloat 6s linear infinite;
        }

        .particle-design3:nth-child(2n) {
            width: 2px;
            height: 2px;
            animation-duration: 8s;
        }

        .particle-design3:nth-child(3n) {
            width: 6px;
            height: 6px;
            animation-duration: 4s;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .hero-content-design3 {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .hero-badge-design3 {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 12px 24px;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 0.9rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .hero-title-design3 {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-subtitle-design3 {
            font-size: 1.4rem;
            margin-bottom: 3rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease-out 0.2s both;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .features-grid-design3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .feature-item-design3 {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            animation: scaleIn 0.6s ease-out;
        }

        .feature-item-design3:nth-child(2) {
            animation-delay: 0.1s;
        }

        .feature-item-design3:nth-child(3) {
            animation-delay: 0.2s;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .feature-item-design3:hover {
            transform: translateY(-5px) scale(1.05);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-icon-design3 {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
            animation: rotateIcon 3s linear infinite;
        }

        @keyframes rotateIcon {
            0%, 90%, 100% { transform: rotate(0deg); }
            95% { transform: rotate(10deg); }
        }

        .feature-title-design3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-description-design3 {
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .cta-container-design3 {
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .cta-button-design3 {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 50%, #45b7d1 100%);
            background-size: 200% 200%;
            animation: gradientButton 3s ease infinite;
            color: white;
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        @keyframes gradientButton {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .cta-button-design3:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            color: white;
            text-decoration: none;
        }

        .cta-button-design3 i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* Морфинг фигуры */
        .morphing-shape-design3 {
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation: morph 8s ease-in-out infinite;
        }

        .morphing-shape-design3:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .morphing-shape-design3:nth-child(2) {
            bottom: 20%;
            right: 15%;
            animation-delay: -4s;
            width: 150px;
            height: 150px;
        }

        @keyframes morph {
            0%, 100% {
                border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
                transform: rotate(0deg);
            }
            25% {
                border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%;
                transform: rotate(90deg);
            }
            50% {
                border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%;
                transform: rotate(180deg);
            }
            75% {
                border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%;
                transform: rotate(270deg);
            }
        }

        @media (max-width: 768px) {
            .hero-title-design3 {
                font-size: 2.5rem;
            }
            
            .features-grid-design3 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .feature-item-design3 {
                padding: 1.2rem;
            }
            
            .morphing-shape-design3 {
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

    <!-- Hero Section - Дизайн 3 -->
    <section class="hero-design3">
        <!-- Анимированные частицы -->
        <div class="particles-design3">
            <div class="particle-design3" style="left: 10%; animation-delay: 0s;"></div>
            <div class="particle-design3" style="left: 20%; animation-delay: 1s;"></div>
            <div class="particle-design3" style="left: 30%; animation-delay: 2s;"></div>
            <div class="particle-design3" style="left: 40%; animation-delay: 3s;"></div>
            <div class="particle-design3" style="left: 50%; animation-delay: 4s;"></div>
            <div class="particle-design3" style="left: 60%; animation-delay: 5s;"></div>
            <div class="particle-design3" style="left: 70%; animation-delay: 0.5s;"></div>
            <div class="particle-design3" style="left: 80%; animation-delay: 1.5s;"></div>
            <div class="particle-design3" style="left: 90%; animation-delay: 2.5s;"></div>
        </div>

        <!-- Морфинг фигуры -->
        <div class="morphing-shape-design3"></div>
        <div class="morphing-shape-design3"></div>
        
        <div class="container">
            <div class="hero-content-design3">
                <div class="hero-badge-design3">
                    <i class="fas fa-magic me-2"></i>
                    Революция в образовании
                </div>
                
                <h1 class="hero-title-design3">
                    Создавай работы <br>
                    мечты с помощью ИИ
                </h1>
                
                <p class="hero-subtitle-design3">
                    Превращай идеи в качественные учебные работы за считанные минуты. <br>
                    Магия искусственного интеллекта в твоих руках.
                </p>
                
                <div class="features-grid-design3">
                    <div class="feature-item-design3">
                        <i class="fas fa-bolt feature-icon-design3"></i>
                        <div class="feature-title-design3">Мгновенно</div>
                        <div class="feature-description-design3">За 10 минут до готовой работы</div>
                    </div>
                    
                    <div class="feature-item-design3">
                        <i class="fas fa-star feature-icon-design3"></i>
                        <div class="feature-title-design3">Качественно</div>
                        <div class="feature-description-design3">99% уникальность текста</div>
                    </div>
                    
                    <div class="feature-item-design3">
                        <i class="fas fa-heart feature-icon-design3"></i>
                        <div class="feature-title-design3">Просто</div>
                        <div class="feature-description-design3">Интуитивный интерфейс</div>
                    </div>
                </div>
                
                <div class="cta-container-design3">
                    <a href="/new" class="cta-button-design3">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        Создать магию
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 