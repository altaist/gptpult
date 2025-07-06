<template>
    <div class="modern-page-layout">
        <!-- Современная шапка -->
        <page-header
            :title="title"
            :left-btn-icon="leftBtnIcon"
            :left-btn-route="leftBtnRoute"
            :left-btn-go-back="leftBtnGoBack"
            :right-btn-icon="rightBtnIcon"
            :right-btn-route="rightBtnRoute"
            :logo-go-home="logoGoHome"
            :documents-count="documentsCount"
            :balance="balance"
            @click:left="emit('click:header:left')"
            @click:right="emit('click:header:right')"
            @click:title="emit('click:header:title')"
        />

        <!-- Основной контент -->
        <main class="modern-main-content">
            <div class="content-container">
                <slot />
            </div>
        </main>

        <!-- Современный подвал -->
        <page-footer 
            :title="footerText"
            :is-sticky="true"
            :menu="footerMenu || localMenu"
            @click:left="emit('click:footer:left')"
            @click:right="emit('click:footer:right')"
            @click:menu="emit('click:footer:menu', $event)"
        />
    </div>
</template>

<script setup>
import PageHeader from '@/components/shared/PageHeader.vue';
import PageFooter from '@/components/shared/PageFooter.vue';
import { onMounted } from 'vue';
import { checkAuth } from '@/composables/auth';

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    footerText: {
        type: String,
        default: ''
    },
    footerMenu: {
        type: Array,
        default: null
    },
    autoAuth: {
        type: Boolean,
        default: false
    },
    leftBtnIcon: {
        type: String,
        default: "fa-solid fa-arrow-left"
    },
    leftBtnRoute: {
        type: String
    },
    leftBtnGoBack: {
        type: Boolean,
        default: true
    },
    rightBtnIcon: {
        type: String,
        default: 'fa-solid fa-user'
    },
    rightBtnRoute: {
        type: String,
        default: '/lk'
    },
    logoGoHome: {
        type: Boolean,
        default: false
    },
    showTitle: {
        type: Boolean,
        default: false
    },
    documentsCount: {
        type: Number,
        default: 0
    },
    balance: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits([
    'click:header:left', 
    'click:header:right', 
    'click:header:title', 
    'click:footer:left', 
    'click:footer:right', 
    'click:footer:title', 
    'click:footer:menu'
]);

const model = defineModel({
    type: String,
});

const localMenu = [
    {
        id: 1,
        label: 'Главная',
        icon: 'fa-solid fa-home'
    },
    {
        id: 2,
        label: 'Документы',
        icon: 'fa-solid fa-file-text'
    },
    {
        id: 3,
        label: 'Профиль',
        icon: 'fa-solid fa-user'
    }
];

onMounted(async () => {
    if (props.autoAuth) {
        await checkAuth();
    }
});
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

.modern-page-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    font-family: 'Inter', 'Bowler', sans-serif;
}

.modern-main-content {
    flex: 1;
    position: relative;
    z-index: 1;
    margin-bottom: 100px;
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px;
    animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) 0.2s both;
}

/* Анимация появления контента */
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

/* Адаптивный дизайн */
@media (max-width: 768px) {
    .content-container {
        padding: 20px 16px;
    }
}

@media (max-width: 480px) {
    .content-container {
        padding: 16px 12px;
    }
}

/* Улучшенная прокрутка на мобильных */
@media (max-width: 768px) {
    .modern-page-layout {
        -webkit-overflow-scrolling: touch;
    }
}

/* Поддержка темной темы */
@media (prefers-color-scheme: dark) {
    .modern-page-layout {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    }
}

/* Эффекты стекломорфизма */
@supports (backdrop-filter: blur(20px)) {
    .content-container {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    @media (prefers-color-scheme: dark) {
        .content-container {
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    }
}

/* Стили для скроллбара */
.modern-page-layout ::-webkit-scrollbar {
    width: 8px;
}

.modern-page-layout ::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
}

.modern-page-layout ::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 4px;
}

.modern-page-layout ::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
}
</style> 