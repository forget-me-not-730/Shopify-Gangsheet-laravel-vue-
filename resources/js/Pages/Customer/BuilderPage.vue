<template>
    <BuilderLayout title="Builder">
        <div v-if="!loading" style="user-select: none" class="text-gray-600 flex flex-col h-full w-full">
            <gang-sheet-header/>
            <div class="flex-1 h-1">
                <gang-sheet-builder/>
            </div>
        </div>
        <customer-login-modal :open="openCustomerLogin" @close="openCustomerLogin = false"/>
    </BuilderLayout>
</template>

<script>
import GangSheetBuilder from '@/Builder/GangSheet/GangSheetBuilder.vue'
import BuilderLayout from '@/Layouts/BuilderLayout.vue'
import GangSheetHeader from '@/Components/Customer/GangSheetHeader.vue'
import {clearSessionId} from '@/Builder/Utils/helpers.js'
import CustomerLoginModal from '@/Builder/Modals/CustomerLoginModal.vue'
import eventBus from '@/Builder/Utils/eventBus.js'
import {router} from '@inertiajs/vue3'
import {clone} from 'lodash'
import {logout} from '@/Builder/Apis/builderApi'
import fontMixin from "@/Builder/Mixins/fontMixin";

export default {
    name: 'BuilderPage',
    components: {CustomerLoginModal, GangSheetHeader, BuilderLayout, GangSheetBuilder},
    mixins: [fontMixin],
    props: {
        id: {
            type: String,
            required: true
        },
        product: {
            type: [Object, null],
            default: null
        },
        merchant: {
            type: Object,
            required: true
        },
        customer: {
            type: [Object, null],
            default: null
        },
        order: {
            type: [Object, null],
            default: null
        },
        size: {
            type: Object,
            required: true
        },
        data: {
            type: Object,
        },
        workingDesigns: {
            type: Array,
        },
        quantity: {
            type: [String, Number],
            required: true
        },
        editMode: {
            type: Boolean,
            default: false
        },
        admin: {
            type: Boolean,
            default: false
        },
        editRequest: {
            type: String,
            default: null
        },
        token: {
            type: String,
            default: null
        }
    },
    data() {
        return {
            loading: true,
            openCustomerLogin: false
        }
    },
    async created() {

        this.setLocale(this.merchant.settings?.language)

        if (this.workingDesigns) {
            this.$store.commit('setStore', {path: 'workingDesigns', value: this.workingDesigns})
            this.$store.commit('setStore', {path: 'images', value: this.workingDesigns[0]?.meta.images || []})
        } else {
            this.$store.commit('setStore', {path: 'workingDesigns', value: []})
            this.$store.commit('setStore', {path: 'images', value: []})
        }

        this.$store.commit('setStore', {path: 'workingDesignIndex', value: 0})

        this.$store.commit('setStore', {path: 'isStandalone', value: true})

        this.$store.commit('setStore', {path: 'shop', value: this.merchant})
        this.$store.commit('setStore', {path: 'editRequest', value: this.editRequest})

        this.$store.commit('setStore', {path: 'product', value: this.product})
        this.$store.commit('setStore', {path: 'quantity', value: Number(this.quantity)})

        this.$store.commit('setStore', {path: 'editMode', value: this.editMode})
        this.$store.commit('setStore', {path: 'admin', value: this.admin})
        this.$store.commit('setStore', {path: 'order', value: this.order})
        this.$store.commit('setStore', {path: 'token', value: this.token})

        if (this.customer) {
            this.$store.commit('setStore', {path: 'customer', value: this.customer})
        }

        this.$store.commit('setStore', {path: 'variant', value: this.size})

        if (this.data) {
            const designJson = clone(this.data)

            this.$store.commit('setStore', {path: 'workingDesigns', value: [designJson]})

            if (designJson.meta.variant) {
                this.$store.commit('setStore', {path: 'variant', value: designJson.meta.variant})

                const oldVariant = designJson.meta.variant
                if (oldVariant) {
                    if (this.size.width !== oldVariant.width || this.size.height !== oldVariant.height) {
                        oldVariant.newSize = this.size
                        oldVariant.id = 10000;
                        (this.product.sizes || this.product.variants).push(oldVariant)
                    }
                }
            }

            if (designJson.meta.images) {
                this.$store.commit('setStore', {path: 'images', value: designJson.meta.images})
            }
        }

        if (this.product) {
            const sortedVariants = (this.product.sizes || this.product.variants).sort((a, b) => a.height - b.height || a.price - b.price)
            this.$store.commit('setStore', {path: 'variants', value: sortedVariants})
        } else {
            this.$store.commit('setStore', {path: 'variants', value: [this.size]})
        }

        eventBus.$on(eventBus.CUSTOMER_LOGIN, () => {
            this.openCustomerLogin = true
        })

        eventBus.$on(eventBus.CUSTOMER_LOGOUT, async () => {
            NProgress.start()
            eventBus.$emit(eventBus.CLOSE_MODAL_ALL)
            const customerLogoutSuccess = await logout()
            if (customerLogoutSuccess) {
                this.openCustomerLogin = false
                this.$store.commit('setStore', {path: 'customer', value: null})
                clearSessionId()
            }
            NProgress.done()
        })

        eventBus.$on(eventBus.CUSTOMER_LOGIN_SUCCESS, async () => {
            this.openCustomerLogin = false
            await router.reload({
                only: ['customer'],
                onFinish: () => {
                    if (this.customer) {
                        this.$store.commit('setStore', {path: 'customer', value: this.customer})
                    }
                }
            })
        })

        this.loading = false
    },
    mounted() {
        const script = document.createElement('script')
        script.defer = true
        script.src = 'https://eu.umami.is/script.js'
        script.dataset.websiteId = 'f080aa65-310d-455f-bcd6-ffff2e5fb9c7'
        document.head.appendChild(script)
    }
}
</script>
