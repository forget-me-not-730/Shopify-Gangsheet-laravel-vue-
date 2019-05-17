<script>
import {defineComponent} from 'vue'
import GangSheetBuilder from '@/Builder/GangSheet/GangSheetBuilder.vue'
import GsbHeader from '@/Components/Gsb/GsbHeader.vue'
import {clone} from 'lodash'
import fontMixin from "@/Builder/Mixins/fontMixin";

export default defineComponent({
    name: 'BuilderPage',
    components: {GsbHeader, GangSheetBuilder},
    mixins: [fontMixin],
    props: {
        shop: {
            type: Object,
            required: true
        },
        design: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            loading: false
        }
    },
    created() {
        if (window.GsbProps) {
            this.$store.commit('setStore', {path: 'isCustomApi', value: true})
            this.$store.commit('setStore', {path: 'shop', value: this.shop})
            this.$store.commit('setStore', {path: 'product', value: window.GsbProps.product})

            let variant = null

            if (this.design) {

                const designJson = clone(this.design.data)

                this.$store.commit('setStore', {path: 'editMode', value: true})
                this.$store.commit('setStore', {path: 'workingDesigns', value: [designJson]})
                this.$store.commit('setStore', {path: 'images', value: designJson.meta.images || []})

                variant = designJson.meta.variant
            } else {
                this.$store.commit('setStore', {path: 'workingDesigns', value: []})
                this.$store.commit('setStore', {path: 'images', value: []})
            }

            if (!variant && window.GsbProps.sizes.length > 0) {
                if (window.GsbProps.size_id) {
                    variant = window.GsbProps.sizes.find(size => size.id.toString() === window.GsbProps.size_id.toString())
                }

                if (!variant) {
                    variant = window.GsbProps.sizes[0]
                }
            }

            if (window.GsbProps.sizes.length > 0) {
                this.$store.commit('setStore', {path: 'variants', value: window.GsbProps.sizes})
            } else if (variant) {
                this.$store.commit('setStore', {path: 'variants', value: [variant]})
            }

            this.$store.commit('setStore', {path: 'variant', value: variant})

            if (window.GsbProps.customer) {
                window.GsbProps.customer.user_id = this.shop.id
                axios.post(route('gs.builder.customer.save'), window.GsbProps.customer).then(res => {
                    if (res.data.success) {
                        this.$store.commit('setStore', {path: 'customer', value: res.data.customer})
                    }
                })
            }
        }
    }
})
</script>

<template>
    <div v-if="!loading" style="user-select: none" class="flex flex-col h-full w-full">
        <gsb-header/>
        <div class="flex-1 h-1">
            <gang-sheet-builder/>
        </div>
    </div>
</template>
