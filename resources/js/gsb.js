import './bootstrap'

import {createApp, h, onMounted} from 'vue'
import {createInertiaApp} from '@inertiajs/vue3'
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers'
import {ZiggyVue} from '../../vendor/tightenco/ziggy/dist/vue.m'
import directives from '@/Directives'

import GangSheetBuilder from '@/Builder'

const appName = 'Build a Gang Sheet'

function createBuilder() {
    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue')
            ),
        setup({el, App, props, plugin}) {
            const app = createApp({
                setup() {
                    onMounted(() => {
                        delete el.dataset.page
                    })
                },
                render: () => h(App, props)
            })
                .use(GangSheetBuilder)
                .use(plugin)
                .use(ZiggyVue, Ziggy)

            directives(app)

            app.config.globalProperties.$route = route

            return app.mount(el)
        },
        progress: {
            color: '#502A7B',
        },
    }).then(() => {
        // ignore
    })
}

if (route().current('gs.builder.edit')) {
    createBuilder()
} else {
    window.addEventListener('message', function (e) {
        if (e.data && e.data.action === 'gsb-init') {
            window.GsbProps = e.data.data
            createBuilder()
        }
    })
}


