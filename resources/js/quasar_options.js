// import "@quasar/extras/material-icons/material-icons.css";
import "@quasar/extras/fontawesome-v6/fontawesome-v6.css";
import quasarLang from "quasar/lang/ru";
import { Dialog, Notify } from 'quasar';

// To be used on app.use(Quasar, { ... })
export default {
    lang: quasarLang,
    plugins: {
        Notify, Dialog
    },
    extras: [
        'fontawesome-v6'
    ],
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
        },
        notify: {}, // default set of options for Notify Quasar plugin
        // ..and many more (check Installation card on each Quasar component/directive/plugin)
    }
};
