<script>
import {defineComponent} from 'vue'
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import Spinner from "@/Components/Spinner.vue";

export default defineComponent({
    name: "AutoNestButton",
    components: {Spinner},
    mixins: [gangSheetMixin],
    data() {
        return {
            loading: false
        }
    },
    methods: {
        autoNest() {
            if (window._gangSheetCanvasEditor) {
                this.$gsb.updateCanvasData()
                const objects = fabric.util.getAutoNestObjectsFromDesigns(this.currentDesign)

                const margin = window._gangSheetCanvasEditor.getMargin()
                const artBoardMargin = window._gangSheetCanvasEditor.getArtboardMargin()

                const rectangles = fabric.util.getAutoNestRectsFromObjects(objects, margin)

                if (rectangles.length) {
                    this.loading = true
                    axios.post(route('builder.auto-nest'), {
                        rectangles: rectangles,
                        margin: margin,
                        artboardMargin: artBoardMargin,
                        variants: [this.currentDesign.meta.variant],
                    }).then(async (res) => {
                        if (res.data.success) {
                            const packs = res.data.packs.filter(p => p.bins.length > 0)
                            await this.$gsb.createCurrentDesignFromBin(packs[0].bins[0], {
                                margin,
                                artBoardMargin
                            })
                        } else {
                            this.$gsb.error('Not able to generate the auto build. Please try again.')
                        }
                    }).finally(() => {
                        this.loading = false
                    })
                }
            }
        }
    }
})
</script>

<template>
    <button class="btn-secondary btn-sm mr-1" :disabled="loading" @click="autoNest">
        <spinner v-if="loading" class="!w-3 !h-3 mr-1"/>
        {{ $t('Auto Nest') }}
    </button>
</template>
