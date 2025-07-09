import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import {ProjectPlugin} from '@/plugins/project';

import { Quasar } from 'quasar';
import quasarOptions from './quasar_options';
import '@quasar/extras/material-icons/material-icons.css'
import '@quasar/extras/fontawesome-v6/fontawesome-v6.css'
import 'quasar/src/css/index.sass';

// Подключаем TMA debug helper в режиме разработки
if (import.meta.env.MODE !== 'production' && import.meta.env.VITE_APP_ENV !== 'production') {
    import('./debug/tma-debug.js');
}

const appName = import.meta.env.VITE_APP_NAME || 'GPT Пульт';
window.TWA = window.Telegram ? window.Telegram.WebApp : null;

// Глобальная функция для отладки (только в режиме разработки)
if (import.meta.env.MODE !== 'production' && import.meta.env.VITE_APP_ENV !== 'production') {
    window.debug = (...t) => console.log(...t);
} else {
    window.debug = () => {}; // Пустая функция в продакшене
}

window.redirect =  (path) => window.location = path;
window.goBack =  () => history.back();

// Функция для добавления Telegram заголовков
const addTelegramHeaders = (headers = {}) => {
    // Проверяем localStorage
    const storedUserId = localStorage.getItem('telegram_auth_user_id')
    const storedTimestamp = localStorage.getItem('telegram_auth_timestamp')
    
    if (storedUserId && storedTimestamp) {
        headers['X-Telegram-Auth-User-Id'] = storedUserId
        headers['X-Telegram-Auth-Timestamp'] = storedTimestamp
    }
    
    // Проверяем куки Telegram
    if (document.cookie) {
        document.cookie.split(';').forEach(cookie => {
            const trimmed = cookie.trim()
            if (trimmed.startsWith('telegram_auth_user_')) {
                const [name, value] = trimmed.split('=')
                if (name && value) {
                    headers['X-Telegram-Cookie-' + name] = value
                }
            }
        })
    }
    
    return headers
}

// Добавляем глобальный перехватчик для Telegram WebApp перенаправлений
if (window.Telegram?.WebApp) {
    // Флаг для предотвращения множественных редиректов
    let isRedirectingFetch = false;
    
    // Перехватываем все fetch запросы
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Автоматически добавляем Telegram заголовки к каждому запросу
        if (!options.headers) {
            options.headers = {};
        }
        
        // Добавляем Telegram заголовки
        options.headers = addTelegramHeaders(options.headers);
        
        // Убеждаемся что куки отправляются
        if (!options.credentials) {
            options.credentials = 'include';
        }
        
        return originalFetch.apply(this, [url, options]).then(response => {
            // Проверяем заголовок перенаправления
            const redirectUrl = response.headers.get('X-Telegram-Redirect');
            if (redirectUrl && window.location.pathname !== redirectUrl && !isRedirectingFetch) {
                // console.log('Global fetch interceptor: Telegram redirect to:', redirectUrl);  // Закомментировано для продакшена
                
                // Если в Telegram WebApp, делаем редирект через window.location
                isRedirectingFetch = true;
                if (window.Telegram?.WebApp) {
                    window.location.href = redirectUrl;
                } else {
                    window.location.href = redirectUrl;
                }
                return null;
            }
            return response;
        });
    };
    
    // console.log('Telegram WebApp fetch interceptor initialized');  // Закомментировано для продакшена
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Quasar, quasarOptions)
            .use(ProjectPlugin);

        return app.mount(el);

    },
    progress: {
        color: '#4B5563',
    },
});
