<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageLayout from '@/components/shared/PageLayout.vue';
import YandexMetrika from '@/components/shared/YandexMetrika.vue';

const props = defineProps({
    status: {
        type: [String, Number],
        default: 404
    },
    message: {
        type: String,
        default: null
    },
    description: {
        type: String,
        default: null
    }
});

// Конфигурация для разных типов ошибок
const errorConfig = computed(() => {
    const status = String(props.status);
    
    const configs = {
        '403': {
            title: 'Доступ ограничен',
            description: 'Для доступа к этой странице нужны специальные права.',
            icon: '🚫',
            showRobotIcon: true,
            color: '#ef4444'
        },
        '404': {
            title: 'Здесь пусто',
            description: 'Мы искали эту страницу везде, но она исчезла. Возможно, она никогда и не существовала.',
            icon: '🤖',
            showRobotIcon: true,
            color: '#3b82f6'
        },
        '419': {
            title: 'Время вышло',
            description: 'Ваша сессия истекла. Обновите страницу или попробуйте позже.',
            icon: '⏰',
            showRobotIcon: false,
            color: '#f59e0b'
        },
        '429': {
            title: 'Не торопитесь',
            description: 'Слишком много запросов за короткое время. Сделайте небольшую паузу.',
            icon: '⚡',
            showRobotIcon: false,
            color: '#f59e0b'
        },
        '500': {
            title: 'Что-то пошло не так',
            description: 'Наши роботы уже работают над исправлением этой проблемы.',
            icon: '⚙️',
            showRobotIcon: true,
            color: '#ef4444'
        },
        '502': {
            title: 'Сервер спит',
            description: 'Сервер временно недоступен. Попробуйте обновить страницу через минуту.',
            icon: '🔌',
            showRobotIcon: true,
            color: '#ef4444'
        },
        '503': {
            title: 'Технические работы',
            description: 'Мы улучшаем сервис для вас. Вернёмся совсем скоро.',
            icon: '🔧',
            showRobotIcon: true,
            color: '#f59e0b'
        }
    };
    
    return configs[status] || {
        title: 'Что-то пошло не так',
        description: 'Произошла неизвестная ошибка.',
        icon: '❌',
        showRobotIcon: false,
        color: '#ef4444'
    };
});

// Заголовок страницы
const pageTitle = computed(() => {
    return `${props.status} - ${errorConfig.value.title}`;
});

// Всегда используем наши тексты, игнорируя сообщения Laravel
const displayMessage = computed(() => {
    return errorConfig.value.title;
});

// Всегда используем наши описания, игнорируя сообщения Laravel
const displayDescription = computed(() => {
    return errorConfig.value.description;
});

// Функция для перехода на главную
const goHome = () => {
    window.location.href = '/';
};

// Функция для перехода в личный кабинет
const goToLk = () => {
    window.location.href = '/lk';
};

// Функция для связи с поддержкой
const contactSupport = () => {
    window.open('https://t.me/gptpult_help', '_blank');
};

// Функция для определения позиции первого нуля в коде ошибки
const getZeroPosition = computed(() => {
    const statusStr = String(props.status);
    const zeroIndex = statusStr.indexOf('0');
    return zeroIndex !== -1 ? zeroIndex : -1;
});

// Функция для получения цифр кода ошибки с учетом замены нуля
const getErrorCodeDigits = computed(() => {
    const statusStr = String(props.status);
    const digits = statusStr.split('');
    const zeroPos = getZeroPosition.value;
    
    return {
        first: digits[0] || '0',
        second: digits[1] || '0', 
        third: digits[2] || '0',
        zeroPosition: zeroPos
    };
});
</script>

