import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { apiClient } from './api';
import { getStoredUser, loadFromLocalStorage, saveToLocalStorage } from '@/utils/localstorage';

export const user = ref(null);
export const isAuthenticated = computed(() => !!user.value);
let justLoggedOut = false;

// Основная функция авторизации
export const authAndAutoReg = async () => {
    return authLocalSaved(true);
}

// Функция проверки и восстановления авторизации
export const authLocalSaved = async (autoreg = false) => {
    // Если только что вышли, не пытаемся авторизоваться
    if (justLoggedOut) {
        justLoggedOut = false;
        return null;
    }

    // 1. Проверка сессии через Inertia
    const userFromInertia = usePage().props.auth.user;
    if (userFromInertia) {
        return setUser(userFromInertia);
    }

    // 2. Проверка токена в localStorage
    const token = loadFromLocalStorage('auto_auth_token');
    
    // 3. Если есть токен, пробуем авторизоваться
    if (token) {
        try {
            const response = await apiClient.post(route('login.auto'), { auth_token: token });
            if (response && response.user) {
                response.user.token = token;
                return setUser(response.user);
            }
        } catch (error) {
            console.error('Login error:', error);
            // Если токен неверный, удаляем его
            if (error.status === 401) {
                localStorage.removeItem('auto_auth_token');
            }
        }
    }

    // 4. Если нет токена или авторизация не удалась, пробуем зарегистрироваться
    if (autoreg) {
        try {
            const twaUser = getTwaUser();
            const data = twaUser ? {
                telegram: {
                    id: twaUser.tgId,
                    username: twaUser.name,
                    data: twaUser.data
                }
            } : {};

            // Создаем временный токен для регистрации
            const tempToken = `${Date.now()}_${Math.random().toString(36).substring(2, 15)}`;

            const response = await apiClient.post(route('register.auto'), {
                auth_token: tempToken,
                name: twaUser?.name || 'Guest',
                data
            });

            if (response && response.user) {
                // Сохраняем токен, полученный от сервера
                if (response.user.auth_token) {
                    saveToLocalStorage('auto_auth_token', response.user.auth_token);
                }
                return setUser(response.user);
            }
        } catch (error) {
            console.error('Registration error:', error);
        }
    }

    return null;
}

// Выход из системы
export const logout = async () => {
    try {
        await apiClient.post(route('logout'));
    } finally {
        logoutLocal();
        justLoggedOut = true; // Устанавливаем флаг выхода
    }
}

// Установка пользователя
const setUser = (u) => {
    user.value = u;
    saveToLocalStorage('user', u);
}

// Локальный выход
const logoutLocal = () => {
    user.value = null;
    localStorage.removeItem('user');
}

// Получение данных пользователя TWA
export const getTwaUser = () => {
    const TWA = window.Telegram?.WebApp;
    if (!TWA?.initDataUnsafe?.user) return null;
    
    const user = TWA.initDataUnsafe.user;
    return {
        tgId: user.id || null,
        name: user.username || 'Guest',
        data: TWA.initDataUnsafe
    };
}

// Проверка авторизации при загрузке страницы
export const checkAuth = async () => {
    return await authAndAutoReg();
}

