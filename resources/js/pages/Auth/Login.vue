<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { checkAuth } from '@/composables/auth';
import { useRecaptcha } from '@/composables/recaptcha';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    recaptcha: {
        type: Object,
        default: () => ({
            site_key: null,
            enabled: false
        })
    }
});

// Состояния
const isLoading = ref(true);
const loadingText = ref('Небольшая проверка безопасности');
const userIP = ref('');
const sessionId = ref('');

// reCAPTCHA
const { initRecaptcha, executeAction, isReady: isRecaptchaReady } = useRecaptcha();
const recaptchaInitialized = ref(false);

// Получение IP адреса
const getUserIP = async () => {
    try {
        const response = await fetch('/api/user-ip', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        const data = await response.json();
        userIP.value = data.ip;
    } catch (error) {
        console.error('Failed to get IP:', error);
        userIP.value = 'Не удалось получить';
    }
};

// Получение session ID (только первые 8 символов для безопасности)
const getSessionId = () => {
    // Получаем CSRF токен из meta тега как индикатор сессии
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        sessionId.value = token.substring(0, 8) + '...';
    } else {
        sessionId.value = 'Не определен';
    }
};

const contactSupport = () => {
    window.open('https://t.me/gptpult_help', '_blank');
};

onMounted(async () => {
    // Получаем IP и session ID
    await getUserIP();
    getSessionId();
    
    // Инициализируем reCAPTCHA если включена
    if (props.recaptcha?.enabled && props.recaptcha?.site_key) {
        try {
            await initRecaptcha(props.recaptcha.site_key);
            recaptchaInitialized.value = true;
        } catch (error) {
            console.error('Failed to initialize reCAPTCHA:', error);
        }
    }
    
    try {
        // Выполняем reCAPTCHA проверку при загрузке, если включена
        if (props.recaptcha?.enabled && recaptchaInitialized.value) {
            try {
                await executeAction('login_page');
            } catch (recaptchaError) {
                console.error('reCAPTCHA check failed:', recaptchaError);
            }
        }
        
        await checkAuth();
    } catch (error) {
        console.error('Auth check failed:', error);
        loadingText.value = 'Ошибка проверки сессии';
    }
});
</script>

<template>
    <div class="login-page">
        <Head title="Вход в систему" />

        <!-- Основной контейнер -->
        <div class="main-container">
            <!-- Заголовок -->
            <h1 class="main-title">{{ loadingText }}</h1>

            <!-- Спиннер -->
            <div class="spinner-container">
                <div class="spinner"></div>
            </div>

            <!-- Информация внизу -->
            <div class="info-section">
                <!-- Кнопка поддержки -->
                <button @click="contactSupport" class="support-button">
                    Поддержка
                </button>

                <!-- Текст под кнопкой поддержки -->
                <div class="support-text">
                    Если застряли на этой странице, напишите в нашу службу поддержки
                </div>

                <!-- Системная информация -->
                <div class="system-info">
                    <div class="info-item">
                        <span class="info-label">IP:</span>
                        <span class="info-value">{{ userIP }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-page {
    min-height: 100vh;
    background: #2d3748;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.main-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 80vh;
    width: 100%;
    max-width: 600px;
    position: relative;
}

.main-title {
    color: white;
    font-size: 52px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 60px;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.spinner-container {
    margin-bottom: 80px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.info-section {
    position: absolute;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    width: 100%;
    max-width: 400px;
}

.support-button {
    background: none;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 400;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
}

.support-button:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.3);
}

.support-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 13px;
    text-align: center;
    line-height: 1.4;
    max-width: 320px;
}

.system-info {
    display: flex;
    gap: 30px;
    align-items: center;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.info-label {
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
}

.info-value {
    color: rgba(255, 255, 255, 0.9);
    font-family: 'Monaco', 'Menlo', monospace;
}

/* Адаптивность */
@media (max-width: 640px) {
    .main-title {
        font-size: 42px;
        margin-bottom: 40px;
    }
    
    .system-info {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .info-section {
        bottom: 20px;
        gap: 16px;
    }
    
    .support-text {
        font-size: 12px;
        max-width: 280px;
    }
}

@media (max-width: 480px) {
    .main-title {
        font-size: 38px;
    }
    
    .login-page {
        padding: 16px;
    }
    
    .support-text {
        font-size: 11px;
        max-width: 260px;
    }
}
</style>
