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

const appName = import.meta.env.VITE_APP_NAME || 'GPT Пульт';
window.TWA = window.Telegram ? window.Telegram.WebApp : null;
window.debug = (...t) => console.log(...t);
window.redirect =  (path) => window.location = path;
window.goBack =  () => history.back();

// Добавляем глобальный перехватчик для Telegram WebApp перенаправлений
if (window.Telegram?.WebApp) {
    // Перехватываем все fetch запросы
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(response => {
            // Проверяем заголовок перенаправления
            const redirectUrl = response.headers.get('X-Telegram-Redirect');
            if (redirectUrl && window.location.pathname !== redirectUrl) {
                console.log('Global fetch interceptor: Telegram redirect to:', redirectUrl);
                window.location.href = redirectUrl;
            }
            return response;
        });
    };
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
            .use(Quasar, {
                plugins: {}, // import Quasar plugins and add here
                config: {
                    brand: {
                        primary: '#3b82f6',
                        secondary: '#64748b',
                        accent: '#9C27B0',
                        
                        dark: '#1d1d1d',
                        'dark-page': '#121212',
                        
                        positive: '#21BA45',
                        negative: '#C10015',
                        info: '#31CCEC',
                        warning: '#F2C037'
                    }
                }
            })
            .use(ProjectPlugin);

        return app.mount(el);

    },
    progress: {
        color: '#4B5563',
    },
});
