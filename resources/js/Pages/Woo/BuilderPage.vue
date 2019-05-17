<template>
    <div v-if="!loading" style="user-select: none" class="text-gray-600 flex flex-col h-full w-full">
        <builder-header/>
        <div class="flex-1 h-1">
            <gang-sheet-builder/>
        </div>
    </div>
</template>

<script>
import GangSheetBuilder from '@/Builder/GangSheet/GangSheetBuilder.vue'
import BuilderHeader from '@/Woo/Components/GangSheetHeader.vue'
import eventBus from '@/Builder/Utils/eventBus'
import {getCustomer} from '@/Builder/Apis/builderApi'
import {Head} from '@inertiajs/vue3'
import fontMixin from "@/Builder/Mixins/fontMixin";

export default {
    name: 'BuilderPage',
    components: {Head, BuilderHeader, GangSheetBuilder},
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
        designStatus: {
            type: [String, null],
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
    data() {
        return {
            selectedVariant: null,
            hasDimension: undefined,
            loading: true
        }
    },
    async created() {
        this.setLocale(this.shop.settings?.language)

        window.isWooCommerce = true

        this.$store.commit('setStore', {path: 'isWoo', value: this.shop})
        this.$store.commit('setStore', {path: 'shop', value: this.shop})
        this.$store.commit('setStore', {path: 'editRequest', value: this.editRequest})

        if (this.designStatus) {
            this.$store.commit('setStore', {path: 'designStatus', value: this.designStatus})
        }

        if (this.token) {
            this.$store.commit('setStore', {path: 'token', value: this.token})
        }

        if (this.customer) {
            this.$store.commit('setStore', {path: 'customer', value: this.customer})
        }

        let variant

        if (this.product) {
            this.$store.commit('setStore', {path: 'product', value: this.product})
            this.$store.commit('setStore', {path: 'quantity', value: Number(this.quantity)})

            const sortedVariants = this.product.variants.sort((a, b) => a.height - b.height || a.price - b.price)
            this.$store.commit('setStore', {path: 'variants', value: sortedVariants})

            if (this.variant) {
                variant = this.product.variants.find(variant => variant.id.toString() === this.variant.toString())
            }
        }

        let hasTextObjects = false

        let designJson
        if (this.designJson) {
            designJson = JSON.parse(JSON.stringify(this.designJson))
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

            this.$store.commit('setStore', {path: 'workingDesigns', value: [designJson]})

            if (!variant) {
                variant = this.product.variants.find(variant => variant.id.toString() === designJson.meta.variant.id.toString())
            }

            hasTextObjects = designJson.objects.some(object => object.type === 'i-text')
            if (designJson.meta.images) {
                this.$store.commit('setStore', {path: 'images', value: designJson.meta.images})
            }

            // remove empty text objects.
            if (hasTextObjects) {
                designJson.objects = designJson.objects.filter(object => {
                    return !(object.type === 'i-text' && !object.text)
                })
            }
        }

        if (variant && variant.width && variant.height) {
            this.selectedVariant = variant
            this.$store.commit('setStore', {path: 'variant', value: variant})
        } else {
            console.error('Invalid variant.')
        }

        if (this.order) {
            this.$store.commit('setStore', {path: 'order', value: this.order})
        }

        if (this.edit) {
            this.$store.commit('setStore', {path: 'editMode', value: this.edit})
        }

        eventBus.$on(eventBus.CUSTOMER_LOGIN, () => {
            this.handleSignIn()
        })

        this.loading = false
    },
    mounted() {
        const script = document.createElement('script')
        script.defer = true
        script.src = 'https://eu.umami.is/script.js'
        script.dataset.websiteId = 'f080aa65-310d-455f-bcd6-ffff2e5fb9c7'
        document.head.appendChild(script)
    },
    methods: {
        handleSignIn() {
            const popupWinWidth = 600
            const popupWinHeight = 800
            const left = (screen.width - popupWinWidth) / 2
            const top = (screen.height - popupWinHeight) / 4
            const shopLoginUrl = `${this.shop.website}/wp-login.php`
            const newWindow = window.open(
                shopLoginUrl,
                'Customer Login',
                `popup=yes,height=${popupWinHeight},width=${popupWinWidth},top=${top},left=${left}`
            )
            if (window.focus) {
                newWindow.focus()
            }

            window.addEventListener('message', async (e) => {
                const data = e.data
                const customerId = parseInt(data.customer_id)
                const customerEmail = data.customer_email
                const customer = await getCustomer(customerId, this.shop.id, customerEmail)
                if (customer) {
                    this.$store.commit('setStore', {path: 'customer', value: customer})
                }
            }, false)

            const timer = setInterval(async () => {
                if (newWindow.closed) {
                    clearInterval(timer)
                }
            }, 1000)
        },
    }
}
</script>
