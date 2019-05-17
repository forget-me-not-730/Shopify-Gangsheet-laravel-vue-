<script>
import {defineComponent} from 'vue'
import AutoNest from "@/Builder/Components/AutoNest.vue";

export default defineComponent({
    extends: AutoNest,
    name: "AutoNestForRolling",
    methods: {
        async handleApply() {
            if (!this.validateApplying()) {
                return
            }

            try {
                this.loading = true

                const margin = Number(this.marginEnabled ? this.margin : 0)
                const artBoardMargin = Number(this.artboardMarginEnabled ? this.artboardMargin : 0)

                const rectangles = fabric.util.getAutoNestRectsFromObjects(this.objects, margin)

                const variants = [
                    {
                        id: this.variant.id,
                        width: this.productSettings.printerWidth,
                        height: this.productSettings.maxHeight,
                        unit: this.artBoardUnit
                    }
                ]

                const res = await axios.post(route('builder.auto-nest'), {
                    rectangles: rectangles,
                    margin: margin,
                    artboardMargin: artBoardMargin,
                    variants: variants
                })

                if (res.data.success) {
                    const packs = res.data.packs.filter(p => p.bins.length > 0)

                    if (packs.length > 0) {
                        await this.$gsb.createDesignsFromBins(packs[0].bins, {margin, artBoardMargin, autoTrim: true})
                    } else {
                        this.$gsb.error('The entry image size is too large to fit inside the artboard.')
                    }
                } else {
                    this.$gsb.error('Not able to generate the auto build. Please try again.')
                }
            } catch (err) {
                console.error(err)
            } finally {
                this.loading = false
            }
        }
    }
})
</script>
