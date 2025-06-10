import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { apiClient } from '@/utils/api';
import { getStoredUser, loadFromLocalStorage, saveToLocalStorage } from '@/utils/localstorage';

export const user = ref(null);
export const isAuthenticated = computed(() => !!user.value);

// Основная функция авторизации
export const authAndAutoReg = async () => {
    return authLocalSaved(true);
}

// Функция проверки и восстановления авторизации
export const authLocalSaved = async (autoreg = false) => {
    // 1. Проверка сессии через Inertia
    const userFromInertia = usePage().props.auth.user;
    if (userFromInertia) {
        return setUser(userFromInertia);
    }

    // 2. Проверка токена в localStorage
    let token = loadFromLocalStorage('auth_token');
    if (token) {
        try {
            const response = await apiClient.post(route('login.auto'), { auth_token: token });
            if (response.user) {
                response.user.token = token;
                return setUser(response.user);
            }
        } catch (error) {
            console.error('Auto login failed:', error);
            // Если автологин не удался, удаляем невалидный токен
            localStorage.removeItem('auth_token');
            token = null;
        }
    }

    // 3. Если нет токена, создаем новый
    if (!token) {
        token = createUserToken();
        saveToLocalStorage('auth_token', token);
    }

    // 4. Автоматическая регистрация если нужно
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

            const response = await apiClient.post(route('register.auto'), {
                auth_token: token,
                name: twaUser?.name || 'Guest',
                data
            });

            if (response.user) {
                return setUser(response.user);
            }
        } catch (error) {
            console.error('Auto registration failed:', error);
        }
    }

    return null;
}

// Выход из системы
export const logout = async (removeToken = false) => {
    try {
        await apiClient.post(route('logout'));
    } finally {
        logoutLocal(removeToken);
    }
}

// Установка пользователя
const setUser = (u) => {
    user.value = u;
    saveToLocalStorage('user', u);
}

// Локальный выход
const logoutLocal = (removeToken = false) => {
    user.value = null;
    localStorage.removeItem('user');
    if (removeToken) {
        localStorage.removeItem('auth_token');
    }
}

// Создание токена пользователя
const createUserToken = () => {
    const twaUser = getTwaUser();
    if (twaUser?.user?.id) {
        return `${Date.now()}_${twaUser.user.id}`;
    }
    return `${Date.now()}_${Math.random().toString(36).substring(2, 15)}`;
}

// Получение данных пользователя TWA
export const getTwaUser = () => {
    const TWA = window.TWA;
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

