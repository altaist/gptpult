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
import 'quasar/src/css/index.sass';

const appName = import.meta.env.VITE_APP_NAME || 'GPT Пульт';
window.TWA = window.Telegram ? window.Telegram.WebApp : null;
window.debug = (...t) => console.log(...t);
window.redirect =  (path) => window.location = path;
window.goBack =  () => history.back();

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
        // color: '#4B5563',
    },
});
