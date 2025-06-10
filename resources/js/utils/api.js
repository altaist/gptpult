import axios from 'axios';
import { ref, computed } from 'vue';

// Создаем экземпляр axios с базовыми настройками
const api = axios.create({
    baseURL: import.meta.env.VITE_APP_API_URL || '',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    withCredentials: true
});

// Состояние загрузки для каждого запроса
const loadingStates = ref(new Map());

// Перехватчик ответов
api.interceptors.response.use(
    response => response.data,
    error => {
        const response = error.response;
        
        // Если нет ответа от сервера
        if (!response) {
            throw {
                message: 'Нет соединения с сервером',
                original: error
            };
        }

        // Обработка ошибок авторизации
        if (response.status === 401) {
            throw {
                message: response.data.message || 'Требуется авторизация',
                original: error,
                status: 401
            };
        }

        // Обработка ошибок валидации
        if (response.status === 422) {
            throw {
                message: 'Ошибка валидации',
                errors: response.data.errors,
                original: error
            };
        }

        // Обработка ошибок сервера
        if (response.status >= 500) {
            throw {
                message: 'Ошибка сервера',
                original: error
            };
        }

        // Обработка остальных ошибок
        throw {
            message: response.data.message || 'Произошла ошибка',
            original: error
        };
    }
);

// Класс для работы с ошибками API
export class ApiError extends Error {
    constructor(message, status, data) {
        super(message);
        this.status = status;
        this.data = data;
    }
}

// Функция для создания уникального ID запроса
const createRequestId = () => Math.random().toString(36).substring(7);

// Основная функция для выполнения запросов
export const request = async (config) => {
    const requestId = createRequestId();
    loadingStates.value.set(requestId, true);

    try {
        const response = await api(config);
        return response.data;
    } catch (error) {
        if (error.response) {
            throw new ApiError(
                error.response.data.message || 'Ошибка сервера',
                error.response.status,
                error.response.data
            );
        }
        throw new ApiError('Ошибка сети', 0, null);
    } finally {
        loadingStates.value.delete(requestId);
    }
};

// Проверка наличия активных запросов
export const isLoading = computed(() => loadingStates.value.size > 0);

// Создаем объект с методами для работы с API
export const apiClient = {
    get: (url, params = {}) => api.get(url, { params }),
    post: (url, data = {}) => api.post(url, data),
    put: (url, data = {}) => api.put(url, data),
    delete: (url) => api.delete(url),
    patch: (url, data = {}) => api.patch(url, data)
};

// Хук для работы с ошибками Laravel
export const useLaravelErrors = (error) => {
    const errors = ref(error?.response?.data?.errors || {});

    const getError = (field, index = 0) => {
        const fieldErrors = errors.value[field];
        if (!Array.isArray(fieldErrors) || index < 0 || index >= fieldErrors.length) {
            return null;
        }
        return fieldErrors[index];
    };

    const hasError = (field) => {
        return !!errors.value[field];
    };

    const getAllErrors = () => {
        return errors.value;
    };

    return {
        errors,
        getError,
        hasError,
        getAllErrors
    };
};

// Экспорт всех необходимых функций и объектов
export default {
    apiClient,
    isLoading,
    useLaravelErrors
}; 