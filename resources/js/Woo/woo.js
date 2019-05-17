import './bootstrap'

import { createApp } from 'vue'
import { createStore } from 'vuex'

import directives from '@/Directives'

import AdminApp from './AdminApp.vue'

const store = createStore({
    state: {
        shop: window.gs_shop,
        gs_version: window.GangSheetOptions?.gs_version,
        gs_latest_version: window.gs_latest_version
    },
    mutations: {
        setStore (state, { path, value }) {
            _.set(state, path, value)
        }
    },
})

window.onload = () => {
    if (document.getElementById('woo-gang-sheet-app')) {
        const app = createApp(AdminApp)

        directives(app)

        app.use(store)
            .mount('#woo-gang-sheet-app')
    }
}
