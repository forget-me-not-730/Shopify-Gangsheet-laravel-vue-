var gs_builder = window.gs_builder || (function (document, window) {

    const DEV_SHOP_ID = '4f21c0f7-940b-4753-b5d4-8772449df254'

    const GS_EV_BUILDER_READY = 'ready'
    const GS_EV_BUILDER_CLOSED = 'closed'

    const GS_EV_DESIGN_CREATED = 'design:created'
    const GS_EV_DESIGN_UPDATED = 'design:updated'

    const GS_EV_CUSTOMER_LOGIN = 'customer:login'
    const GS_EV_CUSTOMER_REGISTER = 'customer:register'

    const GBS = function () {
    }

    GBS.version = '1.0.0'

    GBS.mode = 'production'

    GBS.document = null

    GBS.window = null

    GBS.container = 'gs-builder'

    GBS.eventNames = [
        GS_EV_DESIGN_CREATED,
        GS_EV_DESIGN_UPDATED,
        GS_EV_BUILDER_CLOSED,
        GS_EV_BUILDER_READY,
        GS_EV_CUSTOMER_LOGIN,
        GS_EV_CUSTOMER_REGISTER
    ]

    GBS.shop_id = DEV_SHOP_ID

    GBS.customer = null

    GBS.product = {
        settings: {}
    }

    GBS.sizes = []

    GBS.size_id = null

    GBS.quantity = 22

    GBS.design_id = null

    GBS.eventListeners = {}

    GBS.spinner = null

    GBS.openStartModal = true

    GBS.initialize = function () {
        this.window.addEventListener('message', (e) => {
            if (e.data && e.data.action === 'gsb-close') {
                this.close()

                if (this.eventListeners[GS_EV_BUILDER_CLOSED]) {
                    this.eventListeners[GS_EV_BUILDER_CLOSED].forEach((handler) => {
                        handler({
                            event: GS_EV_BUILDER_CLOSED
                        })
                    })
                }
            }

            if (e.data && e.data.action === 'gsb-loaded') {
                this.getIframe().style.opacity = 1

                if (this.eventListeners[GS_EV_BUILDER_READY]) {
                    this.eventListeners[GS_EV_BUILDER_READY].forEach((handler) => {
                        handler({
                            event: GS_EV_BUILDER_READY
                        })
                    })
                }
            }

            if (e.data && e.data.action === 'gsb-event') {
                if (this.eventNames.includes(e.data.event)) {
                    if (this.eventListeners[e.data.event]) {
                        this.eventListeners[e.data.event].forEach((handler) => {
                            handler({
                                event: e.data.event,
                                data: e.data.data
                            })
                        })
                    }
                }
            }
        })

        window.removeEventListener('beforeunload', () => {
            this.close()
        })
    }

    GBS.config = function (options = {}) {

        if (options.mode) {
            this.mode = options.mode
        }

        if (this.mode === 'production') {
            if (!options.shop_id) {
                throw new Error('shop_id is required')
            }

            if (options.shop_id === DEV_SHOP_ID) {
                throw new Error('shop_id is invalid')
            }
        }

        if (this.mode === 'development') {
            console.warn('You are in development mode.')
        }

        if (options.customer) {
            if (!options.customer.id) {
                throw new Error('customer.id is required')
            }

            this.customer = options.customer
        }

        if (!options.design_id && !options.sizes) {
            throw new Error('sizes is required')
        }

        if (options.container) {
            this.container = options.container
        }

        let fountSize = false

        if (options.sizes) {
            this.sizes = options.sizes.map((size) => {

                if (!size.id || !size.width || !size.height) {
                    throw new Error('sizes must have id, width, and height properties')
                }

                if (size.height > 240) {
                    throw new Error('sizes height must be less than 240')
                }

                const unit = size.unit || 'in'
                const title = size.title || `${size.width}${unit} x ${size.height}${unit}`

                if (options.size_id && options.size_id === size.id) {
                    fountSize = true
                }

                const _size = {
                    id: size.id,
                    width: size.width,
                    height: size.height,
                    unit: unit,
                    title: title,
                    visible: size.visible || 'Visible'
                }

                if (size.metadata) {
                    _size.metadata = size.metadata
                }

                return _size
            })
        }

        if (options.shop_id) {
            this.shop_id = options.shop_id
        }

        if (options.size_id) {

            if (!fountSize) {
                throw new Error('size_id is invalid')
            }

            this.size_id = options.size_id
        }

        if (options.design_id) {
            this.design_id = options.design_id
        }

        if (typeof options.settings !== 'undefined') {
            this.product.settings = {
                ...this.product.settings,
                ...options.settings
            }
        }

        return this
    }

    GBS.getElement = function () {
        let el = this.document.getElementById(this.container)

        if (!el) {
            el = this.document.createElement('div')
            el.setAttribute('id', 'gs-builder')
            this.document.body.appendChild(el)

            el.style.position = 'fixed'
            el.style.left = '0'
            el.style.top = '0'
            el.style.zIndex = '999999'
            el.style.width = '100vw'
            el.style.height = '100vh'
        }

        el.style.backgroundColor = 'white'
        el.style.display = 'block'

        if (this.spinner) {
            el.style.backgroundPosition = 'center'
            el.style.backgroundRepeat = 'no-repeat'
            el.style.backgroundImage = `url(${this.spinner})`
        }

        return el
    }

    GBS.getIframe = function () {
        const el = this.getElement()

        let iframe = el.querySelector('iframe')

        if (!iframe) {
            iframe = this.document.createElement('iframe')
            el.appendChild(iframe)
        }

        iframe.style.border = 'none'
        iframe.style.width = '100%'
        iframe.style.height = '100%'
        iframe.style.position = 'relative'
        iframe.style.zIndex = '999999'

        return iframe
    }

    GBS.getIframeUrl = function () {
        let BASE_URL = 'https://app.buildagangsheet.com'

        if (this.shop_id === DEV_SHOP_ID) {
            BASE_URL = 'https://dev.buildagangsheet.com'
        }

        if (this.mode === 'test') {
            BASE_URL = 'https://dev.buildagangsheet.test'
        }

        const builderUrl = new URL(`${BASE_URL}/gs/builder`)

        builderUrl.searchParams.append('shop_id', this.shop_id)
        builderUrl.searchParams.append('domain', window.location.host)

        if (this.design_id) {
            builderUrl.searchParams.append('design_id', this.design_id)
        }

        return builderUrl.toString()
    }

    GBS.open = function (options) {
        this.eventListeners = {}

        this.config(options)

        this.document.body.style.overflow = 'hidden'
        this.document.querySelector('html').style.overflow = 'hidden'

        const iframe = this.getIframe()

        const openUrl = this.getIframeUrl()

        if (iframe.src !== openUrl) {
            iframe.style.opacity = 0
            iframe.src = openUrl

            iframe.onload = () => {
                iframe.contentWindow.postMessage({
                    action: 'gsb-init',
                    data: {
                        sizes: this.sizes,
                        size_id: this.size_id,
                        quantity: this.quantity,
                        customer: this.customer,
                        product: this.product
                    }
                }, '*')
            }
        }

        this.getElement().style.display = 'block'

        return this
    }

    GBS.close = function () {
        this.getIframe().remove()
        this.getElement().style.display = 'none'
        this.document.body.style.overflow = 'auto'
        this.document.querySelector('html').style.overflow = 'auto'
    }

    GBS.on = function on(event, handler) {
        if (arguments.length === 1) {
            for (const prop in event) {
                this.on(prop, event[prop])
            }
        } else {
            if (this.eventNames.includes(event)) {
                if (!this.eventListeners[event]) {
                    this.eventListeners[event] = []
                }
                this.eventListeners[event].push(handler)
            } else {
                console.error(event + ' is not a valid event name.')
            }
        }

        return this
    }

    GBS.setCustomer = function (customer) {
        this.customer = customer
    }

    if (typeof document !== 'undefined' && typeof window !== 'undefined') {
        if (document instanceof (typeof HTMLDocument !== 'undefined' ? HTMLDocument : Document)) {
            GBS.document = document
        } else {
            GBS.document = document.implementation.createHTMLDocument('')
        }
        GBS.window = window
    }

    GBS.initialize()

    return GBS
})(document, window)

window.gs_builder = gs_builder

if (typeof exports !== 'undefined') {
    exports.gs_builder = gs_builder
}
