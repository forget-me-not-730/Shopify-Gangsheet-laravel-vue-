<script>
import {defineComponent} from 'vue'
import GsCanvas from '@/Builder/Components/GsCanvas.vue'
import {waitForWebfonts} from '@/Builder/Utils/helpers'
import Spinner from '@/Components/Spinner.vue'
import fontMixin from "@/Builder/Mixins/fontMixin";

window._gsBuilderOptions = {
    visualQualify: true
}

export default defineComponent({
    name: 'DesignViewPage',
    components: {Spinner, GsCanvas},
    mixins: [fontMixin],
    props: {
        designJson: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            settings: {
                showResolutionLines: false
            },
            loading: true,
            initializing: true,
            width: '0px',
            height: '0px'
        }
    },
    mounted() {

        const vWidth = Number(this.designJson.meta.variant.width)
        const vHeight = Number(this.designJson.meta.variant.height)

        if (vWidth > vHeight) {
            this.width = '500px'
            this.height = 500 * (vHeight / vWidth) + 'px'
        } else {
            this.height = '500px'
            this.width = 500 * (vWidth / vHeight) + 'px'
        }

        waitForWebfonts(this.$page.props.shopFonts).then(() => {
            this.loading = false
        })
    },
    computed: {
        currentDesign() {
            return this.designJson
        },
        variant() {
            return this.currentDesign.meta.variant
        }
    },
    methods: {
        handleCanvasInitialized(canvas) {
            canvas.setBackgroundColor('')
            canvas.renderAll()
            window._gangSheetCanvasEditor = canvas

            async function redrawImages() {
                const objects = canvas.getObjects('image')

                for (let i = 0; i < objects.length; i++) {
                    const obj = objects[i]
                    const src = obj.getSourceUrl()
                    if (src.endsWith('.svg') || obj.required_reload) {
                        canvas.remove(obj)
                        const image = await fabric.util.makeImageFromUrl(obj.getSourceUrl(), false)
                        image.scaleToWidth(obj.width * obj.scaleX)
                        const scaleY = obj.height * obj.scaleY / image.height
                        image.set({
                            id: obj.imageId,
                            parentId: obj.parentId,
                            isGalleryImage: obj.isGalleryImage,
                            mimeType: obj.mimeType,
                            originSrc: obj.originUrl,
                            left: obj.left,
                            top: obj.top,
                            scaleY: scaleY,
                            angle: obj.angle,
                            overlayColor: obj.overlayColor
                        })

                        image.filters = obj.filters
                        image.applyFilters()

                        canvas.insertAt(image, i, false)
                    }
                }
            }

            canvas.renderOnAddRemove = false

            redrawImages().then(() => {
                canvas.renderOnAddRemove = true
                canvas.renderAll()

                this.initializing = false
                window._gangSheetCanvasEditor.isReady = true
            })
        }
    }

})
</script>

<template>
    <div class="w-full h-full bg-gray-200 flex justify-center items-center">
        <spinner v-if="initializing" class="absolute !w-6 !h-6"/>
        <div v-if="!loading" :style="{width, height}" class="transparent-pattern border border-dashed border-red-500">
            <gs-canvas v-if="!loading" :variant="variant" :json-data="currentDesign" :options="settings" :compact="1" :show-resolution-lines="false" @initialized="handleCanvasInitialized"/>
        </div>
    </div>
</template>

<style scoped>

</style>
