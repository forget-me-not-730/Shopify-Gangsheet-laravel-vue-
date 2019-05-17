<template>
    <div class="relative w-full h-full overflow-hidden">
        <div v-if="loading" class="absolute w-full h-full bg-gray-300 bg-opacity-50 flex flex-col items-center justify-center">
            <spinner/>
        </div>
        <canvas ref="canvas" width="150" height="150"></canvas>
    </div>
</template>

<script>
import { fabric } from 'fabric';
import Spinner from '@/Components/Spinner.vue'

export default {
    name: 'FilteredImage',
    components: {Spinner},
    props: {
        src: {
            type: String,
            required: false,
        },
        filters: {
            type: Object,
            required: false,
        },
    },
    data() {
        return {
            canvas: null,
            fabricImage: null,
            loading: false,
        };
    },
    mounted() {
        this.initCanvas()
    },
    methods: {
        initCanvas() {
            this.loading = true
            this.canvas = new fabric.Canvas(this.$refs.canvas)
            fabric.util.makeImageFromUrl(this.src, false).then(img => {
                if (img) {
                    img.set({
                        left: this.canvas.width / 2,
                        top: this.canvas.height / 2,
                        originX: 'center',
                        originY: 'center',
                        selectable: false,
                        editable: true,
                    })

                    img.scale(Math.min(this.canvas.width / img.width, this.canvas.height / img.height))

                    this.fabricImage = img
                    this.canvas.add(img)
                    this.applyFilters()
                    this.canvas.renderAll()
                    this.loading = false
                }
            })
        },
        applyFilters() {
            this.fabricImage.filters = []
            const {brightness, contrast, saturation} = this.filters

            const brightnessFilter = new fabric.Image.filters.Brightness({brightness: brightness})
            const contrastFilter = new fabric.Image.filters.Contrast({contrast: contrast})
            const saturationFilter = new fabric.Image.filters.Saturation({saturation: saturation})

            this.fabricImage.filters.push(
                brightnessFilter,
                contrastFilter,
                saturationFilter,
            );

            if (this.filters.color) {
                const blendColorFilter = new fabric.Image.filters.BlendColor({color: this.filters.color, mode: "overlay"})
                this.fabricImage.filters.push(blendColorFilter)
            }

            if (this.filters.opacity) {
                this.fabricImage.set({opacity: this.filters.opacity}).setCoords()
            }

            this.fabricImage.applyFilters()

            this.canvas.renderAll()
        },
    },
    watch: {
        filters: {
            handler() {
                if (this.fabricImage) {
                    this.applyFilters()
                }
            },
            deep: true,
        },
        src(newSrc) {
            this.canvas.clear()
            this.initCanvas()
        },
    },
}
</script>

<style scoped>

</style>
