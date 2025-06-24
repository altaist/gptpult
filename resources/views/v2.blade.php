<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPT Пульт - Онлайн конструктор учебных работ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            padding: 80px 0;
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
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(96, 165, 250, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(147, 197, 253, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(59, 130, 246, 0.06) 0%, transparent 50%);
            z-index: 1;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.06'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"),
                url("data:image/svg+xml,%3Csvg width='120' height='120' viewBox='0 0 120 120' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2360a5fa' fill-opacity='0.04'%3E%3Crect x='58' y='58' width='4' height='4' rx='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: float 20s ease-in-out infinite;
            z-index: 1;
        }

        /* Дополнительные декоративные элементы */
        .hero-section .hero-bg-element {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(147, 197, 253, 0.08));
            z-index: 1;
            animation: heroFloat 15s ease-in-out infinite;
        }

        .hero-section .hero-bg-element:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .hero-section .hero-bg-element:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 20%;
            right: 15%;
            animation-delay: -3s;
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.10), rgba(59, 130, 246, 0.06));
        }

        .hero-section .hero-bg-element:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 30%;
            left: 8%;
            animation-delay: -6s;
            background: linear-gradient(135deg, rgba(147, 197, 253, 0.09), rgba(219, 234, 254, 0.06));
        }

        .hero-section .hero-bg-element:nth-child(4) {
            width: 180px;
            height: 180px;
            bottom: 15%;
            right: 20%;
            animation-delay: -9s;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(96, 165, 250, 0.05));
        }

        .hero-section .hero-bg-element:nth-child(5) {
            width: 120px;
            height: 120px;
            top: 50%;
            left: 5%;
            animation-delay: -12s;
            background: linear-gradient(135deg, rgba(147, 197, 253, 0.11), rgba(59, 130, 246, 0.07));
        }

        .hero-section .hero-bg-element:nth-child(6) {
            width: 80px;
            height: 80px;
            top: 70%;
            right: 10%;
            animation-delay: -15s;
            background: linear-gradient(135deg, rgba(219, 234, 254, 0.12), rgba(147, 197, 253, 0.08));
        }

        /* Волновые элементы */
        .hero-section .hero-wave {
            position: absolute;
            width: 100%;
            height: 100px;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(59, 130, 246, 0.06), 
                rgba(96, 165, 250, 0.04), 
                rgba(147, 197, 253, 0.06), 
                transparent
            );
            z-index: 1;
            transform: skewY(-2deg);
            animation: waveMove 12s ease-in-out infinite;
        }

        .hero-section .hero-wave:nth-child(7) {
            top: 20%;
            animation-delay: 0s;
        }

        .hero-section .hero-wave:nth-child(8) {
            bottom: 30%;
            animation-delay: -4s;
            transform: skewY(2deg);
        }

        /* Геометрические фигуры */
        .hero-section .hero-geometry {
            position: absolute;
            z-index: 1;
            opacity: 0.8;
        }

        .hero-section .hero-geometry:nth-child(9) {
            top: 15%;
            right: 25%;
            width: 0;
            height: 0;
            border-left: 30px solid transparent;
            border-right: 30px solid transparent;
            border-bottom: 40px solid rgba(59, 130, 246, 0.12);
            animation: geometryRotate 20s linear infinite;
        }

        .hero-section .hero-geometry:nth-child(10) {
            bottom: 25%;
            left: 25%;
            width: 60px;
            height: 60px;
            background: rgba(96, 165, 250, 0.10);
            transform: rotate(45deg);
            animation: geometryFloat 18s ease-in-out infinite;
        }

        .hero-section .hero-geometry:nth-child(11) {
            top: 60%;
            right: 8%;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(59, 130, 246, 0.15);
            border-radius: 50%;
            animation: geometryPulse 14s ease-in-out infinite;
        }

        @keyframes heroFloat {
            0%, 100% {
                transform: translateY(0px) translateX(0px) scale(1);
            }
            25% {
                transform: translateY(-20px) translateX(10px) scale(1.05);
            }
            50% {
                transform: translateY(-10px) translateX(-15px) scale(0.95);
            }
            75% {
                transform: translateY(-25px) translateX(5px) scale(1.02);
            }
        }

        @keyframes waveMove {
            0%, 100% {
                transform: translateX(-100px) skewY(-2deg);
                opacity: 0.3;
            }
            50% {
                transform: translateX(100px) skewY(-2deg);
                opacity: 0.7;
            }
        }

        @keyframes geometryRotate {
            0% {
                transform: rotate(0deg) translateY(0px);
            }
            100% {
                transform: rotate(360deg) translateY(-10px);
            }
        }

        @keyframes geometryFloat {
            0%, 100% {
                transform: rotate(45deg) translateY(0px);
            }
            50% {
                transform: rotate(45deg) translateY(-15px);
            }
        }

        @keyframes geometryPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.6;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.8;
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
            margin-bottom: 6rem;
            padding: 0 2rem;
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
            margin-bottom: 2.5rem;
            color: #3b82f6;
            letter-spacing: -0.02em;
            font-family: var(--heading-font);
            position: relative;
        }

        .hero-subtitle {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 3rem;
            color: #3b82f6;
            line-height: 1.3;
            font-family: var(--heading-font);
        }

        .hero-description {
            font-size: 1.2rem;
            margin-bottom: 4rem;
            color: #64748b;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            padding: 2rem 2.5rem;
            border-radius: 20px;
            border: 1px solid rgba(59, 130, 246, 0.1);
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.08);
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 5rem;
            margin-bottom: 6rem;
            text-align: center;
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

        .btn-hero-white {
            background: white;
            border: 2px solid white;
            padding: 20px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: #3b82f6;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.3);
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
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            transition: left 0.5s;
        }

        .btn-hero-white:hover {
            background: #f8fafc;
            color: #2563eb;
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(255, 255, 255, 0.4);
        }

        .btn-hero-white:hover::before {
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
            gap: 4rem;
            flex-wrap: wrap;
            position: relative;
            margin-top: 4rem;
        }

        .hero-stat {
            text-align: center;
            padding: 2.5rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(59, 130, 246, 0.1);
            min-width: 200px;
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
            padding: 80px 0;
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
            padding: 70px 0 35px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .how-it-works-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(96, 165, 250, 0.02) 0%, transparent 50%);
            z-index: 1;
        }

        .how-it-works-section .container {
            position: relative;
            z-index: 2;
        }

        .how-it-works-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 32px;
            padding: 3rem 2rem 2.5rem;
            text-align: center;
            border: 1px solid rgba(59, 130, 246, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
            transform-origin: center;
            margin-bottom: 2.5rem;
        }

        .how-it-works-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 
                0 32px 64px rgba(59, 130, 246, 0.12),
                0 8px 24px rgba(0, 0, 0, 0.08);
            background: linear-gradient(145deg, #ffffff 0%, #fafbff 100%);
            border-color: rgba(59, 130, 246, 0.15);
        }

        .step-number {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0 auto 2rem;
            position: relative;
            z-index: 2;
            font-family: var(--heading-font);
            box-shadow: 
                0 8px 32px rgba(59, 130, 246, 0.3),
                0 4px 16px rgba(59, 130, 246, 0.2),
                inset 0 2px 0 rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .how-it-works-card:hover .step-number {
            transform: scale(1.08) rotate(5deg);
            box-shadow: 
                0 16px 48px rgba(59, 130, 246, 0.4),
                0 8px 24px rgba(59, 130, 246, 0.3),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
        }

        .step-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1.5rem;
            font-family: var(--heading-font);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .step-description {
            color: #64748b;
            line-height: 1.7;
            font-size: 1rem;
            font-weight: 400;
            max-width: 100%;
            margin: 0 auto;
        }

        /* Unique gradient colors for each step */
        .how-it-works-card:nth-child(1) .step-number {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%);
            box-shadow: 
                0 8px 32px rgba(99, 102, 241, 0.3),
                0 4px 16px rgba(99, 102, 241, 0.2),
                inset 0 2px 0 rgba(255, 255, 255, 0.2);
        }

        .how-it-works-card:nth-child(2) .step-number {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            box-shadow: 
                0 8px 32px rgba(16, 185, 129, 0.3),
                0 4px 16px rgba(16, 185, 129, 0.2),
                inset 0 2px 0 rgba(255, 255, 255, 0.2);
        }

        .how-it-works-card:nth-child(3) .step-number {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
            box-shadow: 
                0 8px 32px rgba(245, 158, 11, 0.3),
                0 4px 16px rgba(245, 158, 11, 0.2),
                inset 0 2px 0 rgba(255, 255, 255, 0.2);
        }

        .how-it-works-card:nth-child(1):hover .step-number {
            box-shadow: 
                0 16px 48px rgba(99, 102, 241, 0.4),
                0 8px 24px rgba(99, 102, 241, 0.3),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
        }

        .how-it-works-card:nth-child(2):hover .step-number {
            box-shadow: 
                0 16px 48px rgba(16, 185, 129, 0.4),
                0 8px 24px rgba(16, 185, 129, 0.3),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
        }

        .how-it-works-card:nth-child(3):hover .step-number {
            box-shadow: 
                0 16px 48px rgba(245, 158, 11, 0.4),
                0 8px 24px rgba(245, 158, 11, 0.3),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
        }

        /* Connection lines between steps - только для больших экранов */
        @media (min-width: 992px) {
            .how-it-works-card:not(:last-child)::after {
                content: '→';
                position: absolute;
                right: -50px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 2.5rem;
                color: #3b82f6;
                font-weight: 800;
                z-index: 3;
                opacity: 0.6;
                transition: all 0.3s ease;
            }

            .how-it-works-card:hover::after {
                opacity: 1;
                transform: translateY(-50%) translateX(5px);
                color: #2563eb;
            }
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

        @media (max-width: 768px) and (min-width: 577px) {
            .hero-section {
                padding: 70px 0;
                min-height: 95vh;
            }

            .how-it-works-section,
            .services-section,
            .comparison-section,
            .cta-section {
                padding: 70px 0 35px 0;
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                padding: 60px 0;
                min-height: 90vh;
            }

            .how-it-works-section,
            .services-section,
            .comparison-section,
            .cta-section {
                padding: 60px 0 30px 0;
            }

            .hero-title {
                font-size: 2.5rem;
                margin-bottom: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
                margin-bottom: 2.5rem;
            }

            .hero-content {
                margin-bottom: 4rem;
                padding: 0 1rem;
            }

            .hero-buttons {
                gap: 1.5rem;
                margin-top: 3.5rem;
                margin-bottom: 4rem;
            }
            
            .hero-stats {
                flex-direction: column;
                align-items: center;
                gap: 2rem;
                margin-top: 3rem;
            }
            
            .hero-stat {
                width: 100%;
                max-width: 280px;
                padding: 2rem 1.5rem;
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

            .advantages-title h2 {
                font-size: 2rem;
            }

            .advantages-subtitle {
                font-size: 1rem;
            }

            .advantage-card {
                padding: 2rem 1.5rem;
                margin-bottom: 1.5rem;
            }

            .advantage-card-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
                margin-bottom: 1.5rem;
            }

            .pricing-section {
                padding: 60px 0 30px 0;
            }

            .pricing-card {
                padding: 2rem 1.5rem;
                margin-bottom: 1.5rem;
            }

            .pricing-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
                margin-bottom: 1.5rem;
            }

            .pricing-title {
                font-size: 1.3rem;
            }

            .pricing-amount {
                font-size: 2rem;
            }

            .pricing-badge {
                padding: 6px 16px;
                font-size: 0.8rem;
            }

            .pricing-btn {
                padding: 10px 24px;
                font-size: 0.95rem;
            }

            .how-it-works-card {
                padding: 2rem 1.5rem;
                margin-bottom: 2rem;
            }

            .step-number {
                width: 80px;
                height: 80px;
                font-size: 2.2rem;
                margin-bottom: 1.5rem;
            }

            .step-title {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }

            .step-description {
                font-size: 1rem;
                max-width: 100%;
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
            padding: 70px 0;
            background: white;
        }

        .pricing-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem 2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }

        .pricing-card.featured {
            border: 2px solid #3b82f6;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .pricing-badge {
            position: absolute;
            top: -1px;
            right: -1px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 8px 20px;
            border-radius: 0 24px 0 20px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .pricing-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
        }

        .pricing-card:hover .pricing-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .pricing-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .pricing-price {
            margin-bottom: 2rem;
        }

        .pricing-amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: #3b82f6;
            display: block;
            line-height: 1;
        }

        .pricing-period {
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
        }

        .pricing-features {
            margin-bottom: 2rem;
        }

        .pricing-feature {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            color: #475569;
        }

        .pricing-feature i {
            color: #22c55e;
            margin-right: 12px;
            font-size: 1rem;
            width: 16px;
        }

        .pricing-cta {
            text-align: center;
        }

        .pricing-btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: 2px solid #3b82f6;
            width: 100%;
        }

        .pricing-btn:hover {
            background: #2563eb;
            border-color: #2563eb;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .pricing-btn.featured {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }

        .pricing-btn.featured:hover {
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
            transform: translateY(-3px);
        }

        .pricing-note {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
            margin: 0;
        }

        .pricing-note i {
            color: #22c55e;
        }

        /* Advantages Section */
        .advantages-section {
            padding: 70px 0 35px 0;
            background: #f8fafc;
        }

        .advantages-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .advantages-title h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .advantages-subtitle {
            font-size: 1.2rem;
            color: #64748b;
            max-width: 700px;
            margin: 0 auto;
        }

        .advantage-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem 2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            height: 100%;
            margin-bottom: 2rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        }

        .advantage-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
        }

        .advantage-card-icon {
            width: 80px;
            height: 80px;
            background: #3b82f6;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .advantage-card:hover .advantage-card-icon {
            background: #2563eb;
            transform: scale(1.1) rotate(5deg);
        }

        /* Comparison Section */
        .comparison-section {
            padding: 70px 0 35px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .comparison-container {
            position: relative;
            z-index: 2;
        }

        .comparison-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .comparison-title h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .comparison-title p {
            font-size: 1.2rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
        }

        .comparison-wrapper {
            display: flex;
            align-items: stretch;
            gap: 0;
            max-width: 1000px;
            margin: 0 auto;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            background: white;
            position: relative;
        }

        .comparison-side {
            flex: 1;
            padding: 3rem 2.5rem;
            position: relative;
            min-height: 500px;
            display: flex;
            flex-direction: column;
        }

        .comparison-left {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-right: 3px solid #f87171;
        }

        .comparison-right {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }

        .comparison-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .comparison-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            font-weight: 800;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .comparison-left .comparison-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .comparison-right .comparison-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .comparison-title-text {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            font-family: var(--heading-font);
        }

        .comparison-left .comparison-title-text {
            color: #991b1b;
        }

        .comparison-right .comparison-title-text {
            color: #1e40af;
        }

        .comparison-price {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            font-family: var(--heading-font);
        }

        .comparison-left .comparison-price {
            color: #dc2626;
        }

        .comparison-right .comparison-price {
            color: #2563eb;
        }

        .comparison-price-label {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 2rem;
            opacity: 0.8;
            font-style: italic;
        }

        .comparison-left .comparison-price-label {
            color: #991b1b;
        }

        .comparison-right .comparison-price-label {
            color: #1e40af;
        }

        .comparison-features {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .comparison-feature {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .comparison-left .comparison-feature {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .comparison-right .comparison-feature {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .comparison-feature i {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .comparison-left .comparison-feature i {
            background: #ef4444;
            color: white;
        }

        .comparison-right .comparison-feature i {
            background: #3b82f6;
            color: white;
        }

        .comparison-vs {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 900;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.4);
            z-index: 10;
            border: 4px solid white;
            font-family: var(--heading-font);
            margin: 0 !important;
        }

        .comparison-cta {
            margin-top: 2rem;
            text-align: center;
        }

        .comparison-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
            border: 2px solid transparent;
        }

        .comparison-left .comparison-btn {
            background: #ef4444;
            color: white;
            opacity: 0.7;
            cursor: not-allowed;
        }

        .comparison-right .comparison-btn {
            background: #3b82f6;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
        }

        .comparison-right .comparison-btn:hover {
            background: #2563eb;
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
            color: white;
        }

        /* Mobile responsiveness */
        @media (max-width: 1200px) {
            .comparison-wrapper {
                max-width: 900px;
            }
            
            .comparison-side {
                padding: 2.5rem 2rem;
            }
        }

        @media (max-width: 992px) {
            .comparison-wrapper {
                max-width: 100%;
                margin: 0 1rem;
            }
            
            .comparison-side {
                padding: 2rem 1.5rem;
            }
            
            .comparison-title h2 {
                font-size: 2.2rem;
            }
            
            .comparison-price {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .comparison-wrapper {
                flex-direction: column;
                gap: 0;
                margin: 0 1rem;
                position: relative;
            }

            .comparison-left {
                border-right: none;
                border-bottom: 3px solid #f87171;
                border-radius: 24px 24px 0 0;
            }
            
            .comparison-right {
                border-radius: 0 0 24px 24px;
            }

            .comparison-side {
                padding: 2.5rem 2rem;
                min-height: auto;
            }

            .comparison-vs {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 70px;
                height: 70px;
                font-size: 1.3rem;
                z-index: 10;
                margin: 0;
            }

            .comparison-title h2 {
                font-size: 2rem;
            }

            .comparison-price {
                font-size: 2rem;
            }
            
            .comparison-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
            
            .comparison-title-text {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 576px) {
            .comparison-section {
                padding: 60px 0;
            }
            
            .comparison-wrapper {
                margin: 0 0.5rem;
                border-radius: 20px;
                position: relative;
            }
            
            .comparison-left {
                border-radius: 20px 20px 0 0;
            }
            
            .comparison-right {
                border-radius: 0 0 20px 20px;
            }

            .comparison-side {
                padding: 2rem 1.5rem;
            }

            .comparison-title {
                margin-bottom: 3rem;
            }

            .comparison-title h2 {
                font-size: 1.8rem;
                line-height: 1.2;
            }

            .comparison-title p {
                font-size: 1rem;
            }

            .comparison-vs {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 60px;
                height: 60px;
                font-size: 1.1rem;
                z-index: 10;
                margin: 0;
            }

            .comparison-price {
                font-size: 1.6rem;
                margin-bottom: 0.3rem;
            }
            
            .comparison-price-label {
                font-size: 0.85rem;
                margin-bottom: 1.2rem;
            }
            
            .comparison-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .comparison-title-text {
                font-size: 1.4rem;
            }
            
            .comparison-header {
                margin-bottom: 2rem;
            }
            
            .comparison-feature {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            
            .comparison-feature i {
                width: 20px;
                height: 20px;
                font-size: 0.8rem;
                margin-right: 0.8rem;
            }
            
            .comparison-btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .comparison-wrapper {
                margin: 0 0.25rem;
                position: relative;
            }
            
            .comparison-side {
                padding: 1.5rem 1rem;
            }
            
            .comparison-title h2 {
                font-size: 1.6rem;
            }
            
            .comparison-price {
                font-size: 1.6rem;
            }
            
            .comparison-title-text {
                font-size: 1.2rem;
            }
            
            .comparison-feature {
                padding: 0.7rem;
                font-size: 0.85rem;
            }
            
            .comparison-vs {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 50px;
                height: 50px;
                font-size: 1rem;
                z-index: 10;
                margin: 0;
            }
        }

        /* Landscape orientation for tablets */
        @media (max-width: 1024px) and (orientation: landscape) {
            .comparison-wrapper {
                max-width: 95%;
            }
            
            .comparison-side {
                padding: 2rem 1.5rem;
            }
            
            .comparison-features {
                gap: 0.8rem;
            }
            
            .comparison-feature {
                padding: 0.8rem;
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .comparison-vs {
                box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4);
            }
            
            .comparison-icon {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
        }

        /* CTA Section */
        .cta-section {
            padding: 70px 0 35px 0;
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
        <!-- Декоративные элементы фона -->
        <div class="hero-bg-element"></div>
        <div class="hero-bg-element"></div>
        <div class="hero-bg-element"></div>
        <div class="hero-bg-element"></div>
        <div class="hero-bg-element"></div>
        <div class="hero-bg-element"></div>
        <div class="hero-wave"></div>
        <div class="hero-wave"></div>
        <div class="hero-geometry"></div>
        <div class="hero-geometry"></div>
        <div class="hero-geometry"></div>
        
        <div class="hero-container">
            <div class="container-fluid">
                
                <!-- Hero Content -->
                <div class="hero-content">
                    
                    <h1 class="hero-title">
                        Твой ИИ для учебы
                    </h1>
                    
                    <p class="hero-subtitle">
                        Подготовься и сдай работу за час
                    </p>
                    
                    <!-- Hero Stats -->
                    <div class="hero-stats">
                        
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
                            <div class="hero-stat-label">тратит ИИ на работу</div>
                        </div>
                        
                    </div>
                    
                    <div class="hero-buttons">
                        <a href="/new" class="btn-hero-primary" id="btnTextWork">
                            <i class="fas fa-pencil-alt me-2"></i>
                            Написать работу
                        </a>
                    </div>
                    
                </div>

            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Всего 3 простых шага</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="how-it-works-card">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Опиши работу</h3>
                        <p class="step-description">
                            Укажи тип, название и требования. Чем подробнее опишешь задание, тем лучше будет результат
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="how-it-works-card">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Проверь результат</h3>
                        <p class="step-description">
                            ИИ подготовит структуру работы и тезисы. Проверь, внеси коррективы и утверди финальный вариант
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="how-it-works-card">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Подготовься к сдаче</h3>
                        <p class="step-description">
                            На основе полученных результатов подготовься к сдаче
                        </p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="/new" class="btn-hero-primary" id="btnTextWork">
                    <i class="fas fa-pencil-alt me-2"></i>
                    Написать работу
                </a>
            </div>
        </div>
    </section>

    
    
    <!-- Advantages Section -->
    <section class="advantages-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Наши преимущества</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="advantage-card">
                        <div class="advantage-card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="advantage-card-title">Быстрый результат</h3>
                        <p class="advantage-card-description">
                            Получите готовую работу всего за 10 минут. Никаких долгих ожиданий и переписок с исполнителями.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="advantage-card">
                        <div class="advantage-card-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="advantage-card-title">100% уникальность</h3>
                        <p class="advantage-card-description">
                            Каждая работа создается с нуля и проходит проверку на плагиат. Гарантируем высокую уникальность.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="advantage-card">
                        <div class="advantage-card-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h3 class="advantage-card-title">Доступные цены</h3>
                        <p class="advantage-card-description">
                            Стоимость работ в 10 раз ниже, чем у фрилансеров. Качественно и недорого для каждого студента.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="advantage-card">
                        <div class="advantage-card-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="advantage-card-title">Поддержка 24/7</h3>
                        <p class="advantage-card-description">
                            Наша команда всегда готова помочь. Обращайтесь в любое время через чат или телефон.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section">
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
                            <div class="pricing-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <h3 class="pricing-title">Базовый</h3>
                            <div class="pricing-price">
                                <span class="pricing-amount">180₽</span>
                                <span class="pricing-period">за работу</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Эссе до 3 страниц</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Проверка на уникальность</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Результат за 10 минут</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Базовое оформление</span>
                            </div>
                        </div>
                        <div class="pricing-cta">
                            <a href="/new" class="pricing-btn">
                                Заказать
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card featured">
                        <div class="pricing-badge">Популярный</div>
                        <div class="pricing-header">
                            <div class="pricing-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3 class="pricing-title">Стандарт</h3>
                            <div class="pricing-price">
                                <span class="pricing-amount">350₽</span>
                                <span class="pricing-period">за работу</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Работы до 10 страниц</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Расширенная проверка</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Результат за 5 минут</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Профессиональное оформление</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Список литературы</span>
                            </div>
                        </div>
                        <div class="pricing-cta">
                            <a href="/new" class="pricing-btn featured">
                                Заказать сейчас
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <div class="pricing-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <h3 class="pricing-title">Премиум</h3>
                            <div class="pricing-price">
                                <span class="pricing-amount">650₽</span>
                                <span class="pricing-period">за работу</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Работы любого объема</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Экспертная проверка</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Мгновенный результат</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Премиум оформление</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Полная библиография</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Персональная поддержка</span>
                            </div>
                        </div>
                        <div class="pricing-cta">
                            <a href="/new" class="pricing-btn">
                                Заказать
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <p class="pricing-note">
                        <i class="fas fa-shield-alt me-2"></i>
                        Гарантия возврата средств в течение 24 часов
                    </p>
                </div>
            </div>
        </div>
    </section>

        
    <!-- Comparison Section -->
    <section class="comparison-section" id="pricing">
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
                        <div class="comparison-feature">
                            <i class="fas fa-times"></i>
                            <span>Долгое ожидание (2-7 дней)</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-times"></i>
                            <span>Риск некачественной работы</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-times"></i>
                            <span>Нет гарантий качества</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-times"></i>
                            <span>Возможные задержки</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-times"></i>
                            <span>Непредсказуемый результат</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-times"></i>
                            <span>Сложное общение</span>
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
                        <div class="comparison-price">от 180₽</div>
                        <div class="comparison-price-label">за работу</div>
                    </div>
                    
                    <div class="comparison-features">
                        <div class="comparison-feature">
                            <i class="fas fa-check"></i>
                            <span>Мгновенный результат (10 мин)</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-check"></i>
                            <span>Гарантия качества ИИ</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-check"></i>
                            <span>Поддержка 24/7</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-check"></i>
                            <span>Проверка на уникальность</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-check"></i>
                            <span>Экономия времени и денег</span>
                        </div>
                        <div class="comparison-feature">
                            <i class="fas fa-check"></i>
                            <span>Простой интерфейс</span>
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

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title" style="color: white;">Готов начать?</h2>
                    <p class="section-subtitle" style="color: rgba(255,255,255,0.9);">
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
                        <li><a href="{{ asset('docs/Политика персональных данных.docx') }}?v={{ time() }}" class="footer-doc-link" target="_blank">Политика конфиденциальности</a></li>
                        <li><a href="{{ asset('docs/ПОЛОЖЕНИЕ о порядке возврата денежных средств за неоказанные платные услуги.docx') }}?v={{ time() }}" class="footer-doc-link" target="_blank">Условия возврата</a></li>
                        <li><a href="{{ asset('docs/Правила оформления заказа.docx') }}?v={{ time() }}" class="footer-doc-link" target="_blank">Правила оформления заказа</a></li>
                        <li><a href="{{ asset('docs/Публичная оферта.docx') }}?v={{ time() }}" class="footer-doc-link" target="_blank">Публичная оферта</a></li>
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
    <script>
        // Scroll progress
        window.addEventListener('scroll', function() {
            const scrollProgress = document.getElementById('scrollProgress');
            const scrolled = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
            scrollProgress.style.width = scrolled + '%';
        });

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
    </script>
</body>
</html> 