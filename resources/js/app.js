import "./bootstrap";
import "../css/app.scss";

import {createApp, h, onMounted} from "vue";
import {createInertiaApp} from "@inertiajs/vue3";
import {resolvePageComponent} from "laravel-vite-plugin/inertia-helpers";
import {ZiggyVue} from "../../vendor/tightenco/ziggy/dist/vue.m";
import {createStore} from "vuex";
import moment from "moment";
import {VueReCaptcha} from 'vue-recaptcha-v3'
import directives from "@/Directives";



const appName = window.document.getElementsByTagName("title")[0]?.innerText || "Laravel";

const store = createStore({
    state: {
        confirmation: null,
        pageData: null,
    },
    mutations: {
        setStore(state, {path, value}) {
            _.set(state, path, value)
        }
    },
})

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({el, App, props, plugin}) {
        const captchaKey = props.initialPage.props.recaptcha_key;
        const app = createApp({
            setup() {
                onMounted(() => {
                    delete el.dataset.page;
                });
            },
            render: () => h(App, props)
        })
            .use(plugin)
            .use(store)
            .use(VueReCaptcha, {siteKey: captchaKey})
            .use(ZiggyVue, Ziggy)

        directives(app)

        app.config.globalProperties.$filters = {
            formatDate(value) {
                return moment(String(value)).format("YYYY-MM-DD");
            },
            formatDateTime(value) {
                return moment(String(value)).format("YYYY-MM-DD hh:mm:ss");
            },
        };

        app.config.globalProperties.$route = route

        app.mixin({
            computed: {
                pageData: {
                    get() {
                        return store.state.pageData
                    },
                    set(value) {
                        store.commit('setStore', {path: 'pageData', value})
                    }
                },
                isDev() {
                    return window.appEnv === 'development' || window.appEnv === 'local'
                }
            }
        })
        return app.mount(el);
    },
    progress: {
        color: "#502A7B",
    },
});
