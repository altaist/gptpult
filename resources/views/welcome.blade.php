<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'GPT Пульт') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Bowler';
            src: url('{{ asset('fonts/Bowler.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --dark-bg: #0f1419;
            --card-bg: #ffffff;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --heading-font: "Inter", sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-optical-sizing: auto;
            font-weight: 400;
            font-style: normal;
            line-height: 1.6;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        .inter-heading {
            font-family: "Inter", sans-serif;
            font-optical-sizing: auto;
            font-style: normal;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: #3b82f6;
            font-family: 'Bowler', var(--heading-font);
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: #ffffff;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 0;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(96, 165, 250, 0.03) 0%, transparent 50%);
            z-index: 1;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.02'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: float 20s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .hero-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-content {
            text-align: center;
            margin-bottom: 4rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 1px solid #a7f3d0;
            border-radius: 50px;
            padding: 12px 24px;
            margin-bottom: 2rem;
            color: #047857;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.1);
        }

        .hero-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.2);
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        }

        .hero-badge i {
            margin-right: 8px;
            color: #059669;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: #3b82f6;
            letter-spacing: -0.02em;
            font-family: var(--heading-font);
            position: relative;
        }

        .hero-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #3b82f6;
            line-height: 1.3;
            font-family: var(--heading-font);
        }

        .hero-description {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            color: #64748b;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            border: 1px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.08);
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 4rem;
        }

        .btn-hero-primary {
            background: #3b82f6;
            border: none;
            padding: 20px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-hero-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-hero-primary:hover {
            background: #2563eb;
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
            color: white;
        }

        .btn-hero-primary:hover::before {
            left: 100%;
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(59, 130, 246, 0.2);
            padding: 18px 38px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: #475569;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .btn-hero-secondary:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
        }

        .btn-hero-inactive {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(59, 130, 246, 0.1);
            padding: 18px 38px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: #94a3b8;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            opacity: 0.7;
        }

        .btn-hero-inactive:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.1);
            opacity: 1;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            position: relative;
        }

        .hero-stat {
            text-align: center;
            padding: 2rem 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(59, 130, 246, 0.1);
            min-width: 180px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .hero-stat:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(59, 130, 246, 0.15);
            background: rgba(255, 255, 255, 1);
        }

        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #3b82f6;
            display: block;
            line-height: 1;
            margin-bottom: 0.5rem;
            font-family: var(--heading-font);
        }

        .hero-stat-label {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
            line-height: 1.3;
        }

        /* Stats Section */
        .stats-section {
            padding: 120px 0;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #60a5fa 100%);
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            animation: statsFloat 8s ease-in-out infinite;
        }

        @keyframes statsFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .stats-container {
            position: relative;
            z-index: 2;
        }

        .stats-title {
            text-align: center;
            margin-bottom: 5rem;
        }

        .stats-title h2 {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-family: var(--heading-font);
        }

        .stats-title p {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.8);
            max-width: 600px;
            margin: 0 auto;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 8px 16px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .stat-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 32px 64px rgba(0, 0, 0, 0.15),
                0 16px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 1);
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            background: #3b82f6;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
            transition: all 0.3s ease;
        }

        .stat-item:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
            background: #2563eb;
        }

        .stat-number {
            font-size: 4.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 0.5rem;
            line-height: 1;
            position: relative;
            font-family: var(--heading-font);
        }

        .stat-label {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
            letter-spacing: -0.01em;
            font-family: var(--heading-font);
        }

        .stat-description {
            font-size: 1rem;
            color: #64748b;
            line-height: 1.5;
            max-width: 250px;
            margin: 0 auto;
        }

        /* Unique colors for each stat */
        .stat-item:nth-child(1) .stat-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .stat-item:nth-child(2) .stat-icon {
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        }

        .stat-item:nth-child(3) .stat-icon {
            background: linear-gradient(135deg, #93c5fd 0%, #60a5fa 100%);
        }

        .stat-item:nth-child(4) .stat-icon {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        /* Services Section */
        .services-section {
            padding: 120px 0;
            background: #f8fafc;
        }

        .service-card {
            background: white;
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .service-card-icon {
            width: 80px;
            height: 80px;
            background: #3b82f6;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
            transition: all 0.3s ease;
        }

        .service-card:hover .service-card-icon {
            transform: scale(1.1) rotate(5deg);
            background: #2563eb;
        }

        .service-card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .service-card-description {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .service-card-cta {
            display: inline-flex;
            align-items: center;
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .service-card-cta:hover {
            color: #2563eb;
        }

        .service-card-cta i {
            margin-left: 8px;
            transition: transform 0.2s ease;
        }

        .service-card-cta:hover i {
            transform: translateX(4px);
        }

        /* Why Us Section */
        .why-us-section {
            padding: 80px 0;
            background: white;
        }

        .why-us-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .why-us-title h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .why-us-subtitle {
            font-size: 1rem;
            color: #64748b;
            max-width: 700px;
            margin: 0 auto;
        }

        .why-card {
            background: #3b82f6;
            border-radius: 20px;
            padding: 2rem;
            color: white;
            transition: all 0.3s ease;
            min-height: 160px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .why-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px rgba(59, 130, 246, 0.25);
            background: #2563eb;
        }

        .why-card-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
        }

        .why-card-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .why-card:hover .why-card-icon {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .why-card-text {
            flex: 1;
            min-width: 0;
        }

        .why-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            word-wrap: break-word;
            font-family: var(--heading-font);
            color: white;
        }

        .why-card-description {
            font-size: 0.85rem;
            opacity: 0.9;
            line-height: 1.5;
            word-wrap: break-word;
        }

        /* How It Works Section */
        .how-it-works-section {
            padding: 120px 0;
            background: #f8fafc;
        }

        /* Hero Carousel Spacing */
        .hero-carousel-wrapper {
            margin: 4rem 0;
            padding: 0 1rem;
        }

        .tab-navigation {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
            background: #e2e8f0;
            border-radius: 25px;
            padding: 4px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .tab-btn {
            background: none;
            border: none;
            font: inherit;
            cursor: pointer;
            outline: inherit;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
            border-radius: 20px;
            flex: 1;
        }

        .tab-btn.active {
            background: white;
            color: #3b82f6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .step-container {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .step-image {
            position: relative;
            width: 50%;
        }

        .step-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: #3b82f6;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 2;
        }

        .image-placeholder {
            background: #f1f5f9;
            border-radius: 16px;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            border: 2px dashed #e2e8f0;
        }

        .image-placeholder i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .image-placeholder p {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .step-content {
            width: 50%;
        }

        .step-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .step-content p {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .step-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
        }

        .step-nav-btn {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #3b82f6;
            font-size: 1.2rem;
        }

        .step-nav-btn:hover:not(:disabled) {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .step-nav-btn:disabled {
            background: #f8fafc;
            color: #cbd5e1;
            border-color: #e2e8f0;
            cursor: not-allowed;
        }

        .step-indicators {
            display: flex;
            gap: 8px;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .step-dot.active {
            background: #3b82f6;
        }

        @media (max-width: 576px) {
            .hero-title {
            font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .hero-stats {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .hero-stat {
                width: 100%;
                max-width: 200px;
            }
            
            .stat-item {
                padding: 2rem 1rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .section-title {
                font-size: 2rem;
            }

            .why-us-title h2 {
                font-size: 1.8rem;
            }
            
            .why-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
                min-height: 140px;
            }
            
            .why-card-content {
                gap: 1rem;
            }
            
            .why-card-icon {
                width: 50px;
                height: 50px;
                font-size: 1.4rem;
            }
            
            .why-card-title {
                font-size: 1rem;
            }
            
            .why-card-description {
                font-size: 0.85rem;
            }
            
            .step-container {
                padding: 1rem;
            }
            
            .step-image {
                width: 100%;
            }
            
            .step-content {
                width: 100%;
            }
            
            .step-navigation {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .hero-badge {
                padding: 8px 16px;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .service-card {
                padding: 2rem 1.5rem;
            }
        }

        /* Scroll Progress */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: #3b82f6;
            z-index: 1000;
            transition: width 0.3s ease;
        }

        /* Pricing Section */
        .pricing-section {
            padding: 120px 0;
            background: #f8fafc;
        }

        .price-card {
            background: white;
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            position: relative;
        }

        .price-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .price-card.featured {
            border: 2px solid #3b82f6;
            position: relative;
        }

        .price-card.featured::before {
            content: 'Рекомендуем';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #3b82f6;
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .price-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .price-amount {
            font-size: 3rem;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 2rem;
            font-family: var(--heading-font);
        }

        .price-features {
            text-align: left;
        }

        .price-feature {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .price-feature:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        /* CTA Section */
        .cta-section {
            padding: 120px 0;
            background: #3b82f6;
            color: white;
            text-align: center;
        }

        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            font-family: var(--heading-font);
            color: white;
        }

        .cta-subtitle {
            font-size: 1.3rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: var(--dark-bg);
            color: white;
            padding: 60px 0 30px;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #3b82f6;
            font-family: 'Bowler', var(--heading-font);
        }

        .footer-text {
            opacity: 0.7;
            margin-bottom: 2rem;
        }

        .footer-doc-link {
            color: #3b82f6;
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

        /* Section Styles */
        .section-title {
            font-size: 3rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto 4rem;
        }
    </style>
</head>
<body>
    <!-- Loading Animation -->
    <div class="loading-animation" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Scroll Progress -->
    <div class="scroll-progress" id="scrollProgress"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tv me-2"></i>GPT Пульт
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
        <div class="hero-container">
            <div class="container-fluid">
                <!-- Hero Content -->
                <div class="hero-content">
                    <h1 class="hero-title" data-aos="fade-up" data-aos-delay="100">
                        Твой ИИ для учебы
                    </h1>
                    
                    <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="200">
                    Подготовься и защити работу за час
                    </p>
                    
                    <!-- Hero Carousel -->
                    <div class="hero-carousel-wrapper" data-aos="fade-up" data-aos-delay="300">
                        <div class="row">
                            <div class="col-12">
                                <!-- Tab Navigation -->
                                <div class="tab-navigation">
                                    <button class="tab-btn active" data-tab="textwork">Текстовая работа</button>
                                    <button class="tab-btn" data-tab="tasksolve">Решение задачи</button>
                                </div>
                                
                                <!-- Tab Content -->
                                <div class="tab-content">
                                    <!-- Text Work Tab -->
                                    <div class="tab-pane active" id="textwork">
                                        <div class="step-container">
                                            <div class="step-image">
                                                <div class="step-badge">Шаг 1 из 5</div>
                                                <div class="image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                    <p>Изображение процесса</p>
                                                </div>
                                            </div>
                                            
                                            <div class="step-content">
                                                <h3>Укажи тему и детали работы</h3>
                                                <p>Расскажи о своей работе подробно и загрузи методичку</p>
                                                
                                                <div class="step-navigation">
                                                    <button class="step-nav-btn prev" disabled>
                                                        <i class="fas fa-arrow-left"></i>
                                                    </button>
                                                    <div class="step-indicators">
                                                        <span class="step-dot active"></span>
                                                        <span class="step-dot"></span>
                                                        <span class="step-dot"></span>
                                                        <span class="step-dot"></span>
                                                        <span class="step-dot"></span>
                                                    </div>
                                                    <button class="step-nav-btn next">
                                                        <i class="fas fa-arrow-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Task Solve Tab -->
                                    <div class="tab-pane" id="tasksolve">
                                        <div class="step-container">
                                            <div class="step-image">
                                                <div class="step-badge">Шаг 1 из 3</div>
                                                <div class="image-placeholder">
                                                    <i class="fas fa-calculator"></i>
                                                    <p>Решение задачи</p>
                                                </div>
                                            </div>
                                            
                                            <div class="step-content">
                                                <h3>Опиши условие задачи</h3>
                                                <p>Выбери предмет и подробно опиши условие задачи</p>
                                                
                                                <div class="step-navigation">
                                                    <button class="step-nav-btn prev" disabled>
                                                        <i class="fas fa-arrow-left"></i>
                                                    </button>
                                                    <div class="step-indicators">
                                                        <span class="step-dot active"></span>
                                                        <span class="step-dot"></span>
                                                        <span class="step-dot"></span>
                                                    </div>
                                                    <button class="step-nav-btn next">
                                                        <i class="fas fa-arrow-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hero-buttons" data-aos="fade-up" data-aos-delay="400">
                        <a href="/new" class="btn-hero-primary" id="btnTextWork">
                            <i class="fas fa-pencil-alt me-2"></i>
                            Написать работу
                        </a>
                        <a href="/tasks" class="btn-hero-secondary" id="btnTaskSolve">
                            <i class="fas fa-calculator me-2"></i>
                            Решить задачу
                        </a>
                    </div>
                </div>

                <!-- Hero Stats -->
                <div class="hero-stats" data-aos="fade-up" data-aos-delay="500">
                    <div class="hero-stat">
                        <span class="hero-stat-number">10 000+</span>
                        <div class="hero-stat-label">выполненных работ</div>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">5 000+</span>
                        <div class="hero-stat-label">довольных студентов</div>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number">10 мин</span>
                        <div class="hero-stat-label">на работу</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-container">
    <div class="container">
                <div class="stats-title" data-aos="fade-up">
                    <h2>Мы в цифрах</h2>
            </div>
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="stat-item" data-aos="flip-up" data-aos-delay="100">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                </div>
                            <span class="stat-number" data-count="10000">0</span>
                            <div class="stat-label">Выполненных работ</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="stat-item" data-aos="flip-up" data-aos-delay="200">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <span class="stat-number" data-count="5000">0+</span>
                            <div class="stat-label">Студентов</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="stat-item" data-aos="flip-up" data-aos-delay="300">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span class="stat-number" data-count="10">0</span>
                            <div class="stat-label">Минут на работу</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="stat-item" data-aos="flip-up" data-aos-delay="400">
                            <div class="stat-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <span class="stat-number" data-count="99">0%</span>
                            <div class="stat-label">Успешных защит</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="features">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title" data-aos="fade-up">Наши сервисы</h2>
                    <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">
                        Полный комплекс ИИ-инструментов для успешной учебы
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="service-card-icon">
                            <i class="fas fa-file-alt"></i>
                </div>
                        <h3 class="service-card-title">Решить учебную задачу</h3>
                        <p class="service-card-description">
                            Разберись в домашках, тестах, лабах, экзах по праву, экономике, вышке и еще 160+ предметам
                        </p>
                        <a href="/tasks" class="service-card-cta">
                            Решить задачу
                            <i class="fas fa-arrow-right"></i>
                        </a>
        </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="service-card-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3 class="service-card-title">Написать текстовую работу</h3>
                        <p class="service-card-description">
                            Напиши уникальную работу за 5 минут на реальных источниках с AI-репетитором
                        </p>
                        <a href="/new" class="service-card-cta">
                            Написать работу
                            <i class="fas fa-arrow-right"></i>
                        </a>
    </div>
</div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="service-card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="service-card-title">Экспертная проверка</h3>
                        <p class="service-card-description">
                            Все работы проверяются опытными преподавателями и экспертами в соответствующих областях знаний
                        </p>
                        <a href="/check" class="service-card-cta">
                            Проверить работу
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="500">
                        <div class="service-card-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="service-card-title">Удобный интерфейс</h3>
                        <p class="service-card-description">
                            Простой и интуитивно понятный интерфейс. Работайте с любого устройства - компьютера, планшета или смартфона
                        </p>
                        <a href="/mobile" class="service-card-cta">
                            Попробовать
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="600">
                        <div class="service-card-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="service-card-title">Поддержка 24/7</h3>
                        <p class="service-card-description">
                            Наша команда всегда готова помочь вам с любыми вопросами. Обращайтесь в любое время суток через чат или телефон
                        </p>
                        <a href="/support" class="service-card-cta">
                            Получить помощь
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="700">
                        <div class="service-card-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3 class="service-card-title">Полная конфиденциальность</h3>
                        <p class="service-card-description">
                            Мы гарантируем полную конфиденциальность ваших данных и работ. Никто не узнает о том, что вы пользовались нашим сервисом
                        </p>
                        <a href="/privacy" class="service-card-cta">
                            Узнать больше
                            <i class="fas fa-arrow-right"></i>
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
                <div class="col-12">
                    <h2 class="section-title" data-aos="fade-up">Сравните цены</h2>
                    <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">
                        Выгодное предложение по сравнению с фрилансерами
                    </p>
                </div>
            </div>
    <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="price-card" data-aos="fade-up" data-aos-delay="200">
                        <h3 class="price-title">У фрилансеров</h3>
                        <div class="price-amount">₽3000</div>
                        <div class="price-features">
                            <div class="price-feature">
                                <i class="fas fa-times text-danger me-2"></i>Долгое ожидание
                </div>
                            <div class="price-feature">
                                <i class="fas fa-times text-danger me-2"></i>Риск некачественной работы
            </div>
                            <div class="price-feature">
                                <i class="fas fa-times text-danger me-2"></i>Нет гарантий
                </div>
                            <div class="price-feature">
                                <i class="fas fa-times text-danger me-2"></i>Возможные задержки
            </div>
                            <div class="price-feature">
                                <i class="fas fa-times text-danger me-2"></i>Непредсказуемый результат
                </div>
            </div>
        </div>
    </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="price-card featured" data-aos="fade-up" data-aos-delay="300">
                        <h3 class="price-title">GPT Пульт</h3>
                        <div class="price-amount">₽1500</div>
                        <div class="price-features">
                            <div class="price-feature">
                                <i class="fas fa-check text-success me-2"></i>Мгновенный результат
                            </div>
                            <div class="price-feature">
                                <i class="fas fa-check text-success me-2"></i>Гарантия качества
                            </div>
                            <div class="price-feature">
                                <i class="fas fa-check text-success me-2"></i>Поддержка 24/7
                            </div>
                            <div class="price-feature">
                                <i class="fas fa-check text-success me-2"></i>Проверка на уникальность
                            </div>
                            <div class="price-feature">
                                <i class="fas fa-check text-success me-2"></i>Экономия времени
                            </div>
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
                <div class="col-lg-8 mx-auto">
                    <h2 class="cta-title" data-aos="fade-up">Готов начать?</h2>
                    <p class="cta-subtitle" data-aos="fade-up" data-aos-delay="100">
                        Присоединяйся к тысячам студентов, которые уже используют GPT Пульт для успешной учебы
                    </p>
                    <a href="/new" class="btn-hero-primary" data-aos="fade-up" data-aos-delay="200">
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
                        <i class="fas fa-tv me-2"></i>GPT Пульт
                    </div>
                    <p class="footer-text">
                        Революционная платформа для создания учебных работ с использованием 
                        искусственного интеллекта. Быстро, качественно, доступно.
                    </p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Сервис</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light opacity-75">О нас</a></li>
                        <li><a href="#" class="text-light opacity-75">Как это работает</a></li>
                        <li><a href="#" class="text-light opacity-75">Цены</a></li>
                        <li><a href="#" class="text-light opacity-75">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Поддержка</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light opacity-75">Помощь</a></li>
                        <li><a href="#" class="text-light opacity-75">Контакты</a></li>
                        <li><a href="#" class="text-light opacity-75">Чат</a></li>
                        <li><a href="#" class="text-light opacity-75">Email</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Документы</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ asset('docs/Политика персональных данных.docx') }}" class="footer-doc-link" target="_blank">Политика конфиденциальности</a></li>
                        <li><a href="{{ asset('docs/ПОЛОЖЕНИЕ о порядке возврата денежных средств за неоказанные платные услуги.docx') }}" class="footer-doc-link" target="_blank">Условия возврата</a></li>
                        <li><a href="{{ asset('docs/Правила оформления заказа.docx') }}" class="footer-doc-link" target="_blank">Правила оформления заказа</a></li>
                        <li><a href="{{ asset('docs/Публичная оферта.docx') }}" class="footer-doc-link" target="_blank">Публичная оферта</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">Контакты</h5>
                    <p class="opacity-75">
                        <i class="fas fa-envelope me-2"></i>support@gptpult.ru<br>
                        <i class="fas fa-phone me-2"></i>+7 (999) 123-45-67<br>
                        <i class="fas fa-clock me-2"></i>24/7 поддержка
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 GPT Пульт. Все права защищены.</p>
        </div>
    </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Scroll progress
        window.addEventListener('scroll', function() {
            const scrollProgress = document.getElementById('scrollProgress');
            const scrolled = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
            scrollProgress.style.width = scrolled + '%';
        });

        // Counter animation
        function animateCounters() {
            const counters = document.querySelectorAll('[data-count]');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                const hasPercent = counter.textContent.includes('%');
                const hasPlus = counter.textContent.includes('+');
                
                const updateCounter = () => {
                    current += step;
                    if (current >= target) {
                        if (hasPercent) {
                            counter.textContent = target + '%';
                        } else if (hasPlus) {
                            counter.textContent = target + '+';
                        } else {
                            counter.textContent = target;
                        }
                    } else {
                        if (hasPercent) {
                            counter.textContent = Math.floor(current) + '%';
                        } else if (hasPlus) {
                            counter.textContent = Math.floor(current) + '+';
                        } else {
                            counter.textContent = Math.floor(current);
                        }
                        requestAnimationFrame(updateCounter);
                    }
                };
                
                updateCounter();
            });
        }

        // Trigger counter animation when stats section is visible
        const statsSection = document.querySelector('.stats-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        });

        observer.observe(statsSection);

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

        // Add hover effects to cards
        document.querySelectorAll('.service-card, .stat-item').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons and panes
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Show corresponding tab pane
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // Update hero buttons
                const btnTextWork = document.getElementById('btnTextWork');
                const btnTaskSolve = document.getElementById('btnTaskSolve');
                
                if (tabId === 'textwork') {
                    btnTextWork.className = 'btn-hero-primary';
                    btnTaskSolve.className = 'btn-hero-secondary';
                } else if (tabId === 'tasksolve') {
                    btnTextWork.className = 'btn-hero-secondary';
                    btnTaskSolve.className = 'btn-hero-primary';
                }
            });
        });

        // Step navigation functionality
        const textworkSteps = [
            { title: "Укажи тему и детали работы", desc: "Расскажи о своей работе подробно и загрузи методичку", badge: "Шаг 1 из 5", icon: "fas fa-file-alt" },
            { title: "Выбери тип работы", desc: "Курсовая, диплом, реферат или эссе - выбери подходящий формат для твоей работы", badge: "Шаг 2 из 5", icon: "fas fa-list" },
            { title: "Настрой параметры", desc: "Укажи количество страниц, стиль оформления и особые требования к работе", badge: "Шаг 3 из 5", icon: "fas fa-cog" },
            { title: "Получи результат", desc: "Через несколько минут получи готовую уникальную работу с проверкой на плагиат", badge: "Шаг 4 из 5", icon: "fas fa-check-circle" },
            { title: "Доработай при необходимости", desc: "Воспользуйся бесплатными правками или задай дополнительные вопросы ИИ", badge: "Шаг 5 из 5", icon: "fas fa-edit" }
        ];

        const tasksolveSteps = [
            { title: "Опиши условие задачи", desc: "Выбери предмет и подробно опиши условие задачи", badge: "Шаг 1 из 3", icon: "fas fa-calculator" },
            { title: "Получи решение", desc: "ИИ проанализирует задачу и предоставит детальное решение с объяснением каждого шага", badge: "Шаг 2 из 3", icon: "fas fa-brain" },
            { title: "Изучи и примени", desc: "Разберись в решении, задай уточняющие вопросы и применяй полученные знания", badge: "Шаг 3 из 3", icon: "fas fa-graduation-cap" }
        ];

        function updateStepContent(tabType, stepIndex) {
            const steps = tabType === 'textwork' ? textworkSteps : tasksolveSteps;
            const step = steps[stepIndex];
            const pane = document.getElementById(tabType);
            
            // Update content
            pane.querySelector('.step-badge').textContent = step.badge;
            pane.querySelector('.image-placeholder i').className = step.icon;
            pane.querySelector('.step-content h3').textContent = step.title;
            pane.querySelector('.step-content p').textContent = step.desc;
            
            // Update navigation buttons
            const prevBtn = pane.querySelector('.step-nav-btn.prev');
            const nextBtn = pane.querySelector('.step-nav-btn.next');
            
            prevBtn.disabled = stepIndex === 0;
            nextBtn.disabled = stepIndex === steps.length - 1;
            
            // Update indicators
            pane.querySelectorAll('.step-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === stepIndex);
            });
        }

        // Initialize step navigation
        let currentSteps = { textwork: 0, tasksolve: 0 };

        // Handle step navigation clicks
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.step-nav-btn, .step-dot');
            if (!target) return;
            
            const pane = target.closest('.tab-pane');
            if (!pane) return;
            
            const tabType = pane.id;
            const steps = tabType === 'textwork' ? textworkSteps : tasksolveSteps;
            
            if (target.classList.contains('step-nav-btn')) {
                if (target.classList.contains('prev') && currentSteps[tabType] > 0) {
                    currentSteps[tabType]--;
                } else if (target.classList.contains('next') && currentSteps[tabType] < steps.length - 1) {
                    currentSteps[tabType]++;
                }
            } else if (target.classList.contains('step-dot')) {
                const dots = Array.from(pane.querySelectorAll('.step-dot'));
                currentSteps[tabType] = dots.indexOf(target);
            }
            
            updateStepContent(tabType, currentSteps[tabType]);
        });
    </script>
</body>
</html> 