<script>
import {defineComponent} from 'vue'
import GangSheetBuilder from '@/Builder/GangSheet/GangSheetBuilder.vue'
import GsbHeader from '@/Components/Gsb/GsbHeader.vue'
import {formatFonts, waitForWebfonts} from '@/Builder/Utils/helpers'
import eventBus from '@/Builder/Utils/eventBus'
import {clone} from 'lodash'

export default defineComponent({
    name: 'EditPage',
    components: {GsbHeader, GangSheetBuilder},
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
        if (this.$page.props.shopFonts) {
            const [fonts, defaultFont] = formatFonts(this.$page.props.shopFonts, this.$page.props.defaultFont)

            this.$store.commit('setStore', {path: 'shopFonts', value: fonts})
            this.$store.commit('setStore', {path: 'defaultFont', value: defaultFont})
        }

        this.$store.commit('setStore', {path: 'shop', value: this.shop})

        const designJson = clone(this.design.data)

        this.$store.commit('setStore', {path: 'editMode', value: true})
        this.$store.commit('setStore', {path: 'workingDesigns', value: [designJson]})
        this.$store.commit('setStore', {path: 'images', value: designJson.meta.images || []})

        const variant = designJson.meta.variant
        this.$store.commit('setStore', {path: 'variant', value: variant})
        this.$store.commit('setStore', {path: 'variants', value: [variant]})

        waitForWebfonts(this.$page.props.shopFonts).then(() => {
            eventBus.$emit(eventBus.REFRESH_BUILDER)
            this.loading = false
        })
    }
})
</script>

<template>
    <div v-if="!loading" style="user-select: none" class="text-gray-600 flex flex-col h-full w-full">
        <gsb-header/>
        <div class="flex-1 h-1">
            <gang-sheet-builder/>
        </div>
    </div>
</template>
