<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPT Пульт - Твой ИИ для учебы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --accent-color: #f59e0b;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --border-light: #e2e8f0;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-light);
            transition: all 0.3s ease;
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .navbar-logo {
            height: 45px;
            width: auto;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover .navbar-logo {
            transform: scale(1.05);
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
            padding: 140px 0 200px;
            overflow: hidden;
        }

        /* Декоративные элементы */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(147, 197, 253, 0.04));
            animation: float 20s ease-in-out infinite;
        }

        .floating-circle:nth-child(1) {
            width: 400px;
            height: 400px;
            top: 5%;
            left: -8%;
            animation-delay: 0s;
        }

        .floating-circle:nth-child(2) {
            width: 250px;
            height: 250px;
            top: 60%;
            right: -5%;
            animation-delay: -7s;
        }

        .floating-circle:nth-child(3) {
            width: 180px;
            height: 180px;
            top: 15%;
            right: 25%;
            animation-delay: -12s;
        }

        .floating-circle:nth-child(4) {
            width: 120px;
            height: 120px;
            bottom: 25%;
            left: 20%;
            animation-delay: -5s;
        }

        /* Геометрические фигуры */
        .geometric-shape {
            position: absolute;
            opacity: 0.4;
            animation: rotate 25s linear infinite;
        }

        .shape-triangle {
            width: 0;
            height: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            border-bottom: 70px solid rgba(99, 102, 241, 0.12);
            top: 12%;
            right: 8%;
            animation-delay: -4s;
        }

        .shape-square {
            width: 80px;
            height: 80px;
            background: rgba(245, 158, 11, 0.1);
            transform: rotate(45deg);
            bottom: 35%;
            left: 8%;
            animation-delay: -10s;
        }

        .shape-hexagon {
            width: 60px;
            height: 60px;
            background: rgba(147, 197, 253, 0.12);
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            top: 45%;
            right: 5%;
            animation-delay: -15s;
        }

        /* Основной контент */
        .hero-container {
            position: relative;
            z-index: 2;
            max-width: 1100px;
            margin: 0 auto;
            text-align: center;
            padding: 0 20px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(79, 70, 229, 0.08));
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 50px;
            padding: 14px 28px;
            margin-bottom: 2.5rem;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .hero-badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .hero-badge i {
            font-size: 18px;
            animation: pulse 2s infinite;
        }

        .hero-title {
            font-size: 5.5rem;
            font-weight: 800;
            line-height: 1.05;
            margin-bottom: 2.5rem;
            color: var(--text-primary);
            letter-spacing: -0.025em;
        }

        .hero-title-accent {
            color: var(--primary-color);
            position: relative;
            display: inline-block;
        }

        .hero-title-accent::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 3px;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            font-weight: 400;
            margin-bottom: 5rem;
            color: var(--text-secondary);
            line-height: 1.6;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Преимущества */
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin: 8rem 0 6rem 0;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .benefit-card {
            background: white;
            padding: 2rem 1.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            text-align: center;
        }

        .benefit-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }

        .benefit-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 1.8rem;
            box-shadow: var(--shadow-sm);
        }

        .benefit-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.8rem;
        }

        .benefit-description {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Горизонтальные мини-шаги */
        .mini-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4rem;
            margin: 6rem 0 4rem 0;
            flex-wrap: wrap;
            position: relative;
        }

        .mini-steps::before {
            content: '';
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            width: 65%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            z-index: 0;
        }

        .mini-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            text-align: center;
            position: relative;
            z-index: 1;
            min-width: 200px;
            transition: all 0.3s ease;
        }

        .mini-step:hover {
            transform: translateY(-8px);
        }

        .step-number-circle {
            width: 70px;
            height: 70px;
            background: white;
            border: 4px solid var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color);
            box-shadow: var(--shadow-lg);
            position: relative;
            transition: all 0.3s ease;
        }

        .mini-step:hover .step-number-circle {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
        }

        .step-info {
            background: white;
            padding: 20px 24px;
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .mini-step:hover .step-info {
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }

        .step-title-mini {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .step-description-mini {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        /* CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 22px 45px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
            color: white;
            text-decoration: none;
        }

        .cta-button:hover::before {
            left: 100%;
        }

        .cta-button i {
            font-size: 1.3rem;
        }

        /* Контейнеры кнопок */
        .desktop-cta {
            margin: 3rem 0 8rem 0;
        }

        .mobile-cta {
            display: none;
            margin: 2rem 0 0 0;
        }

        /* Мобильный блок с шагами - упрощенная версия */
        .mobile-steps-block {
            display: none;
            margin: 2rem 0;
            text-align: center;
        }

        .mobile-steps-simple {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.2rem;
        }

        .mobile-step-simple {
            background: white;
            color: var(--primary-color);
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 2px solid var(--primary-color);
            width: 100%;
            max-width: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .mobile-step-simple:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-hover);
        }

        .mobile-step-text {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
            text-align: center;
            line-height: 1.3;
        }

        .mobile-step-arrow {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin: 0;
        }

        .mobile-step-arrow:last-of-type {
            display: none;
        }

        /* Статистические карточки */
        .stats-section {
            margin-top: 8rem;
            position: relative;
        }

        .stats-container {
            display: flex;
            justify-content: center;
            gap: 2.5rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: linear-gradient(135deg, white, #f8fafc);
            padding: 40px 32px;
            border-radius: 28px;
            text-align: center;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            min-width: 180px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(99, 102, 241, 0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.6rem;
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Дополнительные декоративные линии */
        .decoration-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .decoration-line {
            position: absolute;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.08), transparent);
            height: 2px;
            width: 250px;
            animation: lineMove 10s ease-in-out infinite;
        }

        .decoration-line:nth-child(1) {
            top: 20%;
            left: 5%;
            animation-delay: 0s;
        }

        .decoration-line:nth-child(2) {
            top: 65%;
            right: 10%;
            animation-delay: -5s;
        }

        .decoration-line:nth-child(3) {
            bottom: 25%;
            left: 25%;
            animation-delay: -2.5s;
        }

        /* Анимации */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) translateX(0px);
            }
            25% {
                transform: translateY(-25px) translateX(15px);
            }
            50% {
                transform: translateY(-15px) translateX(-20px);
            }
            75% {
                transform: translateY(-30px) translateX(8px);
            }
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        @keyframes lineMove {
            0%, 100% {
                opacity: 0;
                transform: translateX(-150px);
            }
            50% {
                opacity: 1;
                transform: translateX(150px);
            }
        }

        /* Анимации для мобильного блока */
        .mobile-steps-block {
            animation: slideInUp 0.6s ease-out;
        }

        .mobile-step-simple {
            transition: all 0.3s ease;
        }

        .mobile-step-simple:hover .mobile-step-number-simple {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Адаптивность */
        @media (max-width: 1200px) {
            .hero-title {
                font-size: 4.5rem;
            }
            
            .benefits-grid {
                gap: 1.5rem;
                margin: 6rem 0 4rem 0;
            }
            
            .desktop-cta {
                margin: 2.5rem 0 6rem 0;
            }
            
            .floating-circle:nth-child(1) {
                width: 300px;
                height: 300px;
            }
            
            .floating-circle:nth-child(2) {
                width: 200px;
                height: 200px;
            }
        }

        @media (max-width: 992px) {
            .hero-section {
                padding: 120px 0 150px;
            }
            
            .hero-title {
                font-size: 4rem;
            }
            
            .hero-subtitle {
                font-size: 1.3rem;
                margin-bottom: 4rem;
            }
            
            .mini-steps {
                gap: 3rem;
                margin: 4rem 0 3rem 0;
            }
            
            .desktop-cta {
                margin: 2rem 0 5rem 0;
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                max-width: 500px;
                margin: 5rem 0 3rem 0;
            }
            
            .stats-container {
                gap: 2rem;
            }
            
            .stats-section {
                margin-top: 5rem;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0 30px;
                min-height: 100vh;
            }
            
            .hero-title {
                font-size: 2.8rem;
                margin-bottom: 1rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
                margin-bottom: 2rem;
            }
            
            /* Скрываем полный контент на мобильных */
            .benefits-grid,
            .mini-steps,
            .stats-section,
            .desktop-cta {
                display: none;
            }

            /* Показываем мобильные элементы */
            .mobile-steps-block,
            .mobile-cta {
                display: block;
            }

            .mini-steps::before {
                display: none;
            }
            
            .cta-button {
                padding: 18px 36px;
                font-size: 1.1rem;
            }
            
            .floating-circle {
                display: none;
            }
            
            .geometric-shape {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .hero-container {
                padding: 0 15px;
            }
            
            .hero-section {
                padding: 70px 0 20px;
                min-height: 100vh;
            }
            
            .hero-title {
                font-size: 2.3rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
                margin-bottom: 1.8rem;
            }
            
            .mobile-steps-block {
                margin: 1.5rem 0;
            }
            
            .mobile-steps-simple {
                gap: 1rem;
            }
            
            .mobile-step-simple {
                max-width: 250px;
                padding: 14px 18px;
            }
            
            .mobile-step-text {
                font-size: 0.9rem;
            }
            
            .mobile-step-arrow {
                font-size: 1rem;
            }
            
            .mobile-cta {
                margin: 2rem 0 0 0;
            }
            
            .cta-button {
                padding: 16px 32px;
                font-size: 1rem;
            }
            
            .navbar-logo {
                height: 40px;
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                padding: 60px 0 15px;
                min-height: 100vh;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 0.95rem;
                margin-bottom: 1.5rem;
            }
            
            .mobile-steps-block {
                margin: 1.2rem 0;
            }
            
            .mobile-steps-simple {
                gap: 0.8rem;
            }
            
            .mobile-step-simple {
                max-width: 220px;
                padding: 12px 16px;
            }
            
            .mobile-step-text {
                font-size: 0.85rem;
            }
            
            .mobile-step-arrow {
                font-size: 0.9rem;
            }
            
            .mobile-cta {
                margin: 1.8rem 0 0 0;
            }
            
            .cta-button {
                padding: 14px 28px;
                font-size: 0.95rem;
            }
        }

        /* Footer */
        .footer {
            background: #0f1419;
            color: white;
            padding: 60px 0 30px;
            margin-top: 80px;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .footer-text {
            opacity: 0.7;
            margin-bottom: 2rem;
        }

        .footer-doc-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            line-height: 1.6;
            transition: all 0.2s ease;
            display: block;
            padding: 0.25rem 0;
        }

        .footer-doc-link:hover {
            color: #60a5fa;
            text-decoration: underline;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            text-align: center;
            opacity: 0.5;
        }
    </style>
</head>
<body>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <!-- Декоративные элементы -->
        <div class="floating-elements">
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
            <div class="floating-circle"></div>
        </div>

        <div class="geometric-shape shape-triangle"></div>
        <div class="geometric-shape shape-square"></div>
        <div class="geometric-shape shape-hexagon"></div>

        <div class="decoration-lines">
            <div class="decoration-line"></div>
            <div class="decoration-line"></div>
            <div class="decoration-line"></div>
        </div>

        <div class="hero-container">
            <h1 class="hero-title">
                Твоя учебная работа<br>
                <span class="hero-title-accent">за 10 минут</span>
            </h1>

            <p class="hero-subtitle">
                Простой и мощный инструмент для создания качественных работ с помощью ИИ<br>
            </p>

            <!-- Горизонтальные мини-шаги (только на десктопе) -->
            <div class="mini-steps">
                <div class="mini-step">
                    <div class="step-number-circle">1</div>
                    <div class="step-info">
                        <div class="step-title-mini">Опиши работу</div>
                        <div class="step-description-mini">Тема и требования</div>
                    </div>
                </div>

                <div class="mini-step">
                    <div class="step-number-circle">2</div>
                    <div class="step-info">
                        <div class="step-title-mini">Проверь структуру</div>
                        <div class="step-description-mini">Утверди содержание</div>
                    </div>
                </div>

                <div class="mini-step">
                    <div class="step-number-circle">3</div>
                    <div class="step-info">
                        <div class="step-title-mini">Получи результат</div>
                        <div class="step-description-mini">Готовая работа</div>
                    </div>
                </div>
            </div>

            <!-- Кнопка для десктопа (после шагов) -->
            <div class="desktop-cta">
                <a href="/new" class="cta-button">
                    <i class="fas fa-plus"></i>
                    <span>Создать работу</span>
                </a>
            </div>

            <!-- Мобильный блок с шагами (только на мобильных) -->
            <div class="mobile-steps-block">
                <div class="mobile-steps-simple">
                    <div class="mobile-step-simple">
                        <div class="mobile-step-text">Опиши работу</div>
                    </div>
                    
                    <div class="mobile-step-arrow">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    
                    <div class="mobile-step-simple">
                        <div class="mobile-step-text">Проверь структуру</div>
                    </div>
                    
                    <div class="mobile-step-arrow">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    
                    <div class="mobile-step-simple">
                        <div class="mobile-step-text">Получи результат</div>
                    </div>
                </div>
            </div>

            <!-- Кнопка для мобильных -->
            <div class="mobile-cta">
                <a href="/new" class="cta-button">
                    <i class="fas fa-plus"></i>
                    <span>Создать работу</span>
                </a>
            </div>

            <!-- Преимущества (только на десктопе) -->
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="benefit-title">Быстрый результат</div>
                    <div class="benefit-description">Получите готовую работу всего за 10 минут</div>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-check"></i>
                    </div>
                    <div class="benefit-title">99% уникальность</div>
                    <div class="benefit-description">Каждая работа создается с нуля и проверяется</div>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="benefit-title">Доступные цены</div>
                    <div class="benefit-description">В 10 раз дешевле, чем у фрилансеров</div>
                </div>
            </div>

            <!-- Статистика (только на десктопе) -->
            <div class="stats-section">
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number">10</div>
                        <div class="stat-label">минут</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <div class="stat-number">99%</div>
                        <div class="stat-label">уникальность</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">поддержка</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-brand">
                        GPT Пульт
                    </div>
                    <p class="footer-text">
                        Платформа для создания учебных работ с использованием 
                        искусственного интеллекта. Быстро, качественно, доступно.
                    </p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Сервис</h5>
                    <ul class="list-unstyled">
                        <li><a href="#hero" class="text-light opacity-75">О нас</a></li>
                        <li><a href="#features" class="text-light opacity-75">Как это работает</a></li>
                        <li><a href="#pricing" class="text-light opacity-75">Цены</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Документы</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ asset('docs/Политика персональных данных.docx') }}?v={{ time() }}" class="footer-doc-link" target="_blank">Политика конфиденциальности</a></li>
                        <li><a href="{{ asset('docs/Публичная оферта.docx') }}?v={{ time() }}" class="footer-doc-link" target="_blank">Публичная оферта</a></li>
                        <li><a href="{{ asset('docs/Реквизиты.docx') }}?v={{ time() }}" class="footer-doc-link" target="_blank">Реквизиты</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">Поддержка</h5>
                    <p class="opacity-75 mb-3">
                        <i class="fas fa-clock me-2"></i>24/7 поддержка
                    </p>
                    <a href="https://t.me/gptpult_help" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-telegram me-2"></i>Написать в Telegram
                    </a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 GPT Пульт. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });

        // Parallax effect for floating elements
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelectorAll('.floating-circle');
            const speed = 0.3;

            parallax.forEach((element, index) => {
                const yPos = -(scrolled * speed * (index + 1) * 0.1);
                element.style.transform = `translateY(${yPos}px)`;
            });
        });

        // Interactive hover effects for steps
        document.querySelectorAll('.mini-step').forEach(step => {
            step.addEventListener('mouseenter', function() {
                const circle = this.querySelector('.step-number-circle');
                const info = this.querySelector('.step-info');
                if (circle) circle.style.transform = 'scale(1.1)';
                if (info) info.style.transform = 'translateY(-2px)';
            });
            
            step.addEventListener('mouseleave', function() {
                const circle = this.querySelector('.step-number-circle');
                const info = this.querySelector('.step-info');
                if (circle) circle.style.transform = 'scale(1)';
                if (info) info.style.transform = 'translateY(0)';
            });
        });

        // Interactive hover effects for benefit cards
        document.querySelectorAll('.benefit-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.benefit-icon');
                if (icon) icon.style.transform = 'scale(1.1) rotate(5deg)';
            });
            
            card.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.benefit-icon');
                if (icon) icon.style.transform = 'scale(1) rotate(0deg)';
            });
        });

        // Interactive hover effects for stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.stat-icon');
                if (icon) icon.style.transform = 'scale(1.1) rotate(5deg)';
            });
            
            card.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.stat-icon');
                if (icon) icon.style.transform = 'scale(1) rotate(0deg)';
            });
        });

        // Add loading animation to CTA button
        const ctaButton = document.querySelector('.cta-button');
        if (ctaButton) {
            ctaButton.addEventListener('click', function(e) {
                if (this.href && this.href.includes('/new')) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Загрузка...</span>';
                }
            });
        }
    </script>
</body>
</html> 