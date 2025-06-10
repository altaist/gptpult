import axios from 'axios';
import { ref, computed } from 'vue';

// Базовая конфигурация axios
const api = axios.create({
    baseURL: process.env.VUE_APP_API_URL || '/api',
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    // Добавляем поддержку сессионных куки
    withCredentials: true
});

// Состояние загрузки для каждого запроса
const loadingStates = ref(new Map());

// Текущий токен авторизации
const authToken = ref(localStorage.getItem('auth_token'));

// Флаг использования токенов
const useTokenAuth = ref(false);

// Установка режима авторизации
export const setAuthMode = (useToken = false) => {
    useTokenAuth.value = useToken;
    if (!useToken) {
        removeAuthToken();
    }
};

// Обновление токена
export const setAuthToken = (token) => {
    if (!useTokenAuth.value) return;
    
    authToken.value = token;
    localStorage.setItem('auth_token', token);
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
};

// Удаление токена
export const removeAuthToken = () => {
    authToken.value = null;
    localStorage.removeItem('auth_token');
    delete api.defaults.headers.common['Authorization'];
};

// Интерцептор для добавления токена
api.interceptors.request.use(config => {
    // Добавляем токен только если включен режим токенов
    if (useTokenAuth.value && authToken.value) {
        config.headers.Authorization = `Bearer ${authToken.value}`;
    }
    return config;
});

// Интерцептор для обработки ошибок
api.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 401) {
            if (useTokenAuth.value) {
                removeAuthToken();
            }
            // Здесь можно добавить редирект на страницу логина
        }
        return Promise.reject(error);
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

// Методы для работы с API
export const apiClient = {
    get: (url, params = {}, useToken = false) => request({
        method: 'get',
        url,
        params,
        withCredentials: !useToken
    }),

    post: (url, data = {}, useToken = false) => request({
        method: 'post',
        url,
        data,
        withCredentials: !useToken
    }),

    put: (url, data = {}, useToken = false) => request({
        method: 'put',
        url,
        data,
        withCredentials: !useToken
    }),

    delete: (url, data = {}, useToken = false) => request({
        method: 'delete',
        url,
        data,
        withCredentials: !useToken
    }),

    // Метод для загрузки файлов
    upload: (url, file, onProgress, useToken = false) => {
        const formData = new FormData();
        formData.append('file', file);

        return request({
            method: 'post',
            url,
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: onProgress,
            withCredentials: !useToken
        });
    }
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
    setAuthToken,
    removeAuthToken,
    setAuthMode,
    useLaravelErrors
}; 