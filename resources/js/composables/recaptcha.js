import { ref, computed } from 'vue';

// Состояние reCAPTCHA
const isRecaptchaLoaded = ref(false);
const isRecaptchaReady = ref(false);
const recaptchaError = ref(null);
const siteKey = ref(null);

// Ссылка на объект grecaptcha
let grecaptcha = null;

/**
 * Загрузить скрипт reCAPTCHA
 */
const loadRecaptchaScript = async (recaptchaSiteKey) => {
    return new Promise((resolve, reject) => {
        // Если уже загружен, возвращаем успех
        if (window.grecaptcha && isRecaptchaLoaded.value) {
            grecaptcha = window.grecaptcha;
            resolve();
            return;
        }

        // Проверяем наличие site key
        if (!recaptchaSiteKey) {
            const error = 'reCAPTCHA site key not provided';
            recaptchaError.value = error;
            reject(new Error(error));
            return;
        }

        siteKey.value = recaptchaSiteKey;

        // Создаем callback для готовности reCAPTCHA
        window.onRecaptchaReady = () => {
            grecaptcha = window.grecaptcha;
            isRecaptchaLoaded.value = true;
            isRecaptchaReady.value = true;
            resolve();
        };

        // Загружаем скрипт
        const script = document.createElement('script');
        script.src = `https://www.google.com/recaptcha/api.js?render=${recaptchaSiteKey}&onload=onRecaptchaReady`;
        script.async = true;
        script.defer = true;
        
        script.onerror = () => {
            const error = 'Failed to load reCAPTCHA script';
            recaptchaError.value = error;
            reject(new Error(error));
        };

        // Добавляем скрипт на страницу
        document.head.appendChild(script);
    });
};

/**
 * Выполнить reCAPTCHA для действия
 */
const executeRecaptcha = async (action) => {
    if (!isRecaptchaReady.value || !grecaptcha) {
        throw new Error('reCAPTCHA не готова');
    }

    if (!siteKey.value) {
        throw new Error('reCAPTCHA site key не установлен');
    }

    try {
        const token = await grecaptcha.execute(siteKey.value, { action });
        
        if (!token) {
            throw new Error('Не удалось получить токен reCAPTCHA');
        }

        return token;
    } catch (error) {
        console.error('reCAPTCHA execution failed:', error);
        recaptchaError.value = error.message;
        throw error;
    }
};

/**
 * Композабл для использования reCAPTCHA
 */
export function useRecaptcha() {
    // Инициализация reCAPTCHA
    const initRecaptcha = async (recaptchaSiteKey) => {
        try {
            recaptchaError.value = null;
            await loadRecaptchaScript(recaptchaSiteKey);
        } catch (error) {
            console.error('Failed to initialize reCAPTCHA:', error);
            recaptchaError.value = error.message;
            throw error;
        }
    };

    // Выполнить действие с reCAPTCHA
    const executeAction = async (action) => {
        try {
            const token = await executeRecaptcha(action);
            return token;
        } catch (error) {
            console.error(`reCAPTCHA action "${action}" failed:`, error);
            throw error;
        }
    };

    // Проверить готовность
    const isReady = computed(() => isRecaptchaReady.value && !recaptchaError.value);

    // Получить ошибку
    const getError = computed(() => recaptchaError.value);

    // Очистить ошибку
    const clearError = () => {
        recaptchaError.value = null;
    };

    return {
        // Состояние
        isRecaptchaLoaded: computed(() => isRecaptchaLoaded.value),
        isRecaptchaReady: computed(() => isRecaptchaReady.value),
        isReady,
        getError,
        
        // Методы
        initRecaptcha,
        executeAction,
        clearError
    };
} 