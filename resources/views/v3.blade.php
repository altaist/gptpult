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
    </script>
</body>
</html> 