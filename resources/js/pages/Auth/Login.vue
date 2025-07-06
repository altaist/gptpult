<script setup>
import GuestLayout from '@/_breeze/Layouts/GuestLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { checkAuth } from '@/composables/auth';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// Состояния
const isLoading = ref(true);
const showSupport = ref(false);
const loadingText = ref('Проверяем авторизацию...');
const supportTimer = ref(null);

// Различные тексты загрузки
const loadingTexts = [
    'Проверяем авторизацию...',
    'Подключаемся к серверу...',
    'Инициализируем сессию...',
    'Почти готово...'
];

let textIndex = 0;

const goToDashboard = () => {
    router.visit(route('dashboard'));
};

const showSupportInfo = () => {
    showSupport.value = true;
    loadingText.value = 'Возникли проблемы с загрузкой?';
};

const contactSupport = () => {
    // Открываем Telegram бот поддержки
    window.open('https://t.me/gptpult_bot', '_blank');
};

const tryManualLogin = () => {
    // Показываем стандартную форму логина
    isLoading.value = false;
    showSupport.value = false;
    clearTimeout(supportTimer.value);
};

onMounted(async () => {
    // Устанавливаем таймер для показа поддержки через 15 секунд
    supportTimer.value = setTimeout(showSupportInfo, 15000);
    
    // Меняем текст загрузки каждые 3 секунды
    const textInterval = setInterval(() => {
        if (!showSupport.value && isLoading.value) {
            textIndex = (textIndex + 1) % loadingTexts.length;
            loadingText.value = loadingTexts[textIndex];
        } else {
            clearInterval(textInterval);
        }
    }, 3000);
    
    try {
        await checkAuth();
    } catch (error) {
        console.error('Auth check failed:', error);
        // Если авторизация не удалась, показываем поддержку
        showSupportInfo();
    }
});
</script>

<template>
    <GuestLayout>
        <Head title="Вход в систему" />

        <!-- Экран загрузки -->
        <div v-if="isLoading" class="loading-container">
            <!-- Логотип или иконка -->
            <div class="loading-logo">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
            </div>

            <!-- Текст загрузки -->
            <div class="loading-text">
                {{ loadingText }}
            </div>

            <!-- Блок поддержки (показывается через 15 секунд) -->
            <div v-if="showSupport" class="support-block">
                <div class="support-message">
                    Загрузка занимает больше времени, чем обычно
                </div>
                
                <div class="support-buttons">
                    <button @click="contactSupport" class="support-btn primary">
                        <i class="fab fa-telegram"></i>
                        Связаться с поддержкой
                    </button>
                    
                    <button @click="tryManualLogin" class="support-btn secondary">
                        Войти вручную
                    </button>
                </div>

                <div class="support-note">
                    Или попробуйте обновить страницу
                </div>
            </div>

            <!-- Кнопка перехода на главную (всегда видна) -->
            <div class="navigation-block">
                <button @click="goToDashboard" class="nav-btn">
                    Перейти на главную
                </button>
            </div>
        </div>

        <!-- Стандартная форма логина (показывается только при ручном выборе) -->
        <div v-else class="manual-login-form">
            <div class="form-header">
                <h2>Вход в систему</h2>
                <p>Введите ваши данные для входа</p>
            </div>

            <form @submit.prevent="() => {}">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        class="form-input"
                        placeholder="example@mail.ru"
                        required
                    />
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input
                        id="password"
                        type="password"
                        class="form-input"
                        placeholder="Введите пароль"
                        required
                    />
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        Войти
                    </button>
                </div>

                <div class="form-footer">
                    <button @click="isLoading = true; showSupport = false" type="button" class="back-btn">
                        ← Вернуться к автоматическому входу
                    </button>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    text-align: center;
    padding: 40px 20px;
}

.loading-logo {
    margin-bottom: 32px;
}

.spinner {
    position: relative;
    width: 80px;
    height: 80px;
}

.spinner-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1.5s linear infinite;
}

.spinner-ring:nth-child(2) {
    animation-delay: 0.15s;
    border-top-color: #8b5cf6;
}

.spinner-ring:nth-child(3) {
    animation-delay: 0.3s;
    border-top-color: #06b6d4;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    font-size: 18px;
    color: #374151;
    margin-bottom: 40px;
    font-weight: 500;
}

.support-block {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
    max-width: 400px;
    width: 100%;
}

.support-message {
    color: #6b7280;
    margin-bottom: 20px;
    font-size: 16px;
}

.support-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.support-btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.support-btn.primary {
    background: #229954;
    color: white;
}

.support-btn.primary:hover {
    background: #1e8449;
    transform: translateY(-1px);
}

.support-btn.secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.support-btn.secondary:hover {
    background: #e5e7eb;
}

.support-note {
    font-size: 13px;
    color: #9ca3af;
    text-align: center;
}

.navigation-block {
    margin-top: 20px;
}

.nav-btn {
    background: none;
    border: none;
    color: #6b7280;
    font-size: 14px;
    cursor: pointer;
    text-decoration: underline;
    padding: 8px 16px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.nav-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Стили для ручной формы логина */
.manual-login-form {
    max-width: 400px;
    margin: 0 auto;
    padding: 20px;
}

.form-header {
    text-align: center;
    margin-bottom: 32px;
}

.form-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}

.form-header p {
    color: #6b7280;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
    margin-bottom: 20px;
}

.submit-btn {
    width: 100%;
    background: #3b82f6;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s ease;
}

.submit-btn:hover {
    background: #2563eb;
}

.form-footer {
    text-align: center;
}

.back-btn {
    background: none;
    border: none;
    color: #6b7280;
    font-size: 14px;
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.back-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Адаптивность */
@media (max-width: 640px) {
    .loading-container {
        padding: 20px 16px;
    }
    
    .support-buttons {
        flex-direction: column;
    }
    
    .manual-login-form {
        padding: 16px;
    }
}
</style>
