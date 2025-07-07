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

        /* Comparison Section */
        .comparison-section {
            padding: 120px 0 80px 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .comparison-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(147, 197, 253, 0.06) 0%, transparent 40%);
            z-index: 1;
        }

        .comparison-container {
            position: relative;
            z-index: 2;
        }

        .comparison-section .section-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 4rem;
            letter-spacing: -0.025em;
            line-height: 1.1;
        }

        .comparison-wrapper {
            display: flex;
            align-items: stretch;
            gap: 0;
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            background: white;
            position: relative;
            border: 1px solid var(--border-light);
        }

        .comparison-side {
            flex: 1;
            padding: 4rem 3rem;
            position: relative;
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .comparison-left {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.1) 0%, rgba(254, 202, 202, 0.05) 100%);
            border-right: 2px solid rgba(248, 113, 113, 0.2);
        }

        .comparison-right {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(191, 219, 254, 0.05) 100%);
        }

        .comparison-vs {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 900;
            box-shadow: 0 15px 50px rgba(99, 102, 241, 0.3);
            z-index: 10;
            border: 5px solid white;
            transition: all 0.3s ease;
        }

        .comparison-vs:hover {
            transform: translate(-50%, -50%) scale(1.1);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.4);
        }

        .comparison-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .comparison-title-text {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .comparison-left .comparison-title-text {
            color: #dc2626;
        }

        .comparison-right .comparison-title-text {
            color: var(--primary-color);
        }

        .comparison-price {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0.8rem;
            letter-spacing: -0.02em;
        }

        .comparison-left .comparison-price {
            color: #dc2626;
        }

        .comparison-right .comparison-price {
            color: var(--primary-color);
        }

        .comparison-price-label {
            font-size: 1.1rem;
            opacity: 0.8;
            font-weight: 600;
        }

        .comparison-features {
            flex: 1;
            margin-bottom: 2.5rem;
        }

        .comparison-feature {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-size: 0.95rem;
            line-height: 1.4;
            font-weight: 500;
            padding: 10px 16px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
            max-width: 85%;
        }

        .comparison-feature-negative {
            background: transparent;
            border: 1.5px solid #ef4444;
            color: #dc2626;
        }

        .comparison-feature-negative:hover {
            background: rgba(239, 68, 68, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.15);
        }

        .comparison-feature-positive {
            background: transparent;
            border: 1.5px solid #22c55e;
            color: #16a34a;
        }

        .comparison-feature-positive:hover {
            background: rgba(34, 197, 94, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.15);
        }

        .comparison-feature i {
            margin-right: 15px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .comparison-left .comparison-feature i {
            background: #dc2626;
            color: white;
        }

        .comparison-right .comparison-feature i {
            background: #22c55e;
            color: white;
        }

        .comparison-cta {
            text-align: center;
            margin-top: auto;
        }

        .comparison-btn {
            display: inline-block;
            padding: 18px 36px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            min-width: 180px;
        }

        .comparison-left .comparison-btn {
            background: #9ca3af;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .comparison-right .comparison-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.3);
        }

        .comparison-right .comparison-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
            text-decoration: none;
            color: white;
        }

        /* Advantages Section */
        .advantages-section {
            padding: 120px 0 80px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f1f5f9 100%);
            position: relative;
            overflow: hidden;
        }

        .advantages-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(147, 197, 253, 0.04) 0%, transparent 50%);
            z-index: 1;
        }

        .advantages-section .container {
            position: relative;
            z-index: 2;
        }

        .advantages-section .section-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 4rem;
            letter-spacing: -0.025em;
            line-height: 1.1;
        }

        .advantage-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 28px;
            padding: 3rem 2.5rem;
            border: 1px solid rgba(99, 102, 241, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }

        .advantage-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 28px 28px 0 0;
        }

        .advantage-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 32px 64px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .advantage-card-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.2rem;
            margin-bottom: 2.5rem;
            transition: all 0.4s ease;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.25);
        }

        .advantage-card:hover .advantage-card-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 16px 48px rgba(99, 102, 241, 0.35);
        }

        .advantage-card-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            letter-spacing: -0.01em;
        }

        .advantage-card-description {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 1.05rem;
        }

        /* Pricing Section */
        .pricing-section {
            padding: 120px 0 80px 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .pricing-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 40%, rgba(99, 102, 241, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, rgba(245, 158, 11, 0.04) 0%, transparent 50%);
            z-index: 1;
        }

        .pricing-section .container {
            position: relative;
            z-index: 2;
        }

        .pricing-section .section-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            letter-spacing: -0.025em;
            line-height: 1.1;
        }

        .pricing-section .section-subtitle {
            font-size: 1.3rem;
            color: var(--text-secondary);
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .pricing-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 32px;
            padding: 3.5rem 3rem;
            border: 1px solid rgba(99, 102, 241, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }

        .pricing-card:hover {
            transform: translateY(-16px) scale(1.03);
            box-shadow: 0 40px 80px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .pricing-card.featured {
            border: 2px solid var(--primary-color);
            background: linear-gradient(145deg, #ffffff 0%, #fafbff 100%);
            transform: scale(1.05);
        }

        .pricing-card.featured:hover {
            transform: translateY(-16px) scale(1.08);
        }

        .pricing-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 12px 28px;
            border-radius: 0 32px 0 24px;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .pricing-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 2rem;
            transition: all 0.4s ease;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.25);
        }

        .pricing-card:hover .pricing-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 16px 48px rgba(99, 102, 241, 0.35);
        }

        .pricing-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            letter-spacing: -0.01em;
        }

        .pricing-price {
            margin-bottom: 3rem;
        }

        .pricing-amount {
            font-size: 3rem;
            font-weight: 900;
            color: var(--primary-color);
            display: block;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .pricing-period {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .pricing-features {
            margin-bottom: 3rem;
        }

        .pricing-feature {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .pricing-feature i {
            color: #22c55e;
            margin-right: 15px;
            font-size: 1.1rem;
            width: 20px;
        }

        .pricing-cta {
            text-align: center;
        }

        .pricing-btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 18px 40px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.3);
        }

        .pricing-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
            color: white;
            text-decoration: none;
        }

        .pricing-btn.featured {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.4);
        }

        .pricing-btn.featured:hover {
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.5);
            transform: translateY(-4px);
        }

        /* CTA Section */
        .cta-section {
            padding: 120px 0 80px 0;
            background: #ffffff;
            color: var(--text-primary);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(147, 197, 253, 0.06) 0%, transparent 50%);
            z-index: 1;
        }

        .cta-section .container {
            position: relative;
            z-index: 2;
        }

        .cta-section .section-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            letter-spacing: -0.025em;
            line-height: 1.1;
        }

        .cta-section .section-subtitle {
            font-size: 1.3rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .btn-hero-white {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border: none;
            padding: 22px 45px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 18px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-hero-white::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-hero-white:hover {
            background: linear-gradient(135deg, var(--primary-hover), #4338ca);
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
            text-decoration: none;
        }

        .btn-hero-white:hover::before {
            left: 100%;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .comparison-section,
            .advantages-section,
            .pricing-section,
            .cta-section {
                padding: 80px 0 60px 0;
            }

            .comparison-section .section-title,
            .advantages-section .section-title,
            .pricing-section .section-title,
            .cta-section .section-title {
                font-size: 2.5rem;
                margin-bottom: 3rem;
            }

            .comparison-wrapper {
                flex-direction: column;
                gap: 3rem;
                border-radius: 24px;
            }

            .comparison-left {
                border-right: none;
                border-bottom: 2px solid rgba(248, 113, 113, 0.2);
            }

            .comparison-vs {
                position: static;
                margin: 0 auto;
                transform: none;
                width: 80px;
                height: 80px;
                font-size: 1.5rem;
            }

            .comparison-side {
                min-height: auto;
                padding: 3rem 2rem;
            }

            .pricing-card {
                margin-bottom: 3rem;
            }

            .pricing-card.featured {
                transform: none;
            }

            .pricing-card.featured:hover {
                transform: translateY(-12px) scale(1.02);
            }

            .advantage-card {
                margin-bottom: 3rem;
            }
        }

        /* Telegram Bot Section */
        .telegram-bot-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 50%, #4338ca 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .telegram-bot-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            z-index: 1;
        }

        .telegram-bot-section .container {
            position: relative;
            z-index: 2;
        }

        .bot-info {
            padding: 2rem 1rem;
        }

        .bot-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            font-family: var(--heading-font);
        }

        .bot-description {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .bot-features {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .bot-cta {
            margin-top: 2rem;
        }

        .bot-btn {
            display: inline-flex;
            align-items: center;
            background: white;
            color: var(--primary-color);
            padding: 15px 30px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
        }

        .bot-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(255, 255, 255, 0.3);
            color: var(--primary-hover);
            text-decoration: none;
            background: #f8fafc;
        }

        /* About Service Section */
        .about-service-section {
            padding: 100px 0 80px 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .about-service-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 25% 25%, rgba(99, 102, 241, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(147, 197, 253, 0.04) 0%, transparent 50%);
            z-index: 1;
        }

        .about-service-section .container {
            position: relative;
            z-index: 2;
        }

        .about-service-section .section-title {
            font-size: 3.2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .about-service-section .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto 4rem;
            line-height: 1.6;
        }

        .service-description {
            padding: 2rem 1rem;
        }

        .service-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }

        .service-text {
            font-size: 1.1rem;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }

        .work-types-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .work-type-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid rgba(99, 102, 241, 0.12);
            border-radius: 16px;
            padding: 1.5rem 1rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .work-type-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(147, 197, 253, 0.04) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .work-type-item:hover {
            transform: translateY(-6px);
            border-color: var(--primary-color);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.2);
        }

        .work-type-item:hover::before {
            opacity: 1;
        }

        .work-type-item:active {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.25);
        }

        .work-type-content {
            position: relative;
            z-index: 2;
        }

        .work-type-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: block;
            line-height: 1.3;
        }

        .service-highlights {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .service-highlight {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.08);
            transition: all 0.3s ease;
        }

        .service-highlight:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.15);
        }

        .highlight-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .highlight-content {
            flex: 1;
        }

        .highlight-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .highlight-description {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.5;
        }

        .service-visual {
            padding: 1rem;
            display: flex;
            justify-content: center;
        }

        .visual-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            border: 2px solid #e2e8f0;
            width: 100%;
            max-width: 100%;
        }

        .visual-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .visual-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.3;
        }

        .visual-steps {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .visual-step {
            display: flex;
            align-items: center;
            padding: 1.5rem 1.2rem;
            background: #ffffff;
            border-radius: 12px;
            border: 2px solid #3b82f6;
        }

        .step-text {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 600;
            line-height: 1.4;
            width: 100%;
            text-align: center;
        }

        .service-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .service-stat {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 2.5rem 2rem;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.06);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .service-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(147, 197, 253, 0.02) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .service-stat:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.12);
        }

        .service-stat:hover::before {
            opacity: 1;
        }

        .stat-icon-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.6rem;
            flex-shrink: 0;
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.25);
            transition: all 0.4s ease;
            position: relative;
            z-index: 2;
        }

        .service-stat:hover .stat-icon-circle {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.35);
        }

        .stat-info {
            flex: 1;
            position: relative;
            z-index: 2;
        }

        .service-stat .stat-number {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 0.4rem;
            letter-spacing: -0.02em;
        }

        .service-stat .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .about-service-section {
                padding: 80px 0 60px 0;
            }

            .about-service-section .section-title {
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }

            .about-service-section .section-subtitle {
                font-size: 1.1rem;
                margin-bottom: 3rem;
            }

            .service-title {
                font-size: 1.7rem;
                margin-bottom: 1rem;
            }

            .service-text {
                font-size: 1rem;
                margin-bottom: 2rem;
            }

            .work-types-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
                margin-top: 1.5rem;
            }

            .work-type-item {
                padding: 1.2rem 0.8rem;
            }

            .work-type-title {
                font-size: 0.95rem;
            }

            .visual-card {
                padding: 2.5rem 2rem;
            }

            .visual-title {
                font-size: 1.3rem;
            }

            .visual-steps {
                gap: 1rem;
            }

            .visual-step {
                padding: 1.3rem 1rem;
                border: 2px solid #3b82f6;
            }

            .step-text {
                font-size: 1rem;
            }

            .service-stats-row {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-top: 2rem;
            }

            .service-stat {
                padding: 2rem 1.5rem;
            }

            .stat-icon-circle {
                width: 60px;
                height: 60px;
                font-size: 1.4rem;
            }

            .service-stat .stat-number {
                font-size: 2rem;
            }

            .service-stat .stat-label {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .about-service-section .section-title {
                font-size: 2rem;
            }

            .work-types-grid {
                gap: 0.6rem;
                margin-top: 1rem;
            }

            .work-type-item {
                padding: 1rem 0.6rem;
            }

            .work-type-title {
                font-size: 0.9rem;
            }

            .visual-card {
                padding: 2rem 1.5rem;
            }

            .visual-title {
                font-size: 1.2rem;
            }

            .visual-steps {
                gap: 0.8rem;
            }

            .visual-step {
                padding: 1.2rem 0.8rem;
                border: 2px solid #3b82f6;
            }

            .step-text {
                font-size: 0.95rem;
            }

            .service-stat {
                padding: 1.8rem 1.2rem;
            }

            .stat-icon-circle {
                width: 55px;
                height: 55px;
                font-size: 1.3rem;
            }

            .service-stat .stat-number {
                font-size: 1.8rem;
            }
        }

        /* Advantages Grid */
        .advantages-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding: 1rem;
        }

        .advantage-item {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .advantage-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            z-index: 1;
        }

        .advantage-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.35);
        }

        .advantage-content {
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-align: center;
            line-height: 1.4;
            position: relative;
            z-index: 2;
        }

        /* Mobile responsive for advantages */
        @media (max-width: 768px) {
            .advantages-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
                padding: 0.5rem;
            }

            .advantage-item {
                padding: 1.2rem;
            }

            .advantage-content {
                font-size: 0.9rem;
            }
        }

        /* Telegram Logo */
        .telegram-logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 300px;
            padding: 2rem;
        }

        .telegram-logo {
            font-size: 12rem;
            color: white;
            text-shadow: 0 4px 20px rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .telegram-logo:hover {
            transform: scale(1.05);
            text-shadow: 0 6px 30px rgba(255, 255, 255, 0.5);
        }

        /* Mobile responsive for telegram logo */
        @media (max-width: 768px) {
            .telegram-logo-container {
                display: none;
            }

            .telegram-logo {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .telegram-logo-container {
                display: none;
            }

            .telegram-logo {
                display: none;
            }
        }

        /* Advantages Grid - removed, replaced with telegram logo */
        .advantages-grid {
            display: none;
        }

        .advantage-item {
            display: none;
        }

        .advantage-content {
            display: none;
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
                Твоя текстовая работа<br>
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

    </section>

    <!-- About Service Section -->
    <section class="about-service-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Что такое GPT Пульт?</h2>
                </div>
            </div>
            
            <div class="row align-items-center mb-5 justify-content-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="service-description">
                        <h3 class="service-title">Современное решение для студентов</h3>
                        <p class="service-text">
                            Наша платформа использует передовые технологии искусственного интеллекта для создания качественных учебных работ. Мы автоматизировали процесс написания рефератов, эссе, курсовых и других академических текстов.
                        </p>
                        <div class="work-types-grid">
                            <div class="work-type-item" onclick="window.location.href='/new'">
                                <div class="work-type-content">
                                    <span class="work-type-title">Отчет о практике</span>
                                </div>
                            </div>
                            <div class="work-type-item" onclick="window.location.href='/new'">
                                <div class="work-type-content">
                                    <span class="work-type-title">Курсовая работа</span>
                                </div>
                            </div>
                            <div class="work-type-item" onclick="window.location.href='/new'">
                                <div class="work-type-content">
                                    <span class="work-type-title">Доклад</span>
                                </div>
                            </div>
                            <div class="work-type-item" onclick="window.location.href='/new'">
                                <div class="work-type-content">
                                    <span class="work-type-title">Эссе</span>
                                </div>
                            </div>
                            <div class="work-type-item" onclick="window.location.href='/new'">
                                <div class="work-type-content">
                                    <span class="work-type-title">Реферат</span>
                                </div>
                            </div>
                            <div class="work-type-item" onclick="window.location.href='/new'">
                                <div class="work-type-content">
                                    <span class="work-type-title">Научная статья</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="service-visual">
                        <div class="visual-card">
                            <div class="visual-header">
                                <h4 class="visual-title">Настроили ИИ специально для тебя</h4>
                            </div>
                            <div class="visual-steps">
                                <div class="visual-step">
                                    <span class="step-text">Используем модели от разных компаний</span>
                                </div>
                                <div class="visual-step">
                                    <span class="step-text">Все генерируется по ГОСТу</span>
                                </div>
                                <div class="visual-step">
                                    <span class="step-text">Система проверки результата</span>
                                </div>
                                <div class="visual-step">
                                    <span class="step-text">Получаешь результат за 10 минут</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="service-stats-row">
                        <div class="service-stat">
                            <div class="stat-icon-circle">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number">3,000+</div>
                                <div class="stat-label">Довольных студентов</div>
                            </div>
                        </div>
                        <div class="service-stat">
                            <div class="stat-icon-circle">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number">10,000+</div>
                                <div class="stat-label">Созданных работ</div>
                            </div>
                        </div>
                        <div class="service-stat">
                            <div class="stat-icon-circle">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number">90%</div>
                                <div class="stat-label">Гарантия уникальности</div>
                            </div>
                        </div>
                        <div class="service-stat">
                            <div class="stat-icon-circle">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number">10 мин</div>
                                <div class="stat-label">Время генерации</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <!-- Comparison Section -->
    <section class="comparison-section">
        <div class="container comparison-container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Репетиторы VS GPT Пульт</h2>
                </div>
            </div>
            
            <div class="comparison-wrapper">
                <!-- Freelancers Side -->
                <div class="comparison-side comparison-left">
                    <div class="comparison-header">
                        <h3 class="comparison-title-text">Репетиторы</h3>
                        <div class="comparison-price">от 3000₽</div>
                        <div class="comparison-price-label">за работу</div>
                    </div>
                    
                    <div class="comparison-features">
                        <div class="comparison-feature comparison-feature-negative">
                            Долгое ожидание (2-7 дней)
                        </div>
                        <div class="comparison-feature comparison-feature-negative">
                            Риск некачественной работы
                        </div>
                        <div class="comparison-feature comparison-feature-negative">
                            Нет гарантий качества
                        </div>
                        <div class="comparison-feature comparison-feature-negative">
                            Возможные задержки
                        </div>
                        <div class="comparison-feature comparison-feature-negative">
                            Непредсказуемый результат
                        </div>
                        <div class="comparison-feature comparison-feature-negative">
                            Сложное общение
                        </div>
                    </div>
                    
                    <div class="comparison-cta">
                        <span class="comparison-btn">Устарело</span>
                    </div>
                </div>
                
                <!-- VS Circle -->
                <div class="comparison-vs">VS</div>
                
                <!-- GPT Пульт Side -->
                <div class="comparison-side comparison-right">
                    <div class="comparison-header">
                        <h3 class="comparison-title-text">GPT Пульт</h3>
                        <div class="comparison-price">100₽</div>
                        <div class="comparison-price-label">за работу</div>
                    </div>
                    
                    <div class="comparison-features">
                        <div class="comparison-feature comparison-feature-positive">
                            Мгновенный результат (10 мин)
                        </div>
                        <div class="comparison-feature comparison-feature-positive">
                            Гарантия качества ИИ
                        </div>
                        <div class="comparison-feature comparison-feature-positive">
                            Поддержка 24/7
                        </div>
                        <div class="comparison-feature comparison-feature-positive">
                            Проверка на уникальность
                        </div>
                        <div class="comparison-feature comparison-feature-positive">
                            Экономия времени и денег
                        </div>
                        <div class="comparison-feature comparison-feature-positive">
                            Простой интерфейс
                        </div>
                    </div>
                    
                    <div class="comparison-cta">
                        <a href="/new" class="comparison-btn">
                            <i class="fas fa-rocket me-2"></i>
                            Выбрать
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section" id="pricing">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Тарифные планы</h2>
                    <p class="section-subtitle">Выберите подходящий план для ваших задач</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3 class="pricing-title">Бесплатный</h3>
                            <div class="pricing-price">
                                <span class="pricing-amount">0₽</span>
                                <span class="pricing-period">уже у тебя</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Содержание документа</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Цели и описание</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Список литературы</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Результат за минуту</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-times" style="color: red;"></i>
                                <span>Полный документ</span>
                            </div>
                        </div>
                        <div class="pricing-cta">
                            <a href="/new" class="pricing-btn">
                                Использовать
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card featured">
                        <div class="pricing-badge">Популярный</div>
                        <div class="pricing-header">
                            <h3 class="pricing-title">Абонемент</h3>
                            <div class="pricing-price">
                                <span class="pricing-amount">300₽</span>
                                <span class="pricing-period">без ограничений по времени</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>3 генерации</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Работы до 25 страниц</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Гарантия уникальности</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Результат за 10 минут</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Профессиональное оформление</span>
                            </div>
                        </div>
                        <div class="pricing-cta">
                            <a href="/lk" class="pricing-btn featured">
                                Заказать сейчас
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Telegram Bot Section -->
    <section class="telegram-bot-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="bot-info">
                        <h2 class="bot-title">Тот же GPT Пульт, но в Telegram</h2>
                        <p class="bot-description">
                            Получай уведомления о статусе работ, создавай новые и получай результат за 10 минут!
                        </p>
                        <div class="bot-cta">
                            <a href="https://t.me/gptpult_bot" target="_blank" class="bot-btn">
                                <i class="fab fa-telegram me-2"></i>
                                Подключить
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="telegram-logo-container">
                        <div class="telegram-logo">
                            <i class="fab fa-telegram"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Готов начать?</h2>
                    <p class="section-subtitle">
                        Присоединяйся к тысячам студентов, которые уже используют GPT Пульт для успешной учебы
                    </p>
                    <a href="/new" class="btn-hero-white">
                        <i class="fas fa-play me-2"></i>Начать сейчас
                    </a>
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
            
            <!-- Реквизиты -->
            <div class="row mt-5 pt-4">
                <div class="col-12">
                    <div class="footer-requisites" style="background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                        <h6 class="mb-3" style="color: #60a5fa; font-weight: 600;">Реквизиты</h6>
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div style="font-size: 0.85rem; opacity: 0.7; line-height: 1.6;">
                                    <p class="mb-2"><strong>Название организации:</strong><br>
                                    ИНДИВИДУАЛЬНЫЙ ПРЕДПРИНИМАТЕЛЬ ВЛАСЕНКО СЕРГЕЙ ВЛАДИМИРОВИЧ</p>
                                    
                                    <p class="mb-2"><strong>Юридический адрес:</strong><br>
                                    630132, РОССИЯ, НОВОСИБИРСКАЯ ОБЛ, Г НОВОСИБИРСК, УЛ 1905 ГОДА, Д 85/2, КВ 250</p>
                                    
                                    <p class="mb-2"><strong>ИНН:</strong> 041105019528</p>
                                    <p class="mb-2"><strong>ОГРНИП:</strong> 318547600089160</p>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <div style="font-size: 0.85rem; opacity: 0.7; line-height: 1.6;">
                                    <p class="mb-2"><strong>Расчетный счет:</strong> 40802810700000572721</p>
                                    <p class="mb-2"><strong>Банк:</strong> АО «ТБанк»</p>
                                    <p class="mb-2"><strong>ИНН банка:</strong> 7710140679</p>
                                    <p class="mb-2"><strong>БИК банка:</strong> 044525974</p>
                                    <p class="mb-2"><strong>Корреспондентский счет:</strong> 30101810145250000974</p>
                                    <p class="mb-0"><strong>Юридический адрес банка:</strong><br>
                                    127287, г. Москва, ул. Хуторская 2-я, д. 38А, стр. 26</p>
                                </div>
                            </div>
                        </div>
                    </div>
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