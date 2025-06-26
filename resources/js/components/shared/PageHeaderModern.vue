<template>
    <div class="modern-header">
        <!-- Основной контейнер шапки -->
        <div class="header-container">
            <div class="header-content">
                <!-- Левая часть - Логотип -->
                <div class="logo-section" @click="onLogoClick">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <span class="logo-text">GPT Пульт</span>
                    </div>
                </div>

                <!-- Центральная часть - Заголовок (только если передан title) -->
                <div class="title-section" v-if="title">
                    <h1 class="page-title" @click="emit('click:title')">
                        {{ title }}
                    </h1>
                </div>

                <!-- Правая часть - Действия -->
                <div class="actions-section">
                    <!-- Левая кнопка -->
                    <q-btn
                        v-if="leftBtnIcon"
                        :icon="leftBtnIcon"
                        flat
                        round
                        class="action-btn left-btn"
                        @click="onLeftBtnClick"
                    />
                    
                    <!-- Правая кнопка -->
                    <q-btn
                        v-if="rightBtnIcon"
                        :icon="rightBtnIcon"
                        flat
                        round
                        class="action-btn right-btn"
                        @click="onRightBtnClick"
                    />
                </div>
            </div>
        </div>

        <!-- Декоративная линия -->
        <div class="header-divider"></div>
    </div>
</template>

<script setup>
import { user } from '@/composables/auth';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String
    },
    color: {
        type: String,
        default: 'primary'
    },
    leftBtnIcon: {
        type: String,
    },
    leftBtnRoute: {
        type: String
    },
    leftBtnGoBack: {
        type: Boolean,
        default: true
    },
    rightBtnIcon: {
        type: String
    },
    rightBtnRoute: {
        type: String
    },
    logoGoHome: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click:left', 'click:right', 'click:title']);

const onLogoClick = () => {
    if (props.logoGoHome) {
        window.location.href = '/';
    } else {
        window.history.back();
    }
};

const onLeftBtnClick = () => {
    if (props.leftBtnRoute) {
        router.visit(props.leftBtnRoute);
        return;
    }
    if (props.leftBtnGoBack) {
        window.history.back();
        return;
    }
    emit("click:left");
};

const onRightBtnClick = () => {
    if (props.rightBtnRoute) {
        router.visit(props.rightBtnRoute);
        return;
    }
    emit("click:right");
};
</script>

<style scoped>
/* Подключение шрифта Bowler */
@font-face {
    font-family: 'Bowler';
    src: url('/fonts/Bowler.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}

.modern-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
    position: relative;
}

/* Логотип */
.logo-section {
    flex: 0 0 auto;
    cursor: pointer;
}

.logo-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.logo-wrapper:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(45deg, #FFD700, #FFA500);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.logo-icon i {
    font-size: 20px;
    color: #1a1a1a;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.logo-text {
    font-family: 'Bowler', 'Inter', sans-serif;
    font-size: 24px;
    font-weight: 800;
    color: white;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    letter-spacing: -0.5px;
    line-height: 1;
}

/* Заголовок */
.title-section {
    flex: 1;
    display: flex;
    justify-content: center;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    pointer-events: none;
}

.page-title {
    font-family: 'Bowler', 'Inter', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: white;
    margin: 0;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    cursor: pointer;
    pointer-events: auto;
    transition: all 0.3s ease;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

.page-title:hover {
    transform: scale(1.05);
    text-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

/* Действия */
.actions-section {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 14px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.action-btn:active {
    transform: translateY(0);
}

/* Декоративная линия */
.header-divider {
    height: 1px;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255, 255, 255, 0.3) 20%, 
        rgba(255, 255, 255, 0.6) 50%, 
        rgba(255, 255, 255, 0.3) 80%, 
        transparent 100%
    );
}

/* Адаптивный дизайн */
@media (max-width: 768px) {
    .header-container {
        padding: 0 16px;
    }
    
    .header-content {
        height: 60px;
    }
    
    .logo-wrapper {
        gap: 8px;
        padding: 6px 12px;
    }
    
    .logo-icon {
        width: 32px;
        height: 32px;
    }
    
    .logo-icon i {
        font-size: 16px;
    }
    
    .logo-text {
        font-size: 18px;
    }
    
    .page-title {
        font-size: 16px;
        max-width: 200px;
    }
    
    .action-btn {
        width: 40px;
        height: 40px;
    }
}

@media (max-width: 480px) {
    .header-container {
        padding: 0 12px;
    }
    
    .logo-text {
        font-size: 16px;
    }
    
    .page-title {
        font-size: 14px;
        max-width: 150px;
    }
    
    .actions-section {
        gap: 4px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
    }
}

/* Анимации загрузки */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modern-header {
    animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Эффект стекломорфизма для поддерживающих браузеров */
@supports (backdrop-filter: blur(20px)) {
    .modern-header {
        background: rgba(102, 126, 234, 0.8);
    }
    
    .logo-wrapper,
    .action-btn {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
}

/* Темная тема (опционально) */
@media (prefers-color-scheme: dark) {
    .modern-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    }
}
</style> 