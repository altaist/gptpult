<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPT Пульт - Твой ИИ для учебы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #5271ff;
            --primary-hover: #5271ff;
            --accent-color: #5271ff;
            --text-primary: #050038;
            --text-secondary: #5271ff;
            --text-muted: #8B8B8B;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --border-light: #e4e4e4;
            --border-dark: #d1d5db;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            font-size: 16px;
        }

        /* Header Navigation - точная копия Miro */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid #f0f0f0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            height: 64px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Logo */
        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 700;
            font-size: 20px;
            min-width: 140px;
        }

        .header-logo img {
            height: 32px;
            width: auto;
        }

        /* Navigation */
        .header-nav {
            display: flex;
            align-items: center;
            gap: 32px;
            flex: 1;
            justify-content: center;
        }

        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 12px 16px;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .nav-dropdown-btn:hover {
            background: rgba(82, 113, 255, 0.1);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(82, 113, 255, 0.2);
        }

        .nav-dropdown-btn i {
            font-size: 12px;
            transition: transform 0.2s ease;
        }

        .nav-dropdown:hover .nav-dropdown-btn i {
            transform: rotate(180deg);
        }

        .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e4e4e4;
            border-radius: 12px;
            padding: 8px;
            min-width: 200px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s ease;
            z-index: 1001;
        }

        .nav-dropdown:hover .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-dropdown-item {
            display: block;
            padding: 12px 16px;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .nav-dropdown-item:hover {
            background: rgba(82, 113, 255, 0.1);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        /* Simple nav links */
        .nav-link {
            padding: 12px 16px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: rgba(82, 113, 255, 0.1);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(82, 113, 255, 0.2);
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 140px;
            justify-content: flex-end;
        }

        .btn-header {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-header-outline {
            background: transparent;
            border: 1px solid #d1d5db;
            color: var(--text-primary);
        }

        .btn-header-outline:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(82, 113, 255, 0.3);
            text-decoration: none;
        }

        .btn-header-primary {
            background: var(--primary-color);
            border: 1px solid var(--primary-color);
            color: white;
        }

        .btn-header-primary:hover {
            background: #5271ff;
            border-color: #5271ff;
            color: white;
            text-decoration: none;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 25px rgba(82, 113, 255, 0.4);
        }

        /* Mobile menu */
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            gap: 4px;
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
        }

        .mobile-menu-btn span {
            width: 20px;
            height: 2px;
            background: var(--text-primary);
            transition: all 0.3s ease;
        }

        .mobile-menu-btn.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .mobile-menu-btn.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-btn.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }

        .mobile-menu {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            background: white;
            border-bottom: 1px solid #e4e4e4;
            padding: 24px 32px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .mobile-menu.active {
                opacity: 1;
            visibility: visible;
                transform: translateY(0);
        }

        .mobile-nav {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .mobile-nav-item {
            padding: 12px 0;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            border-bottom: 1px solid #f0f0f0;
        }

        .mobile-nav-item:hover {
            background: rgba(82, 113, 255, 0.1);
            color: var(--primary-color);
            transform: translateX(8px);
        }

        .mobile-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 24px;
        }

        /* Hero Section - Miro Style */
            .hero-section {
                min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px 0;
            background: var(--bg-primary);
            text-align: center;
            position: relative;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hero-content {
            margin-bottom: 60px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #E8F4FD;
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 32px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .hero-badge:hover {
            background: rgba(82, 113, 255, 0.15);
            color: var(--primary-color);
            text-decoration: none;
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 20px rgba(82, 113, 255, 0.2);
            }
            
            .hero-title {
            font-size: 72px;
            font-weight: 800;
            line-height: 1.1;
            color: var(--text-primary);
            margin-bottom: 24px;
            letter-spacing: -0.02em;
            }
            
            .hero-subtitle {
            font-size: 24px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 48px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }

        .btn-hero {
                padding: 16px 32px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-hero-primary {
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
        }

        .btn-hero-primary:hover {
            background: #5271ff;
            border-color: #5271ff;
            color: white;
            text-decoration: none;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 30px rgba(82, 113, 255, 0.4);
        }

        .btn-hero-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--border-dark);
        }

        .btn-hero-secondary:hover {
            background: rgba(82, 113, 255, 0.05);
            border-color: var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 20px rgba(82, 113, 255, 0.2);
        }

        /* Hero Stats - внизу hero секции */
        .hero-stats {
            background: var(--bg-secondary);
            padding: 30px 0;
            border-radius: 24px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-stats-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            justify-content: center;
        }

        .hero-stats-title {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 32px;
            font-weight: 500;
            text-align: center;
        }

        .hero-stats-grid {
            display: flex;
            justify-content: center;
            gap: 48px;
            width: 100%;
            max-width: 1000px;
        }

        .hero-stat-item {
            min-width: 280px;
            max-width: 320px;
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: transparent;
            padding: 32px 20px;
            border-radius: 20px;
            box-shadow: none;
            transition: all 0.3s ease;
        }

        .hero-stat-item:hover {
            transform: translateY(-6px);
            box-shadow: none;
        }

        .hero-stat-number {
            font-size: 48px;
            font-weight: 900;
            background: #5271ff;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 12px;
        }

        .hero-stat-label {
            font-size: 16px;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* About Service Section */
        .about-service-section {
            padding: 60px 0;
            background: var(--bg-primary);
        }

        .about-service-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .about-service-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .about-service-title {
            font-size: 56px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .about-service-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: start;
            margin-bottom: 80px;
        }

        .service-description {
            padding-right: 20px;
        }

        .service-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 24px;
            line-height: 1.2;
        }

        .service-text {
            font-size: 18px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .service-types {
            padding-left: 20px;
        }

        .types-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 32px;
            text-align: center;
        }

        .work-types-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .work-type-item {
            background: #5271ff;
            border: none;
            border-radius: 12px;
            padding: 20px 16px;
            text-align: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(82, 113, 255, 0.3);
        }

        .work-type-item:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(82, 113, 255, 0.4);
        }

        .work-type-title {
            color: white;
            font-weight: 600;
            line-height: 1.3;
        }

        .ai-explanation {
            max-width: 1000px;
            margin: 0 auto;
            padding: 60px 0;
            position: relative;
        }

        .ai-explanation::before {
            display: none;
        }

        .visual-card {
            background: transparent;
            border: none;
            padding: 0;
            box-shadow: none;
        }

        .visual-card:hover {
            transform: none;
            box-shadow: none;
        }

        .visual-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .visual-subtitle {
            font-size: 18px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 48px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .visual-steps {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .visual-step {
            background: white;
            border: 1px solid rgba(82, 113, 255, 0.15);
            border-radius: 20px;
            padding: 32px 24px;
            text-align: left;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .visual-step:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(82, 113, 255, 0.15);
            border-color: var(--primary-color);
        }

        .visual-step::before {
            display: none;
        }

        .step-icon {
            width: 56px;
            height: 56px;
            background: #5271ff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .visual-step:hover .step-icon {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(82, 113, 255, 0.4);
        }

        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .step-text {
            color: var(--text-muted);
            font-size: 15px;
            font-weight: 500;
            line-height: 1.5;
            margin: 0;
        }

        .about-service-title {
            font-size: 28px;
        }

        .service-stat {
            background: var(--bg-primary);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .service-stat:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(82, 113, 255, 0.15);
            border-color: var(--primary-color);
        }

        .stat-icon-circle {
            width: 64px;
            height: 64px;
            background: var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .service-stat:hover .stat-icon-circle {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(82, 113, 255, 0.4);
        }

        .service-stat .stat-number {
            font-size: 36px;
            font-weight: 900;
            background: #5271ff;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 8px;
        }

        .service-stat .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Features Section */
        .features-section {
            padding: 60px 0;
            background: var(--bg-primary);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .features-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .features-title {
            font-size: 56px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .features-subtitle {
            font-size: 20px;
            color: var(--text-muted);
            line-height: 1.5;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 48px;
        }

        .feature-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 40px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 50px rgba(82, 113, 255, 0.15);
            border-color: var(--primary-color);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            color: white;
            font-size: 24px;
        }

        .feature-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .feature-description {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .feature-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .feature-link:hover {
            color: #5271ff;
            text-decoration: none;
            transform: translateX(8px) scale(1.05);
        }

        /* Telegram Bot Section */
        .telegram-bot-section {
            padding: 60px 0;
            background: #5271ff;
            position: relative;
            overflow: hidden;
        }

        .telegram-bot-section::before {
            display: none;
        }

        .telegram-bot-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            position: relative;
            z-index: 1;
        }

        .telegram-bot-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .bot-info {
            z-index: 2;
        }

        .bot-title {
            font-size: 48px;
            font-weight: 800;
            color: white;
            line-height: 1.2;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .bot-description {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 500px;
        }

        .bot-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: white;
            color: #5271ff;
            padding: 18px 36px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: none;
        }

        .bot-btn:hover {
            background: #f8fafc;
            color: #5271ff;
            text-decoration: none;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .bot-btn i {
            font-size: 22px;
        }

        .telegram-visual {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .telegram-logo-main {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 100px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: float 6s ease-in-out infinite;
            backdrop-filter: blur(10px);
        }

        .telegram-logo-main::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: -20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: -1;
            animation: pulse 3s ease-in-out infinite;
        }

        .telegram-features {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .telegram-feature {
            position: absolute;
            background: white;
            padding: 12px 16px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 136, 204, 0.2);
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            animation: float 4s ease-in-out infinite;
        }

        .telegram-feature:nth-child(1) {
            top: 20%;
            left: -10%;
            animation-delay: -1s;
        }

        .telegram-feature:nth-child(2) {
            top: 30%;
            right: -15%;
            animation-delay: -2s;
        }

        .telegram-feature:nth-child(3) {
            bottom: 25%;
            left: -5%;
            animation-delay: -3s;
        }

        .telegram-feature:nth-child(4) {
            bottom: 15%;
            right: -10%;
            animation-delay: -4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.7;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }
        }

        /* Mobile styles for telegram bot section */
        @media (max-width: 1024px) {
            .telegram-bot-content {
                grid-template-columns: 1fr;
                gap: 60px;
                text-align: left;
            }

            .telegram-visual {
                display: none;
            }

            .bot-title {
                font-size: 36px;
            }

            .bot-description {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .telegram-bot-section {
                padding: 80px 0;
            }

            .telegram-bot-container {
                padding: 0 24px;
            }

            .telegram-bot-content {
                text-align: left;
            }

            .bot-title {
                font-size: 32px;
                margin-bottom: 20px;
                text-align: left;
            }

            .bot-description {
                font-size: 18px;
                margin-bottom: 32px;
                text-align: left;
            }

            .bot-btn {
                padding: 16px 32px;
                font-size: 16px;
            }

            .telegram-visual {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .telegram-bot-container {
                padding: 0 20px;
            }

            .bot-title {
                font-size: 28px;
                text-align: left;
            }

            .bot-description {
                font-size: 16px;
                text-align: left;
            }

            .bot-btn {
                padding: 14px 28px;
                font-size: 15px;
            }
        }

        /* Pricing Section */
        .pricing-section {
            padding: 60px 0;
            background: var(--bg-secondary);
        }

        .pricing-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 64px;
        }

        .pricing-title {
            font-size: 56px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .pricing-subtitle {
            font-size: 20px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 32px;
            align-items: stretch;
            justify-content: center;
        }

        .pricing-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-light);
            border-radius: 24px;
            padding: 48px 32px;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 580px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .pricing-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(82, 113, 255, 0.2);
            border-color: var(--primary-color);
        }

        .pricing-card.featured {
            border: 3px solid var(--primary-color);
            transform: scale(1.02);
            box-shadow: 0 8px 30px rgba(82, 113, 255, 0.2);
        }

        .pricing-card.featured:hover {
            transform: scale(1.02) translateY(-8px);
            box-shadow: 0 25px 50px rgba(82, 113, 255, 0.3);
        }

        .pricing-badge {
            position: absolute;
            top: -16px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            color: white;
            padding: 10px 28px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(82, 113, 255, 0.4);
        }

        .pricing-card-header {
            margin-bottom: 32px;
        }

        .pricing-plan-name {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 20px;
        }

        .pricing-price {
            font-size: 56px;
            font-weight: 900;
            color: var(--primary-color);
            margin-bottom: 8px;
            line-height: 1;
        }

        .pricing-period {
            font-size: 16px;
            color: var(--text-muted);
            margin-bottom: 0;
            font-weight: 600;
        }

        .pricing-features {
            text-align: left;
            margin-bottom: 40px;
            flex-grow: 1;
        }

        .pricing-feature {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 500;
        }

        .pricing-feature i {
            color: #22c55e;
            font-size: 16px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .pricing-cta {
            width: 100%;
            margin-top: auto;
        }

        /* Mobile pricing adjustments */
        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .pricing-card {
                min-height: auto;
            }
            
            .pricing-card.featured {
                transform: none;
                margin: 0;
            }
            
            .pricing-card.featured:hover {
                transform: translateY(-8px);
            }
        }

        /* CTA Section */
        .cta-section {
            padding: 60px 0;
            background: var(--bg-primary);
            text-align: center;
        }

        .cta-container {
            max-width: 800px;
                margin: 0 auto;
            padding: 0 32px;
        }

        .cta-title {
            font-size: 56px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .cta-subtitle {
            font-size: 20px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 48px;
        }

        /* Footer */
        .footer {
            background: #f8fafc;
            padding: 80px 0 40px;
        }

        .footer-container {
            max-width: 1200px;
                margin: 0 auto;
            padding: 0 32px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .footer-logo {
            height: 40px;
            width: auto;
        }

        .footer-brand-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .footer-description {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .footer-column h5 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 24px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 16px;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
            transform: translateX(6px) scale(1.05);
        }

        .footer-bottom {
            border-top: 1px solid var(--border-light);
            padding-top: 32px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .hero-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }

            .hero-stat-item {
                padding: 24px 16px;
            }

            .hero-stat-number {
                font-size: 36px;
            }

            .hero-stat-label {
                font-size: 14px;
            }

            .header-nav {
                display: none;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .header-container {
                padding: 0 24px;
            }

            .hero-container,
            .hero-stats-container,
            .about-service-container,
            .features-container,
            .pricing-container,
            .cta-container,
            .footer-container {
                padding: 0 24px;
            }

            .about-service-content {
                grid-template-columns: 1fr;
                gap: 60px;
            }

            .service-description {
                padding-right: 0;
            }

            .service-types {
                padding-left: 0;
            }

            .visual-steps {
                grid-template-columns: 1fr;
            }

            .service-stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                min-height: auto;
                padding: 100px 0 0;
            }

            .hero-content {
                margin-bottom: 40px;
            }

            .hero-stats {
                padding: 30px 0;
            }

            .hero-stats-grid {
                flex-direction: column;
                gap: 20px;
                max-width: 100%;
            }
            .hero-stat-item {
                min-width: 0;
                max-width: 100%;
                width: 100%;
                margin: 0 auto;
                padding: 28px 10px;
                border-radius: 16px;
            }

            .hero-stat-number {
                font-size: 32px;
                margin-bottom: 8px;
            }

            .hero-stat-label {
                font-size: 13px;
            }

            .hero-stats-title {
                font-size: 16px;
                margin-bottom: 24px;
            }

            .header-container {
                padding: 0 20px;
                height: 60px;
            }

            .header-logo {
                font-size: 18px;
                min-width: auto;
            }

            .header-logo img {
                height: 24px;
            }

            .header-actions {
                display: none;
            }

            .mobile-menu {
                padding: 20px;
                top: 60px;
            }

            .mobile-nav {
                gap: 12px;
            }

            .mobile-nav-item {
                padding: 16px 20px;
                font-size: 16px;
                border-radius: 12px;
                background: var(--bg-secondary);
                margin-bottom: 8px;
                border-bottom: none;
                text-align: center;
            }

            .mobile-actions {
                margin-top: 24px;
                gap: 12px;
            }

            .mobile-actions .btn-header {
                padding: 14px 24px;
                font-size: 16px;
                font-weight: 700;
                border-radius: 12px;
                text-align: center;
            }

            .hero-container {
                padding: 0 20px;
            }

            .hero-stats-container {
                padding: 0 20px;
            }

            .hero-badge {
                padding: 10px 20px;
                font-size: 13px;
                margin-bottom: 24px;
                border-radius: 20px;
            }

            .hero-title {
                font-size: 42px;
                margin-bottom: 20px;
                line-height: 1.1;
            }

            .hero-subtitle {
                font-size: 18px;
                margin-bottom: 32px;
                line-height: 1.5;
            }

            .hero-cta {
                flex-direction: column;
                gap: 12px;
                margin-bottom: 0;
            }

            .btn-hero {
                width: 100%;
                justify-content: center;
                padding: 16px 32px;
                font-size: 16px;
                border-radius: 12px;
            }

            .about-service-section {
                padding: 40px 0;
            }

            .about-service-container {
                padding: 0 20px;
            }

            .about-service-header {
                margin-bottom: 60px;
            }

            .about-service-title {
                font-size: 36px;
                margin-bottom: 20px;
            }

            .about-service-content {
                margin-bottom: 60px;
            }

            .service-title {
                font-size: 26px;
                margin-bottom: 20px;
            }

            .service-text {
                font-size: 16px;
                margin-bottom: 32px;
            }

            .work-types-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .work-type-item {
                padding: 16px 12px;
                font-size: 15px;
            }

            .visual-card {
                padding: 32px 24px;
            }

            .visual-title {
                font-size: 20px;
                margin-bottom: 24px;
            }

            .visual-steps {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .visual-step {
                padding: 24px 20px;
            }

            .step-text {
                font-size: 14px;
            }

            .service-stats-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .service-stat {
                padding: 24px 20px;
            }

            .stat-icon-circle {
                width: 48px;
                height: 48px;
                font-size: 20px;
                margin-bottom: 16px;
            }

            .service-stat .stat-number {
                font-size: 28px;
            }

            .service-stat .stat-label {
                font-size: 13px;
            }

            .features-section {
                padding: 40px 0;
            }

            .features-container {
                padding: 0 20px;
            }

            .features-header {
                margin-bottom: 60px;
            }

            .features-title {
                font-size: 36px;
                margin-bottom: 20px;
            }

            .features-subtitle {
                font-size: 18px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .feature-card {
                padding: 28px 24px;
                border-radius: 20px;
            }

            .feature-icon {
                width: 60px;
                height: 60px;
                border-radius: 16px;
                margin-bottom: 20px;
                font-size: 24px;
            }

            .feature-title {
                font-size: 22px;
                margin-bottom: 16px;
            }

            .feature-description {
                font-size: 15px;
                margin-bottom: 20px;
                line-height: 1.6;
            }

            .telegram-bot-section {
                padding: 40px 0;
            }

            .telegram-bot-container {
                padding: 0 24px;
            }

            .telegram-bot-content {
                text-align: left;
            }

            .bot-title {
                font-size: 32px;
                margin-bottom: 20px;
                text-align: left;
            }

            .bot-description {
                font-size: 18px;
                margin-bottom: 32px;
                text-align: left;
            }

            .bot-btn {
                padding: 16px 32px;
                font-size: 16px;
            }

            .telegram-visual {
                display: none;
            }

            .pricing-section {
                padding: 40px 0;
            }

            .pricing-container {
                padding: 0 20px;
            }

            .pricing-header {
                margin-bottom: 48px;
            }

            .pricing-title {
                font-size: 36px;
                margin-bottom: 20px;
            }

            .pricing-subtitle {
                font-size: 18px;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                max-width: 100%;
                margin: 0;
            }
            
            .pricing-card {
                min-height: auto;
                padding: 32px 24px;
                border-radius: 20px;
            }
            
            .pricing-card.featured {
                transform: none;
                margin: 0;
                order: -1;
            }
            
            .pricing-card.featured:hover {
                transform: translateY(-4px);
            }

            .pricing-card:hover {
                transform: translateY(-4px);
            }

            .pricing-badge {
                top: -12px;
                padding: 8px 20px;
                font-size: 12px;
            }

            .pricing-card-header {
                margin-bottom: 24px;
            }

            .pricing-plan-name {
                font-size: 22px;
                margin-bottom: 16px;
            }

            .pricing-price {
                font-size: 42px;
            }

            .pricing-period {
                font-size: 14px;
            }

            .pricing-features {
                margin-bottom: 28px;
            }

            .pricing-feature {
                margin-bottom: 12px;
                font-size: 15px;
            }

            .btn-hero.pricing-cta {
                padding: 14px 28px;
                font-size: 16px;
                border-radius: 12px;
            }

            .cta-section {
                padding: 40px 0;
            }

            .cta-container {
                padding: 0 20px;
            }

            .cta-title {
                font-size: 36px;
                margin-bottom: 20px;
            }

            .cta-subtitle {
                font-size: 18px;
                margin-bottom: 32px;
            }

            .footer {
                padding: 60px 0 30px;
            }

            .footer-container {
                padding: 0 20px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
                text-align: center;
            }

            .footer-brand {
                justify-content: center;
                margin-bottom: 20px;
            }

            .footer-brand-name {
                font-size: 20px;
            }

            .footer-description {
                font-size: 15px;
                max-width: 280px;
                margin: 0 auto;
            }

            .footer-column h5 {
                font-size: 16px;
                margin-bottom: 20px;
            }

            .footer-links a {
                font-size: 15px;
            }

            .footer-bottom {
                padding-top: 24px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .hero-stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .hero-stat-item {
                padding: 20px 4px;
                border-radius: 12px;
            }

            .hero-stat-number {
                font-size: 28px;
            }

            .header-container {
                padding: 0 16px;
            }

            .hero-container,
            .hero-stats-container,
            .about-service-container,
            .features-container,
            .pricing-container,
            .cta-container,
            .footer-container {
                padding: 0 16px;
            }

            .mobile-menu {
                padding: 16px;
            }

            .mobile-nav-item {
                padding: 14px 18px;
                font-size: 15px;
            }

            .mobile-actions .btn-header {
                padding: 12px 20px;
                font-size: 15px;
            }

            .hero-title {
                font-size: 32px;
            }

            .hero-subtitle {
                font-size: 16px;
            }

            .btn-hero {
                padding: 14px 28px;
                font-size: 15px;
            }

            .about-service-title {
                font-size: 28px;
            }

            .service-title {
                font-size: 22px;
            }

            .service-text {
                font-size: 15px;
            }

            .work-type-item {
                padding: 14px 10px;
                font-size: 14px;
            }

            .visual-card {
                padding: 24px 16px;
            }

            .visual-title {
                font-size: 18px;
            }

            .visual-step {
                padding: 14px 16px;
            }

            .step-text {
                font-size: 14px;
            }

            .service-stat {
                padding: 20px 16px;
            }

            .stat-icon-circle {
                width: 48px;
                height: 48px;
                font-size: 18px;
            }

            .service-stat .stat-number {
                font-size: 24px;
            }

            .service-stat .stat-label {
                font-size: 12px;
            }

            .features-title,
            .pricing-title,
            .cta-title {
                font-size: 28px;
            }

            .features-subtitle,
            .pricing-subtitle,
            .cta-subtitle {
                font-size: 16px;
            }

            .feature-card {
                padding: 24px 20px;
            }

            .pricing-card {
                padding: 28px 20px;
            }

            .footer-description {
                max-width: 240px;
            }
        }

        .footer-links a:hover {
            color: var(--primary-color);
            transform: translateX(6px) scale(1.05);
        }

        .footer-requisites {
            background: rgba(82, 113, 255, 0.05);
            border: 1px solid rgba(82, 113, 255, 0.1);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 48px;
            transition: all 0.3s ease;
        }

        .footer-requisites:hover {
            background: rgba(82, 113, 255, 0.08);
            border-color: rgba(82, 113, 255, 0.2);
        }

        .requisites-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 24px;
            text-align: center;
        }

        .requisites-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }

        .requisites-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .requisites-item {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .requisites-item strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Mobile styles for requisites */
        @media (max-width: 768px) {
            .footer-requisites {
                padding: 24px 20px;
                margin-bottom: 32px;
            }

            .requisites-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .requisites-title {
                font-size: 18px;
                margin-bottom: 20px;
            }

            .requisites-item {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Navigation - точная копия Miro -->
    <header class="header">
        <div class="header-container">
            <!-- Logo -->
            <a href="#" class="header-logo">
                <img src="{{ asset('gptpult.svg') }}" alt="GPT Пульт">
            </a>

            <!-- Navigation -->
            <nav class="header-nav">
                <a href="#features" class="nav-link">Возможности</a>
                


                <a href="#pricing" class="nav-link">Тарифы</a>

                <a href="https://t.me/gptpult_bot" class="nav-link">ТГ бот</a>
                <a href="https://t.me/gptpult_help" class="nav-link">Поддержка</a>
            </nav>

            <!-- Actions -->
            <div class="header-actions">
                <a href="/lk" class="btn-header btn-header-outline">Личный кабинет</a>
            </div>

            <!-- Mobile menu button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <span></span>
                <span></span>
                <span></span>
            </button>
            </div>

        <!-- Mobile menu -->
        <div class="mobile-menu" id="mobileMenu">
            <nav class="mobile-nav">
                <a href="#features" class="mobile-nav-item">Возможности</a>
                <a href="#pricing" class="mobile-nav-item">Тарифы</a>
                <a href="https://t.me/gptpult_help" class="mobile-nav-item" target="_blank">Поддержка</a>
                <a href="#contact" class="mobile-nav-item">Контакты</a>
            </nav>
            <div class="mobile-actions">
                <a href="/lk" class="btn-header btn-header-outline" onclick="saveIntendedUrl('/lk')">Личный кабинет</a>
                <a href="/new" class="btn-header btn-header-primary" onclick="saveIntendedUrl('/new')">Создать работу</a>
                    </div>
                    </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <a href="#" class="hero-badge">
                    Самый умный AI сервис для студентов и школьников
                </a>

                <h1 class="hero-title">
                    Твоя работа<br>
                    за 10 минут
                </h1>

                <p class="hero-subtitle">
                    Простой и мощный инструмент для создания качественных академических работ 
                    с помощью искусственного интеллекта. Быстро, качественно, доступно.
                </p>

                <div class="hero-cta">
                    <a href="/new" class="btn-hero btn-hero-primary" onclick="saveIntendedUrl('/new')">
                        <span class="btn-text">Создать работу</span>
                        <i class="fas fa-arrow-right btn-icon"></i>
                    </a>
                    <a href="#features" class="btn-hero btn-hero-secondary">
                        Узнать больше
                    </a>
                </div>
            </div>
        </div>
        <!-- Hero Stats (desktop/tablet only) -->
        <div class="hero-stats">
            <div class="hero-stats-container">
                <div class="hero-stats-grid">
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">3,000+</div>
                        <div class="hero-stat-label">Созданных работ</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">от 90%</div>
                        <div class="hero-stat-label">Гарантия уникальности</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">10 мин</div>
                        <div class="hero-stat-label">Среднее время генерации</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="hero-stat-number">24/7</div>
                        <div class="hero-stat-label">Поддержка</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Service Section -->
    <section class="about-service-section">
        <div class="about-service-container">
        

            <div class="about-service-content">
                <div class="service-description">
                    <h3 class="service-title">Современное решение для студентов</h3>
                    <p class="service-text">
                        Наша платформа использует передовые технологии искусственного интеллекта для создания качественных учебных работ. Мы автоматизировали процесс написания рефератов, эссе, курсовых и других академических текстов.
                    </p>
                    <p class="service-text">
                        GPT Пульт поможет вам сэкономить время и силы на создании академических работ, при этом гарантируя высокое качество и соответствие всем требованиям. Больше не нужно тратить недели на написание - теперь это займет всего 10 минут.
                    </p>
                </div>

                <div class="service-types">
                    <h4 class="types-title">Типы работ</h4>
                    <div class="work-types-grid">
                        <div class="work-type-item" onclick="saveIntendedUrlAndRedirect('/new')">
                            <div class="work-type-content">
                                <span class="work-type-title">Отчет о практике</span>
                            </div>
                        </div>
                        <div class="work-type-item" onclick="saveIntendedUrlAndRedirect('/new')">
                            <div class="work-type-content">
                                <span class="work-type-title">Курсовая работа</span>
                            </div>
                        </div>
                        <div class="work-type-item" onclick="saveIntendedUrlAndRedirect('/new')">
                            <div class="work-type-content">
                                <span class="work-type-title">Доклад</span>
                            </div>
                        </div>
                        <div class="work-type-item" onclick="saveIntendedUrlAndRedirect('/new')">
                            <div class="work-type-content">
                                <span class="work-type-title">Эссе</span>
                            </div>
                        </div>
                        <div class="work-type-item" onclick="saveIntendedUrlAndRedirect('/new')">
                            <div class="work-type-content">
                                <span class="work-type-title">Реферат</span>
                            </div>
                        </div>
                        <div class="work-type-item" onclick="saveIntendedUrlAndRedirect('/new')">
                            <div class="work-type-content">
                                <span class="work-type-title">Научная статья</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ai-explanation">
                <div class="visual-card">
                    <h4 class="visual-title">Настроили ИИ специально для тебя</h4>
                    <p class="visual-subtitle">
                        Наша система использует передовые технологии искусственного интеллекта для создания уникальных и качественных работ
                    </p>
                    <div class="visual-steps">
                        <div class="visual-step">
                            <div class="step-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h5 class="step-title">Mix AI</h5>
                            <p class="step-text">
                                Над вашей работой одновременно работают сразу несколько передовых AI ассистентов для максимального качества
                            </p>
                        </div>
                        <div class="visual-step">
                            <div class="step-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="step-title">AI антидетектинг</h5>
                            <p class="step-text">
                                Специальная обработка работы для обеспечения максимальной уникальности и естественности текста
                            </p>
                        </div>
                        <div class="visual-step">
                            <div class="step-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <h5 class="step-title">Соответствие ГОСТ РФ</h5>
                            <p class="step-text">
                                Все работы автоматически проверяются и оформляются в строгом соответствии с российскими стандартами
                            </p>
                        </div>
                        <div class="visual-step">
                            <div class="step-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h5 class="step-title">Результат за 10 минут</h5>
                            <p class="step-text">
                                Быстрая генерация без потери качества благодаря оптимизированным алгоритмам обработки
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="features-container">
            <div class="features-header">
                <h2 class="features-title">
                    Почему студенты выбирают нас
                </h2>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                </div>
                    <h3 class="feature-title">Умный ИИ-помощник</h3>
                    <p class="feature-description">
                        Передовые модели ИИ создают уникальный контент по вашим требованиям. 
                        Автоматическое соблюдение ГОСТов и академических стандартов
                    </p>
                    <a href="/new" class="feature-link" onclick="saveIntendedUrl('/new')">
                        Попробовать <i class="fas fa-arrow-right"></i>
                    </a>
            </div>
            
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Быстрый результат</h3>
                    <p class="feature-description">
                        Напиши работу сам за 10 минут
                        Больше времени на изучение материала и подготовку к защите
                    </p>
                    <a href="/new" class="feature-link" onclick="saveIntendedUrl('/new')">
                        Начать сейчас <i class="fas fa-arrow-right"></i>
                    </a>
                    </div>
                    
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                        </div>
                    <h3 class="feature-title">Гарантия качества</h3>
                    <p class="feature-description">
                        90% уникальности текста, профессиональное оформление и соответствие 
                        всем требованиям
                    </p>
                    <a href="/lk" class="feature-link" onclick="saveIntendedUrl('/lk')">
                        Подробнее <i class="fas fa-arrow-right"></i>
                    </a>
                    </div>
                    
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="feature-title">Все типы работ</h3>
                    <p class="feature-description">
                        Рефераты, курсовые, отчеты по практике, эссе, доклады и научные статьи
                    </p>
                    <a href="/new" class="feature-link" onclick="saveIntendedUrl('/new')">
                        Выбрать тип <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <h3 class="feature-title">Telegram-бот</h3>
                    <p class="feature-description">
                        Создавайте работы прямо в Telegram. Удобные уведомления о статусе 
                        и мгновенный доступ ко всем функциям сервиса
                    </p>
                    <a href="https://t.me/gptpult_bot" class="feature-link" target="_blank">
                        Подключить <i class="fas fa-external-link-alt"></i>
                    </a>
                    </div>
                    
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 class="feature-title">Поддержка 24/7</h3>
                    <p class="feature-description">
                        Наша команда всегда готова помочь. Быстро ответим на вопросы 
                        и решим любые проблемы в любое время
                    </p>
                    <a href="https://t.me/gptpult_help" class="feature-link" target="_blank">
                        Написать <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section" id="pricing">
        <div class="pricing-container">
            <div class="pricing-header">
                <h2 class="pricing-title">Получай максимум от платформы</h2>
                </div>

            <div class="pricing-grid">
                    <div class="pricing-card">
                    <div class="pricing-card-header">
                        <h3 class="pricing-plan-name">Бесплатный</h3>
                        <div class="pricing-price">0₽</div>
                        <div class="pricing-period">навсегда</div>
                            </div>
                    
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>Содержание работы</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>Цели и задачи</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Список литературы</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>Быстрый результат</span>
                        </div>
                        <div class="pricing-feature">
                            <i class="fas fa-times" style="color: #e53935;"></i>
                            <span>Нет полной генерации</span>
                        </div>
                            </div>
                    
                    <a href="/new" class="btn-hero btn-hero-secondary pricing-cta" onclick="saveIntendedUrl('/new')">
                        Начать бесплатно
                    </a>
                </div>
                
                    <div class="pricing-card featured">
                        <div class="pricing-badge">Популярный</div>
                    <div class="pricing-card-header">
                        <h3 class="pricing-plan-name">Абонемент</h3>
                        <div class="pricing-price">300₽</div>
                        <div class="pricing-period">3 полные работы</div>
                            </div>
                    
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>Полные работы до 25 страниц</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>90% уникальности</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>Профессиональное оформление</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>Приоритетная поддержка</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                            <span>Без ограничений по времени</span>
                            </div>
                        </div>
                    
                    <a href="/lk" class="btn-hero btn-hero-primary pricing-cta" onclick="saveIntendedUrl('/lk')">
                        Купить абонемент
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- Telegram Bot Section -->
    <section class="telegram-bot-section">
        <div class="telegram-bot-container">
            <div class="telegram-bot-content">
                <div class="bot-info">
                    <h2 class="bot-title">
                        Telegram-бот GPT Пульт
                    </h2>
                    <p class="bot-description">
                        Создавайте работы прямо в Telegram. Удобные уведомления о статусе 
                        и мгновенный доступ ко всем функциям сервиса
                    </p>
                    <a href="https://t.me/gptpult_bot" class="bot-btn" target="_blank">
                        <i class="fab fa-telegram-plane"></i>
                        Подключить
                    </a>
                </div>
                <div class="telegram-visual">
                    <div class="telegram-logo-main">
                        <i class="fab fa-telegram-plane"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <h2 class="cta-title">
                Готов начать?
            </h2>
            <p class="cta-subtitle">
                Присоединяйся к тысячам студентов, которые уже экономят время с GPT Пульт
            </p>
            <a href="/new" class="btn-hero btn-hero-primary" onclick="saveIntendedUrl('/new')">
                <i class="fas fa-rocket"></i>
                Создать первую работу
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">
                        <img src="{{ asset('gptpult.svg') }}" alt="GPT Пульт" class="footer-logo">
                    </div>
                    <p class="footer-description">
                        Современная платформа для создания учебных работ с помощью 
                        искусственного интеллекта. Быстро, качественно, доступно.
                    </p>
                </div>

                <div class="footer-column">
                    <h5>Продукт</h5>
                    <ul class="footer-links">
                        <li><a href="#features">Возможности</a></li>
                        <li><a href="#pricing">Тарифы</a></li>
                        <li><a href="/new" onclick="saveIntendedUrl('/new')">Создать работу</a></li>
                        <li><a href="https://t.me/gptpult_bot" target="_blank">Telegram-бот</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h5>Поддержка</h5>
                    <ul class="footer-links">
                        <li><a href="https://t.me/gptpult_help" target="_blank">Помощь</a></li>
                        <li><a href="#contact">Контакты</a></li>
                        <li><a href="/lk" onclick="saveIntendedUrl('/lk')">Личный кабинет</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h5>Документы</h5>
                    <ul class="footer-links">
                        <li><a href="{{ asset('docs/Политика персональных данных.docx') }}" target="_blank">Политика конфиденциальности</a></li>
                        <li><a href="{{ asset('docs/Публичная оферта.docx') }}" target="_blank">Оферта</a></li>
                        <li><a href="{{ asset('docs/Реквизиты.docx') }}" target="_blank">Реквизиты</a></li>
                    </ul>
            </div>
            
                <div class="footer-column">
                    <h5>Компания</h5>
                    <ul class="footer-links">
                        <li><a href="#features">О сервисе</a></li>
                        <li><a href="#stats">Статистика</a></li>
                        <li><a href="https://t.me/gptpult_help" target="_blank">Обратная связь</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Реквизиты -->
            <div class="footer-requisites">
                <h5 class="requisites-title">Реквизиты</h5>
                <div class="requisites-content">
                    <div class="requisites-column">
                        <div class="requisites-item">
                            <strong>Название организации:</strong><br>
                            ИНДИВИДУАЛЬНЫЙ ПРЕДПРИНИМАТЕЛЬ ВЛАСЕНКО СЕРГЕЙ ВЛАДИМИРОВИЧ
                        </div>
                        
                        <div class="requisites-item">
                            <strong>Юридический адрес:</strong><br>
                            630132, РОССИЯ, НОВОСИБИРСКАЯ ОБЛ, Г НОВОСИБИРСК, УЛ 1905 ГОДА, Д 85/2, КВ 250
                        </div>
                        
                        <div class="requisites-item">
                            <strong>ИНН:</strong> 041105019528
                        </div>
                        <div class="requisites-item">
                            <strong>ОГРНИП:</strong> 318547600089160
                        </div>
                    </div>
                    <div class="requisites-column">
                        <div class="requisites-item">
                            <strong>Расчетный счет:</strong> 40802810700000572721
                        </div>
                        <div class="requisites-item">
                            <strong>Банк:</strong> АО «ТБанк»
                        </div>
                        <div class="requisites-item">
                            <strong>ИНН банка:</strong> 7710140679
                        </div>
                        <div class="requisites-item">
                            <strong>БИК банка:</strong> 044525974
                        </div>
                        <div class="requisites-item">
                            <strong>Корреспондентский счет:</strong> 30101810145250000974
                        </div>
                        <div class="requisites-item">
                            <strong>Юридический адрес банка:</strong><br>
                            127287, г. Москва, ул. Хуторская 2-я, д. 38А, стр. 26
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
        // Smooth scrolling
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

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = 'none';
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenuBtn.classList.toggle('active');
                mobileMenu.classList.toggle('active');
            });

            // Close mobile menu when clicking on links
            document.querySelectorAll('.mobile-nav-item').forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenuBtn.classList.remove('active');
                    mobileMenu.classList.remove('active');
                });
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenuBtn.classList.remove('active');
                mobileMenu.classList.remove('active');
            }
        });

        // Dropdown navigation (for desktop)
        document.querySelectorAll('.nav-dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
            });
        });

        // Функции для сохранения intended URL
        function saveIntendedUrl(url) {
            // Сохраняем URL для использования после авторизации
            localStorage.setItem('intended_url', url);
        }

        function saveIntendedUrlAndRedirect(url) {
            // Сохраняем URL и выполняем переход
            saveIntendedUrl(url);
            window.location.href = url;
        }
    </script>
</body>
</html> 