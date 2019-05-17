import {getCustomer, getCustomerSession} from '@/Builder/Apis/builderApi'
import {clone} from 'lodash'
import fontMixin from "@/Builder/Mixins/fontMixin";
import eventBus from "@/Builder/Utils/eventBus";
import {clearSessionId} from "@/Builder/Utils/helpers";

export default {
    mixins: [fontMixin],
    props: {
        shop: {
            type: Object,
            required: true
        },
        order: {
            type: [Object, null],
            default: null
        },
        product: {
            type: [Object, null],
            default: null
        },
        variant: {
            type: String,
            default: ''
        },
        quantity: {
            type: [Number, String],
            default: 1
        },
        customer: {
            type: [Object, null],
            default: null
        },
        designJson: {
            type: [Object, null],
            default: null
        },
        edit: {
            type: Boolean,
            default: false
        },
        token: {
            type: String,
            default: ''
        },
        editRequest: {
            type: String,
            default: null
        }
    },
    async created() {
        this.setLocale(this.shop.settings?.language)

        this.$store.commit('setStore', {path: 'isShopify', value: true})
        this.$store.commit('setStore', {path: 'shop', value: this.shop})
        this.$store.commit('setStore', {path: 'editRequest', value: this.editRequest})

        if (this.token) {
            this.$store.commit('setStore', {path: 'token', value: this.token})
        }

        if (this.customer) {
            this.$store.commit('setStore', {path: 'customer', value: this.customer})
        } else {
            getCustomerSession().then(customer => {
                if (customer) {
                    this.$store.commit('setStore', {path: 'customer', value: customer})
                }
            })
        }

        let variant

        if (this.product) {
            this.$store.commit('setStore', {path: 'product', value: this.product})
            this.$store.commit('setStore', {path: 'quantity', value: Number(this.quantity)})

            if (this.product.variants) {
                const sortedVariants = this.product.variants.sort((a, b) => a.height - b.height || a.price - b.price)
                this.$store.commit('setStore', {path: 'variants', value: sortedVariants})
                if (this.variant) {
                    variant = this.product.variants.find(variant => variant.id.toString() === this.variant.toString())
                }
            }
        }

        let designJson
        if (this.designJson) {
            designJson = clone(this.designJson)
        } else if (variant) {
            const key = 'GangSheetDesign_' + variant.id
            const draftDesignJsonString = localStorage.getItem(key)
            if (draftDesignJsonString) {
                const json = JSON.parse(draftDesignJsonString)
                if (json.designId) {
                    localStorage.removeItem(key)
                } else {
                    designJson = json
                }
            }
        }

        if (designJson) {
            designJson.quantity = Number(this.quantity)
            this.$store.commit('setStore', {path: 'workingDesigns', value: [designJson]})

            if (!variant) {
                variant = (this.product?.variants || []).find(variant => variant.id.toString() === designJson.meta.variant.id.toString())
            }

            if (!variant) {
                variant = designJson.meta.variant
            }

            if (designJson.meta.images) {
                this.$store.commit('setStore', {path: 'images', value: designJson.meta.images})
            }

            if (designJson.cutLineType) {
                this.$store.commit('setStore', {path: 'cutLineType', value: designJson.cutLineType})
            }
        }

        if (!variant) {
            variant = this.product?.variants?.[0]
        }

        if (variant && variant.width && variant.height) {
            this.$store.commit('setStore', {path: 'variant', value: variant})

            if ((this.product?.variants || []).length === 0) {
                const sortedVariants = [variant].sort((a, b) => a.height - b.height)
                this.$store.commit('setStore', {path: 'variants', value: sortedVariants})
            }
        }

        if (this.order) {
            this.$store.commit('setStore', {path: 'order', value: this.order})
        }

        if (this.edit) {
            this.$store.commit('setStore', {path: 'editMode', value: this.edit})
        }

        this.loading = false
    },
    beforeMount() {
        eventBus.$on(eventBus.CUSTOMER_LOGIN, () => {
            this.handleSignIn(false)
        })

        eventBus.$on(eventBus.CUSTOMER_SWITCH_ACCOUNT, () => {
            this.handleSignIn(true)
        })
        
        window.removeEventListener('message', this.handleCustomerLoggedInMessageEvent)
        window.addEventListener('message', this.handleCustomerLoggedInMessageEvent.bind(this))
    },
    methods: {
        handleSignIn(switchAccount = false) {
            const popupWinWidth = 600
            const popupWinHeight = 800
            const left = (screen.width - popupWinWidth) / 2;
            const top = (screen.height - popupWinHeight) / 4;

            let shopLoginUrl = `https://${this.shop.name}/account`
            if (switchAccount) {
                shopLoginUrl = `https://${this.shop.name}/account/logout?return_url=/account/login`
            }

            const newWindow = window.open(
                shopLoginUrl,
                'Customer Login',
                `popup=yes,height=${popupWinHeight},width=${popupWinWidth},top=${top},left=${left}`
            );
            if (window.focus) {
                newWindow.focus()
            }

            const timer = setInterval(async () => {
                if (newWindow.closed) {
                    clearInterval(timer);
                }
            }, 1000);
        },
        async handleCustomerLoggedInMessageEvent(e) {
            const data = e.data
            const customerId = data.customer_id
            if (customerId) {
                const customer = await getCustomer(customerId, this.shop.id)
                if (customer) {
                    clearSessionId()
                    this.$store.commit('setStore', {path: 'customer', value: customer})

                    let url = new URL(window.location.href)
                    const params = url.searchParams
                    params.set('customer_id', customer.shopify_id)
                    history.pushState(null, '', `${url.pathname}?${params.toString()}`)
                }
            }
        },
    }
}
