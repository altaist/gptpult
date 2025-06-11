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
    /*
        config: {
            brand: {
            // primary: '#e46262',
            // ... or all other brand colors
            },
            notify: {...}, // default set of options for Notify Quasar plugin
            loading: {...}, // default set of options for Loading Quasar plugin
            loadingBar: { ... }, // settings for LoadingBar Quasar plugin
            // ..and many more (check Installation card on each Quasar component/directive/plugin)
        }
    */
};
