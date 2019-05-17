import {createApp} from 'vue';
import Selector from "@/Directives/Components/Selector.vue";

const selectorDirective = (app) => {
    app.directive("selector", {
        mounted(el, binding) {
            init(el, binding);
        },
        updated(el, binding) {
            init(el, binding);
        }
    });
};

function init(el, binding) {
    let value = binding.value

    const className = 'v-selector'

    function renderSelector() {
        if (el.classList.contains(className)) {
            return
        }

        el.style.position = 'relative'
        el.style.zIndex = 999
        el.classList.add(className)

        const container = document.createElement('div')
        container.style.position = 'absolute'
        container.style.top = 'calc(100% + 5px)'
        container.style.left = 0
        container.style.width = '100%'
        container.style.zIndex = 1000

        const elSelector = document.createElement('div')
        container.appendChild(elSelector)
        el.appendChild(container)

        const app = createApp(Selector, {
            options: value,
        })

        function closeSelector() {
            app.unmount()
            container.remove()
            el.classList.remove(className)
            el.style.zIndex = 'auto'
            window.removeEventListener('scroll', closeSelector)
            el.removeEventListener('focusout', closeSelector)
        }

        const input = el.querySelector('input')

        app.mixin({
            data() {
                return {
                    search: ''
                }
            },
            mounted() {
                if (input) {
                    input.addEventListener('input', () => {
                        this.search = input.value
                    })
                }
            },
            methods: {
                onSelect(value) {
                    closeSelector()
                    if (input) {
                        input.value = value

                        const changeEvent = new Event('change');
                        const inputEvent = new Event('input');
                        input.dispatchEvent(changeEvent);
                        input.dispatchEvent(inputEvent);
                    }
                }
            }
        })

        app.mount(elSelector)

        window.addEventListener('scroll', closeSelector)
        document.addEventListener('click', (e) => {
            if (!el.contains(e.target)) {
                closeSelector()
            }
        })
    }

    el.addEventListener('click', renderSelector)
    el.addEventListener('focusin', renderSelector)
}

export default selectorDirective;
