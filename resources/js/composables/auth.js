import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { apiClient } from './api';
import { getStoredUser, loadFromLocalStorage, saveToLocalStorage } from '@/utils/localstorage';

export const user = ref(null);
export const isAuthenticated = computed(() => !!user.value);
export const errorMessage = ref('');
let justLoggedOut = false;

// Флаг для предотвращения множественных редиректов
let isRedirectingAuth = false

// Инициализация: загружаем пользователя из localStorage
const initUser = () => {
    const storedUser = getStoredUser();
    if (storedUser) {
        user.value = storedUser;
    }
    
    // Слушаем глобальное событие unauthorized
    window.addEventListener('auth:unauthorized', (event) => {
        console.log('Received auth:unauthorized event, clearing state', event.detail);
        
        // Проверяем, есть ли данные Telegram WebApp для создания нового аккаунта
        const hasTelegramData = window.Telegram?.WebApp?.initDataUnsafe?.user;
        
        if (hasTelegramData) {
            console.log('Telegram WebApp data available, attempting to create account instead of redirecting');
            
            // Небольшая задержка для очистки состояния
            setTimeout(async () => {
                if (!isRedirectingAuth) {
                    try {
                        clearAuthState(); // Очищаем только после попытки создания аккаунта
                        
                        // Пытаемся создать новый аккаунт через authLocalSaved
                        const authResult = await authLocalSaved(true);
                        if (authResult) {
                            console.log('New Telegram account created after 401 error, redirecting to /lk');
                            isRedirectingAuth = true;
                            window.location.href = '/lk';
                            return;
                        }
                        
                        // Если создание аккаунта не удалось, пытаемся отправить данные напрямую
                        if (window.Telegram?.WebApp?.initData) {
                            console.log('Trying direct Telegram auth after account creation failed');
                            
                            const headers = {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'X-Telegram-Init-Data': window.Telegram.WebApp.initData,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            };
                            
                            const response = await fetch('/telegram/auth', {
                                method: 'POST',
                                headers,
                                credentials: 'include',
                                body: JSON.stringify({
                                    init_data: window.Telegram.WebApp.initData
                                })
                            });
                            
                            if (response.ok) {
                                const data = await response.json();
                                if (data.success && data.user) {
                                    console.log('Direct Telegram auth successful, redirecting to /lk');
                                    isRedirectingAuth = true;
                                    window.location.href = '/lk';
                                    return;
                                }
                            }
                        }
                        
                        console.warn('All Telegram auth methods failed, staying on current page');
                    } catch (error) {
                        console.warn('Failed to handle Telegram auth after 401:', error);
                    }
                }
            }, 200);
        } else {
            // Нет данных Telegram - обычная очистка состояния
            clearAuthState();
            
            // Если мы НЕ на странице логина, перенаправляем туда
            if (window.location.pathname !== '/login') {
                setTimeout(() => {
                    if (!isRedirectingAuth) {
                        isRedirectingAuth = true;
                        window.location.href = '/login';
                    }
                }, 100);
            }
        }
    });
};
initUser();

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
        setUser(userFromInertia);
        return userFromInertia;
    }

    // 2. Проверка токена в localStorage
    const token = loadFromLocalStorage('auto_auth_token');
    
    // 3. Если есть токен, пробуем авторизоваться
    if (token) {
        try {
            const response = await apiClient.post(route('login.auto'), { auth_token: token });
            if (response && response.user) {
                response.user.token = token;
                setUser(response.user);
                return response.user;
            }
        } catch (error) {
            // Если токен неверный, удаляем его и очищаем состояние
            if (error.status === 401) {
                console.log('Auto auth token is invalid, clearing auth state');
                clearAuthState();
                // НЕ возвращаем ошибку, продолжаем попытку регистрации
            } else {
                console.warn('Auto auth failed with non-401 error:', error);
            }
        }
    }

    // 4. Если нет токена или авторизация не удалась, пробуем зарегистрироваться
    if (autoreg) {
        try {
            const twaUser = getTwaUser();
            
            // Если есть данные Telegram WebApp, отправляем их для создания связанного аккаунта
            if (twaUser && window.Telegram?.WebApp?.initData) {
                console.log('Creating account with Telegram WebApp data');
                
                // Отправляем данные напрямую на Telegram auth endpoint
                const headers = {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Telegram-Init-Data': window.Telegram.WebApp.initData,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                };
                
                const telegramResponse = await fetch('/telegram/auth', {
                    method: 'POST',
                    headers,
                    credentials: 'include',
                    body: JSON.stringify({
                        init_data: window.Telegram.WebApp.initData
                    })
                });
                
                if (telegramResponse.ok) {
                    const telegramData = await telegramResponse.json();
                    if (telegramData.success && telegramData.user) {
                        console.log('Successfully created Telegram linked account');
                        setUser(telegramData.user);
                        
                        // Сохраняем информацию об авторизации
                        if (telegramData.user.auth_token) {
                            saveToLocalStorage('auto_auth_token', telegramData.user.auth_token);
                        }
                        localStorage.setItem('telegram_auth_user_id', telegramData.user.id);
                        localStorage.setItem('telegram_auth_timestamp', Date.now());
                        
                        return telegramData.user;
                    }
                }
                
                console.log('Telegram auth endpoint failed, trying fallback registration');
            }
            
            // Fallback: обычная автоматическая регистрация
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
                setUser(response.user);
                return response.user;
            }
        } catch (error) {
            console.log('Registration failed:', error.message || error);
            // console.error('Registration error:', error);  // Закомментировано для продакшена
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

// Полный выход из системы с очисткой всех данных
export const fullLogout = async () => {
    try {
        await apiClient.post(route('logout'));
    } finally {
        fullLogoutLocal();
        justLoggedOut = true; // Устанавливаем флаг выхода
    }
}

// Установка пользователя
const setUser = (u) => {
    user.value = u;
    saveToLocalStorage('user', u);
    return u;
}

// Локальный выход
const logoutLocal = () => {
    user.value = null;
    localStorage.removeItem('user');
}

// Полная локальная очистка
const fullLogoutLocal = () => {
    user.value = null;
    
    // Очищаем все данные аутентификации
    localStorage.removeItem('user');
    localStorage.removeItem('auto_auth_token');
    localStorage.removeItem('telegram_auth_user_id');
    localStorage.removeItem('telegram_auth_timestamp');
    
    // Очищаем данные магазина
    localStorage.removeItem('shop.main');
    
    // Очищаем настройки и языки
    localStorage.removeItem('settings');
    localStorage.removeItem('lang');
    localStorage.removeItem('locale');
    
    // Очищаем все куки Telegram
    document.cookie.split(';').forEach(cookie => {
        const trimmed = cookie.trim();
        if (trimmed.startsWith('telegram_auth_user_')) {
            const cookieName = trimmed.split('=')[0];
            // Удаляем куки установив их в прошедшее время
            document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
        }
    });
    
    // Очищаем сессию
    try {
        if (window.sessionStorage) {
            window.sessionStorage.clear();
        }
    } catch (e) {
        // Игнорируем ошибки sessionStorage
    }
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

// Определить, нужно ли показывать кнопку выхода (с дополнительными данными)
export const shouldShowLogoutButtonWithData = (documentsCount = 0, balance = 0) => {
    if (!isAuthenticated.value) {
        return false;
    }
    
    // 1. Если есть хотя бы 1 документ
    if (documentsCount > 0) {
        return true;
    }
    
    // 2. Если баланс пополнен (больше 0)
    if (balance > 0) {
        return true;
    }
    
    // 3. Проверяем данные пользователя из БД
    const currentUser = user.value;
    if (currentUser) {
        // 3.1. Если email НЕ является автогенерированным
        if (currentUser.email && !currentUser.email.endsWith('@auto.user') && !currentUser.email.endsWith('@linked.user')) {
            return true;
        }
        
        // 3.2. Если есть данные о согласии на обработку персональных данных
        if (currentUser.privacy_consent) {
            return true;
        }
        
        // 3.3. Если пользователь связан с Telegram (имеет telegram_id в БД)
        if (currentUser.telegram_id) {
            return true;
        }
        
        // 3.4. Если пользователь связан с Telegram (имеет telegram_username в БД)
        if (currentUser.telegram_username) {
            return true;
        }
        
        // 3.5. Если пользователь связан с Telegram (дата связывания в БД)
        if (currentUser.telegram_linked_at) {
            return true;
        }
        
        // 3.6. Если аккаунт существует более 1 часа (проверяем created_at из БД)
        if (currentUser.created_at) {
            const createdAt = new Date(currentUser.created_at);
            const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
            if (createdAt < oneHourAgo) {
                return true;
            }
        }
        
        // 3.7. Если в person есть данные о том, что аккаунт не автосозданный
        if (currentUser.person && currentUser.person.telegram && currentUser.person.telegram.auto_created === false) {
            return true;
        }
        
        // 3.8. Если пользователь имеет настройки или статистику в БД (значит взаимодействовал с системой)
        /*if (currentUser.settings && Object.keys(currentUser.settings).length > 0) {
            return true;
        }
        
        if (currentUser.statistics && Object.keys(currentUser.statistics).length > 0) {
            return true;
        }
        
        // 3.9. Если роль пользователя не обычная (например, админ, модератор)
        if (currentUser.role_id && currentUser.role_id > 0) {
            return true;
        }
        
        // 3.10. Если у пользователя есть auth_token в БД (значит создавал документы)
        if (currentUser.auth_token) {
            return true;
        }*/
    }
    
    // 4. FALLBACK: Проверяем localStorage только если данные пользователя недостаточны
    
    // 4.1. Проверяем авторизацию через Telegram в localStorage (fallback)
    const telegramUserId = localStorage.getItem('telegram_auth_user_id');
    if (telegramUserId) {
        return true;
    }
    
    // 4.2. Проверяем куки Telegram (fallback)
    if (document.cookie.includes('telegram_auth_user_')) {
        return true;
    }
    
    // 4.3. Проверяем URL параметры для определения типа входа (fallback)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('tgWebAppStartParam') || urlParams.has('telegram')) {
        // Пользователь пришел через Telegram WebApp
        return true;
    }
    
    // Если ни один из критериев не сработал - это "пустой" аккаунт
    return false;
}

// Определить, нужно ли показывать кнопку выхода
export const shouldShowLogoutButton = () => {
    return shouldShowLogoutButtonWithData(0, user.value?.balance || 0);
}

// Проверка авторизации при загрузке страницы
export const checkAuth = async () => {
    // Если уже происходит редирект, не выполняем повторную проверку
    if (isRedirectingAuth) {
        return false
    }

    // console.log('checkAuth: Starting authentication check...')  // Закомментировано для продакшена
    
    try {
        // ПРИОРИТЕТ 1: Если в Telegram WebApp и есть данные пользователя - обрабатываем их ПЕРВЫМИ
        if (window.Telegram?.WebApp?.initDataUnsafe?.user) {
            console.log('checkAuth: Telegram WebApp detected with user data, processing priority authentication');
            
            const initData = window.Telegram.WebApp.initData;
            
            if (initData) {
                try {
                    console.log('checkAuth: Sending Telegram data to server for priority processing');
                    
                    const headers = {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'X-Telegram-Init-Data': initData,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    };
                    
                    const response = await fetch('/telegram/auth', {
                        method: 'POST',
                        headers,
                        credentials: 'include',
                        body: JSON.stringify({
                            init_data: initData
                        })
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.user) {
                            console.log('checkAuth: Telegram WebApp authentication successful');
                            setUser(data.user);
                            
                            // Сохраняем данные авторизации
                            if (data.user.auth_token) {
                                saveToLocalStorage('auto_auth_token', data.user.auth_token);
                            }
                            localStorage.setItem('telegram_auth_user_id', data.user.id);
                            localStorage.setItem('telegram_auth_timestamp', Date.now());
                            
                            // Если на странице логина, перенаправляем в ЛК
                            if (window.location.pathname === '/login' && !isRedirectingAuth) {
                                console.log('checkAuth: Redirecting Telegram user from login to /lk');
                                isRedirectingAuth = true;
                                window.location.href = '/lk';
                                return true;
                            }
                            
                            return true;
                        }
                    }
                    
                    console.log('checkAuth: Telegram auth endpoint response not successful, continuing with other methods');
                } catch (error) {
                    console.warn('checkAuth: Telegram WebApp authentication failed:', error);
                    // Продолжаем с другими методами авторизации
                }
            }
        }
        
        // ПРИОРИТЕТ 2: Проверяем есть ли уже пользователь через Inertia
        if (isAuthenticated.value) {
            // Дополнительная проверка активности сессии
            const inertiaValid = checkInertiaAuth();
            
            if (!inertiaValid) {
                // Если Inertia props пусты, проверяем сессию через API
                try {
                    const sessionValid = await checkSessionStatus();
                    
                    if (sessionValid === false) {
                        // Сессия точно неактивна - пытаемся создать новый аккаунт
                        console.log('Session is inactive, trying to create new account');
                        
                        // Если на странице логина, пытаемся автозарегистрироваться
                        if (window.location.pathname === '/login') {
                            try {
                                const authResult = await authLocalSaved(true);
                                if (authResult) {
                                    console.log('New account created successfully, redirecting to /lk');
                                    isRedirectingAuth = true;
                                    window.location.href = '/lk';
                                    return true;
                                }
                            } catch (error) {
                                console.warn('Failed to create new account:', error);
                            }
                        }
                        
                        return false;
                    }
                    
                    if (sessionValid === null) {
                        // Неопределенное состояние (ошибка сети и т.д.)
                        // Остаемся на текущей странице
                        console.log('Session status unclear, staying on current page');
                        return false;
                    }
                } catch (error) {
                    // При ошибке API не делаем редирект
                    console.warn('Session check failed:', error);
                    // Не очищаем состояние если есть данные Telegram
                    if (!window.Telegram?.WebApp?.initDataUnsafe?.user) {
                        clearAuthState();
                    }
                    return false;
                }
            }
            
            // console.log('checkAuth: User already authenticated via Inertia')  // Закомментировано для продакшена
            
            // Если пользователь на странице входа, но уже авторизован - редиректим
            if (window.location.pathname === '/login' && !isRedirectingAuth) {
                // console.log('checkAuth: User is on login page but authenticated, redirecting to /lk')  // Закомментировано для продакшена
                isRedirectingAuth = true
                window.location.href = '/lk';
                return true;
            }
            
            return true;
        }
        
        // ПРИОРИТЕТ 3: Пытаемся восстановить авторизацию из localStorage или автозарегистрироваться только на странице логина
        if (window.location.pathname === '/login' && !isRedirectingAuth) {
            const authResult = await authLocalSaved(true);
            if (authResult) {
                // console.log('checkAuth: User authenticated via localStorage or auto-registered')  // Закомментировано для продакшена
                
                // Редиректим в ЛК
                // console.log('checkAuth: Redirecting restored user from login page')  // Закомментировано для продакшена
                isRedirectingAuth = true
                window.location.href = '/lk';
                return true;
            }
        }
        
        const result = isAuthenticated.value;
        // console.log('checkAuth: Authentication result:', !!result)  // Закомментировано для продакшена
        
        // Если пользователь авторизован и на странице логина
        if (result && window.location.pathname === '/login' && !isRedirectingAuth) {
            // console.log('checkAuth: Final check - redirecting authenticated user from login page')  // Закомментировано для продакшена
            isRedirectingAuth = true
            window.location.href = '/lk';
        }
        
        return !!result;
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Ошибка входа';
        // console.error('Login error:', error);  // Закомментировано для продакшена
        throw error;
    }
}

// Функция для отладки - показывает какие критерии активности пользователя срабатывают
export const debugLogoutButtonCriteria = (documentsCount = 0, balance = 0) => {
    if (!isAuthenticated.value) {
        return { shouldShow: false, reason: 'Пользователь не авторизован' };
    }
    
    const criteria = [];
    
    // 1. Документы
    if (documentsCount > 0) {
        criteria.push(`Есть документы: ${documentsCount}`);
        return { shouldShow: true, reason: 'Есть документы', criteria };
    }
    
    // 2. Баланс
    if (balance > 0) {
        criteria.push(`Баланс пополнен: ${balance}`);
        return { shouldShow: true, reason: 'Баланс пополнен', criteria };
    }
    
    // 3. Telegram авторизация
    const telegramUserId = localStorage.getItem('telegram_auth_user_id');
    if (telegramUserId) {
        criteria.push(`Telegram авторизация: ${telegramUserId}`);
        return { shouldShow: true, reason: 'Telegram авторизация', criteria };
    }
    
    // 4. Токен авторизации
    const authToken = localStorage.getItem('auto_auth_token');
    if (authToken) {
        criteria.push(`Есть токен авторизации: ${authToken.substring(0, 10)}...`);
        return { shouldShow: true, reason: 'Есть токен авторизации', criteria };
    }
    
    const currentUser = user.value;
    if (currentUser) {
        // 5.1. Email
        if (currentUser.email && !currentUser.email.endsWith('@auto.user') && !currentUser.email.endsWith('@linked.user')) {
            criteria.push(`Реальный email: ${currentUser.email}`);
            return { shouldShow: true, reason: 'Реальный email', criteria };
        } else if (currentUser.email) {
            criteria.push(`Автогенерированный email: ${currentUser.email}`);
        }
        
        // 5.2. Согласие на обработку данных
        if (currentUser.privacy_consent) {
            criteria.push(`Согласие на обработку данных: да`);
            return { shouldShow: true, reason: 'Согласие на обработку данных', criteria };
        }
        
        // 5.3. Telegram ID
        if (currentUser.telegram_id) {
            criteria.push(`Telegram ID: ${currentUser.telegram_id}`);
            return { shouldShow: true, reason: 'Связан с Telegram', criteria };
        }
        
        // 5.4. Дата связывания с Telegram
        if (currentUser.telegram_linked_at) {
            criteria.push(`Связан с Telegram: ${currentUser.telegram_linked_at}`);
            return { shouldShow: true, reason: 'Связан с Telegram', criteria };
        }
        
        // 5.5. Время создания
        if (currentUser.created_at) {
            const createdAt = new Date(currentUser.created_at);
            const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
            const ageMinutes = Math.floor((Date.now() - createdAt.getTime()) / (1000 * 60));
            criteria.push(`Возраст аккаунта: ${ageMinutes} минут`);
            if (createdAt < oneHourAgo) {
                return { shouldShow: true, reason: 'Аккаунт старше 1 часа', criteria };
            }
        }
        
        // 5.6. Автосозданный флаг
        if (currentUser.person?.telegram?.auto_created === false) {
            criteria.push(`Не автосозданный аккаунт`);
            return { shouldShow: true, reason: 'Не автосозданный аккаунт', criteria };
        } else if (currentUser.person?.telegram?.auto_created === true) {
            criteria.push(`Автосозданный аккаунт`);
        }
        
        // 5.7. Настройки и статистика
        if (currentUser.settings && Object.keys(currentUser.settings).length > 0) {
            criteria.push(`Есть настройки: ${Object.keys(currentUser.settings).length} ключей`);
            return { shouldShow: true, reason: 'Есть пользовательские настройки', criteria };
        }
        
        if (currentUser.statistics && Object.keys(currentUser.statistics).length > 0) {
            criteria.push(`Есть статистика: ${Object.keys(currentUser.statistics).length} ключей`);
            return { shouldShow: true, reason: 'Есть статистика', criteria };
        }
    }
    
    // 6. LocalStorage
    const shopData = localStorage.getItem('shop.main');
    if (shopData) {
        criteria.push(`Данные магазина в localStorage`);
        return { shouldShow: true, reason: 'Данные магазина', criteria };
    }
    
    const settings = localStorage.getItem('settings');
    if (settings) {
        criteria.push(`Настройки в localStorage`);
        return { shouldShow: true, reason: 'Сохраненные настройки', criteria };
    }
    
    const lang = localStorage.getItem('lang') || localStorage.getItem('locale');
    if (lang && lang !== 'ru') {
        criteria.push(`Выбран язык: ${lang}`);
        return { shouldShow: true, reason: 'Выбран не русский язык', criteria };
    } else if (lang) {
        criteria.push(`Язык по умолчанию: ${lang}`);
    }
    
    // 6.4. Куки Telegram
    if (document.cookie.includes('telegram_auth_user_')) {
        criteria.push(`Куки Telegram найдены`);
        return { shouldShow: true, reason: 'Куки Telegram', criteria };
    }
    
    // 7. URL параметры
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('tgWebAppStartParam') || urlParams.has('telegram')) {
        criteria.push(`Параметры Telegram WebApp в URL`);
        return { shouldShow: true, reason: 'Пришел через Telegram WebApp', criteria };
    }
    
    return { 
        shouldShow: false, 
        reason: 'Пустой аккаунт - все критерии не сработали',
        criteria 
    };
};

// Функция для полной очистки состояния авторизации
export const clearAuthState = () => {
    user.value = null;
    localStorage.removeItem('user');
    localStorage.removeItem('auto_auth_token');
    
    // Сбрасываем флаг редиректа
    isRedirectingAuth = false;
    
    console.log('Auth state cleared due to invalid session');
}

// Функция для проверки активности сессии
export const checkSessionStatus = async () => {
    try {
        // Делаем простой API запрос для проверки авторизации
        const response = await apiClient.get('/api/user');
        
        if (response && response.id) {
            // Сессия активна, обновляем данные пользователя
            setUser(response);
            return true;
        }
        
        return false;
    } catch (error) {
        // Если получили 401 - сессия неактивна
        if (error.status === 401) {
            // Проверяем, есть ли данные Telegram для создания нового аккаунта
            const hasTelegramData = window.Telegram?.WebApp?.initDataUnsafe?.user;
            
            if (!hasTelegramData) {
                // Только очищаем состояние если нет данных Telegram
                clearAuthState();
            } else {
                console.log('Session inactive but Telegram data available - not clearing state yet');
            }
            
            return false;
        }
        
        // Для других ошибок считаем что сессия может быть активна
        // (например, проблемы с сетью)
        console.warn('Session check failed with non-401 error:', error);
        return null; // неопределенное состояние
    }
}

// Функция для проверки состояния Inertia props
export const checkInertiaAuth = () => {
    try {
        const userFromInertia = usePage().props.auth?.user;
        
        if (userFromInertia && userFromInertia.id) {
            // Пользователь найден в Inertia props - сессия активна
            setUser(userFromInertia);
            return true;
        }
        
        // Пользователь не найден в Inertia props
        return false;
    } catch (error) {
        // Ошибка доступа к Inertia props
        console.error('Error accessing Inertia props:', error);
        return false;
    }
}

// Безопасная проверка авторизации перед редиректом
export const safeAuthCheck = async () => {
    try {
        // 1. Проверяем Inertia props
        const inertiaUser = usePage().props.auth?.user;
        if (inertiaUser && inertiaUser.id) {
            setUser(inertiaUser);
            return { isAuthenticated: true, source: 'inertia' };
        }
        
        // 2. Проверяем localStorage
        const localUser = getStoredUser();
        if (localUser && localUser.id) {
            // Валидируем через API запрос
            try {
                const sessionValid = await checkSessionStatus();
                if (sessionValid === true) {
                    return { isAuthenticated: true, source: 'localStorage+api' };
                }
                if (sessionValid === false) {
                    // Сессия неактивна - данные уже очищены в clearAuthState
                    return { isAuthenticated: false, source: 'session_expired' };
                }
            } catch (error) {
                console.warn('Session validation failed:', error);
                clearAuthState();
                return { isAuthenticated: false, source: 'validation_error' };
            }
        }
        
        // 3. Проверяем токен авторизации
        const token = loadFromLocalStorage('auto_auth_token');
        if (token) {
            try {
                const response = await apiClient.post(route('login.auto'), { auth_token: token });
                if (response && response.user) {
                    response.user.token = token;
                    setUser(response.user);
                    return { isAuthenticated: true, source: 'auto_token' };
                }
            } catch (error) {
                if (error.status === 401) {
                    console.log('Auto token expired, clearing auth state');
                    clearAuthState();
                    return { isAuthenticated: false, source: 'token_expired' };
                } else {
                    console.warn('Auto token validation failed:', error);
                }
            }
        }
        
        return { isAuthenticated: false, source: 'none' };
        
    } catch (error) {
        console.error('SafeAuthCheck error:', error);
        clearAuthState();
        return { isAuthenticated: false, source: 'error', error };
    }
}

