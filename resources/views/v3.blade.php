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
    <link href="{{ asset('css/v3.css') }}" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --heading-font: 'Inter', sans-serif;
        }

        /* Hero Carousel Styles */
        .hero-carousel-wrapper {
            margin: 4rem 0;
            padding: 0 1rem;
        }

        .step-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
        }

        .step-image {
            display: none;
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
            width: 100%;
            max-width: 600px;
            text-align: center;
        }

        .step-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
            font-family: var(--heading-font);
        }

        .step-content p {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .step-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0;
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
            transform: scale(1.1);
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
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .step-dot.active {
            background: #3b82f6;
            transform: scale(1.2);
        }

        .step-dot:hover {
            transform: scale(1.1);
        }

        /* CTA Button */
        .step-cta {
            margin-top: 2rem;
        }

        .btn-step-primary {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 16px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
            border: none;
        }

        .btn-step-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.4);
            color: white;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .how-it-works-section {
                padding: 40px 0;
            }

            .hero-carousel-wrapper {
                margin: 1.5rem 0;
                padding: 1rem;
            }
            
            .step-container {
                gap: 1rem;
            }
            
            .step-content {
                max-width: 100%;
            }
            
            .step-content h3 {
                font-size: 1.4rem;
                margin-bottom: 0.5rem;
            }
            
            .step-content p {
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            .progress-wrapper {
                margin-bottom: 1.5rem;
            }

            .progress-labels {
                font-size: 0.7rem;
            }

            .step-icon-wrapper {
                margin-bottom: 0.8rem;
            }

            .step-icon {
                width: 55px;
                height: 55px;
            }

            .step-icon i {
                font-size: 1.4rem;
            }

            .step-badge-modern {
                padding: 5px 12px;
                font-size: 0.8rem;
                margin-bottom: 0.8rem;
            }

            .step-navigation {
                margin: 1rem 0 0.5rem 0;
            }

            .step-cta {
                margin-top: 1rem;
            }
        }

        /* Hero Carousel Styles */
        .how-it-works-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .hero-carousel-wrapper {
            margin: 2rem 0;
            padding: 1.5rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        /* Progress Bar */
        .progress-wrapper {
            margin-bottom: 2rem;
        }

        .progress-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            position: relative;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            border-radius: 3px;
            width: 0%;
            transition: width 0.5s ease;
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
        }

        .progress-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .progress-label.active {
            color: #3b82f6;
            font-weight: 600;
        }

        /* Step Icon */
        .step-icon-wrapper {
            margin-bottom: 1rem;
        }

        .step-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }

        .step-icon i {
            font-size: 1.8rem;
            color: white;
        }

        /* Modern Badge */
        .step-badge-modern {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .step-content {
            width: 100%;
            max-width: 600px;
            text-align: center;
        }

        .step-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.8rem;
            font-family: var(--heading-font);
        }

        .step-content p {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .step-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0 1rem 0;
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
    <section class="hero-section" id="hero">
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
                        Онлайн - конструктор учебных работ
                    </h1>
                    
                    <p class="hero-subtitle">
                        Подготовься и сдай работу за 10 минут
                    </p>
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
                    Создать работу
                </a>
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
                        <div class="comparison-price">от 350₽</div>
                        <div class="comparison-price-label">за подписку</div>
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

    
    <!-- Advantages Section -->
    <section class="advantages-section" id="features">
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

    <!-- How It Works Section -->
    <section class="how-it-works-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title">Твои 4 шага до сдачи</h2>
                </div>
            </div>
            
            <!-- Hero Carousel -->
            <div class="hero-carousel-wrapper">
                <div class="row">
                    <div class="col-12">
                        <!-- Progress Bar -->
                        <div class="progress-wrapper">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                            <div class="progress-labels">
                                <span class="progress-label active">Начало</span>
                                <span class="progress-label">Тип</span>
                                <span class="progress-label">Результат</span>
                                <span class="progress-label">Готово</span>
                            </div>
                        </div>
                        
                        <!-- Step Content -->
                        <div class="step-container">
                            <div class="step-content">
                                <div class="step-icon-wrapper">
                                    <div class="step-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                </div>
                                
                                <div class="step-badge-modern">Шаг 1 из 4</div>
                                
                                <h3>Укажи тему и детали работы</h3>
                                <p>Расскажи о своей работе: тема, объем, описание. Чем больше деталей, тем лучше результат</p>
                                
                                
                                <div class="step-navigation">
                                    <button class="step-nav-btn prev" disabled>
                                        <i class="fas fa-arrow-left"></i>
                                    </button>
                                    <div class="step-indicators">
                                        <span class="step-dot active" data-step="0"></span>
                                        <span class="step-dot" data-step="1"></span>
                                        <span class="step-dot" data-step="2"></span>
                                        <span class="step-dot" data-step="3"></span>
                                    </div>
                                    <button class="step-nav-btn next">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                                
                                <!-- CTA Button -->
                                <div class="step-cta">
                                    <a href="/new" class="btn-step-primary">
                                        <i class="fas fa-rocket me-2"></i>
                                        Начать создание работы
                                    </a>
                                </div>
                            </div>
                        </div>
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
                            <div class="pricing-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <h3 class="pricing-title">Беасплатный</h3>
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
                            <div class="pricing-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3 class="pricing-title">Стандарт</h3>
                            <div class="pricing-price">
                                <span class="pricing-amount">350₽</span>
                                <span class="pricing-period">в месяц</span>
                            </div>
                        </div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>3 генерации</span>
                            </div>
                            <div class="pricing-feature">
                                <i class="fas fa-check"></i>
                                <span>Работы до 12 страниц</span>
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
                            <a href="/new" class="pricing-btn featured">
                                Заказать сейчас
                            </a>
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
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Hide loading animation
        window.addEventListener('load', function() {
            const loading = document.getElementById('loading');
            if (loading) {
                loading.style.opacity = '0';
                setTimeout(() => {
                    loading.style.display = 'none';
                }, 300);
            }
        });

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

        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Step navigation functionality
        const textworkSteps = [
            { 
                title: "Укажи тему и детали работы", 
                desc: "Расскажи о своей работе: тема, объем, описание. Чем больше деталей, тем лучше результат",
                icon: "fas fa-file-alt",
                badge: "Шаг 1 из 4"
            },
            { 
                title: "Выбери тип работы", 
                desc: "Курсовая, диплом, реферат или эссе - выбери подходящий формат для твоей работы",
                icon: "fas fa-list",
                badge: "Шаг 2 из 4"
            },
            { 
                title: "Получи результат", 
                desc: "Через 10 минут получи готовую уникальную работу с проверкой на плагиат",
                icon: "fas fa-check-circle",
                badge: "Шаг 3 из 4"
            },
            { 
                title: "Доработай при необходимости", 
                desc: "Воспользуйся бесплатными правками или сам отредактируй работу",
                icon: "fas fa-edit",
                badge: "Шаг 4 из 4"
            }
        ];

        function updateStepContent(stepIndex) {
            const step = textworkSteps[stepIndex];
            const stepContent = document.querySelector('.step-content');
            
            // Update icon
            const stepIcon = stepContent.querySelector('.step-icon i');
            stepIcon.className = step.icon;
            
            // Update badge
            const stepBadge = stepContent.querySelector('.step-badge-modern');
            stepBadge.textContent = step.badge;
            
            // Update content
            stepContent.querySelector('h3').textContent = step.title;
            stepContent.querySelector('p').textContent = step.desc;
            
            // Update progress bar
            const progressFill = document.getElementById('progressFill');
            const progressWidth = ((stepIndex) / (textworkSteps.length-1)) * 100;
            progressFill.style.width = progressWidth + '%';
            
            // Update progress labels
            document.querySelectorAll('.progress-label').forEach((label, index) => {
                label.classList.toggle('active', index === stepIndex);
            });
            
            // Update navigation buttons
            const prevBtn = document.querySelector('.step-nav-btn.prev');
            const nextBtn = document.querySelector('.step-nav-btn.next');
            
            prevBtn.disabled = stepIndex === 0;
            nextBtn.disabled = stepIndex === textworkSteps.length - 1;
            
            // Update indicators
            document.querySelectorAll('.step-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === stepIndex);
            });
        }

        // Initialize step navigation
        let currentStep = 0;

        // Handle step navigation clicks
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.step-nav-btn, .step-dot');
            if (!target) return;
            
            if (target.classList.contains('step-nav-btn')) {
                if (target.classList.contains('prev') && currentStep > 0) {
                    currentStep--;
                } else if (target.classList.contains('next') && currentStep < textworkSteps.length - 1) {
                    currentStep++;
                }
            } else if (target.classList.contains('step-dot')) {
                const dots = Array.from(document.querySelectorAll('.step-dot'));
                currentStep = dots.indexOf(target);
            }
            
            updateStepContent(currentStep);
        });
    </script>
</body>
</html> 