<template>
    <Head :title="pageTitle" />
    <YandexMetrika />

    <page-layout :auto-auth="true">
        <div class="error-page">
            <div class="error-container">
                <!-- Основная ошибка -->
                <div class="error-content">
                    <!-- Код ошибки с иконкой -->
                    <div class="error-code-section">
                        <div class="error-code-wrapper">
                            <!-- Первая позиция -->
                            <div class="error-position">
                                <div v-if="errorConfig.showRobotIcon && getErrorCodeDigits.zeroPosition === 0" class="robot-icon">
                                    <img src="/robot_blue_circle.png" alt="Robot" class="robot-image" />
                                </div>
                                <span v-else class="error-digit">{{ getErrorCodeDigits.first }}</span>
                            </div>
                            
                            <!-- Вторая позиция -->
                            <div class="error-position">
                                <div v-if="errorConfig.showRobotIcon && getErrorCodeDigits.zeroPosition === 1" class="robot-icon">
                                    <img src="/robot_blue_circle.png" alt="Robot" class="robot-image" />
                                </div>
                                <span v-else class="error-digit">{{ getErrorCodeDigits.second }}</span>
                            </div>
                            
                            <!-- Третья позиция -->
                            <div class="error-position">
                                <div v-if="errorConfig.showRobotIcon && getErrorCodeDigits.zeroPosition === 2" class="robot-icon">
                                    <img src="/robot_blue_circle.png" alt="Robot" class="robot-image" />
                                </div>
                                <span v-else class="error-digit">{{ getErrorCodeDigits.third }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Заголовок ошибки -->
                    <h1 class="error-title">{{ displayMessage }}</h1>

                    <!-- Описание ошибки -->
                    <p class="error-description">{{ displayDescription }}</p>

                    <!-- Кнопки действий -->
                    <div class="error-actions">
                        <button @click="goHome" class="action-btn primary-btn">
                            На главную
                        </button>
                        <button @click="goToLk" class="action-btn secondary-btn">
                            В личный кабинет
                        </button>
                    </div>

                    <!-- Кнопка поддержки -->
                    <div class="support-section">
                        <button @click="contactSupport" class="support-btn">
                            Поддержка
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </page-layout>
</template>

<style scoped>
.error-page {
    min-height: 100vh;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.error-container {
    max-width: 600px;
    width: 100%;
    text-align: center;
}

.error-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 32px;
}

/* Код ошибки */
.error-code-section {
    margin-bottom: 20px;
}

.error-code-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    font-size: 120px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1;
}

.error-position {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
}

.error-digit {
    display: block;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-weight: 700;
}

.robot-icon {
    width: 100%;
    height: 100%;
    background: #f0f9ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #5271ff;
    box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
}

.robot-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* Заголовок */
.error-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    line-height: 1.2;
}

/* Описание */
.error-description {
    font-size: 18px;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
    max-width: 500px;
}

/* Кнопки действий */
.error-actions {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
}

.action-btn {
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    min-width: 160px;
}

.primary-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.primary-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.secondary-btn {
    background: #f8fafc;
    color: #374151;
    border: 2px solid #e2e8f0;
}

.secondary-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

/* Поддержка */
.support-section {
    margin-top: 20px;
}

.support-btn {
    background: none;
    border: 1px solid rgba(107, 114, 128, 0.3);
    color: #6b7280;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.support-btn:hover {
    background: rgba(107, 114, 128, 0.05);
    border-color: rgba(107, 114, 128, 0.4);
    color: #374151;
}

/* Адаптивность */
@media (max-width: 768px) {
    .error-page {
        padding: 20px 16px;
    }
    
    .error-code-wrapper {
        font-size: 120px; /* 80px * 1.5 = 120px */
        gap: 8px; /* уменьшено с 12px */
    }
    
    .error-position {
        width: 120px; /* 80px * 1.5 = 120px */
        height: 120px;
    }
    
    .robot-icon {
        width: 100%;
        height: 100%;
        background: #f0f9ff;
        border-radius: 50%;
        border: 3px solid #5271ff;
        box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
    }
    
    .robot-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .error-title {
        font-size: 24px;
    }
    
    .error-description {
        font-size: 16px;
    }
    
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .action-btn {
        width: 100%;
        max-width: 280px;
    }
}

@media (max-width: 480px) {
    .error-page {
        padding: 16px 12px;
    }
    
    .error-code-wrapper {
        font-size: 90px; /* 60px * 1.5 = 90px */
        gap: 6px; /* уменьшено с 8px */
    }
    
    .error-position {
        width: 90px; /* 60px * 1.5 = 90px */
        height: 90px;
    }
    
    .robot-icon {
        width: 100%;
        height: 100%;
        background: #f0f9ff;
        border-radius: 50%;
        border: 2px solid #5271ff;
        box-shadow: 0 6px 24px rgba(59, 130, 246, 0.3);
    }
    
    .robot-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .error-title {
        font-size: 20px;
    }
    
    .error-description {
        font-size: 14px;
    }
    
    .error-content {
        gap: 24px;
    }
}

@media (max-width: 360px) {
    .error-code-wrapper {
        font-size: 72px; /* 48px * 1.5 = 72px */
        gap: 4px; /* уменьшено с 6px */
    }
    
    .error-position {
        width: 72px; /* 48px * 1.5 = 72px */
        height: 72px;
    }
    
    .robot-icon {
        width: 100%;
        height: 100%;
        background: #f0f9ff;
        border-radius: 50%;
        border: 2px solid #5271ff;
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
    }
    
    .robot-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .error-title {
        font-size: 18px;
    }
    
    .error-description {
        font-size: 13px;
    }
    
    .action-btn {
        padding: 12px 20px;
        font-size: 14px;
    }
}
</style> 