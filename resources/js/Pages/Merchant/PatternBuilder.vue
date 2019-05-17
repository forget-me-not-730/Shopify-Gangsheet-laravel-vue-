<template>
    <builder-layout title="Product Pattern Builder">
        <div v-if="!loading" style="user-select: none" class="text-gray-600 flex flex-col h-full w-full">
            <pattern-header/>
            <div class="flex-1 h-1">
                <pattern-builder/>
            </div>
        </div>
    </builder-layout>
</template>

<script>
import PatternBuilder from '@/Builder/GangSheet/PatternBuilder.vue'
import BuilderLayout from '@/Layouts/BuilderLayout.vue'
import PatternHeader from '@/Components/Customer/PatternHeader.vue'
import fontMixin from "@/Builder/Mixins/fontMixin";

export default {
    components: {PatternHeader, BuilderLayout, PatternBuilder},
    mixins: [fontMixin],
    props: {
        product: {
            type: Object,
            required: true
        },
        merchant: {
            type: Object,
            required: true
        },
    },
    data() {
        return {
            loading: true,
        }
    },
    async created() {
        this.setLocale(this.merchant.settings?.language)

        this.$store.commit('setStore', {path: 'isStandalone', value: true})

        this.$store.commit('setStore', {path: 'shop', value: this.merchant})
        this.$store.commit('setStore', {path: 'product', value: this.product})
        this.$store.commit('setStore', {path: 'editMode', value: true})
        this.$store.commit('setStore', {path: 'patternMode', value: true})

        const variants = this.product.sizes
        this.$store.commit('setStore', {path: 'variants', value: variants})
        this.$store.commit('setStore', {path: 'variant', value: variants[0]})

        this.loading = false
    }
}
</script>
