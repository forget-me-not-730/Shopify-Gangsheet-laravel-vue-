<script>
import {defineComponent} from 'vue'
import GangSheetCanvas from '@/Builder/Utils/GangSheetCanvas'
import {convertDimension, getPixelSize} from '@/Builder/Utils/helpers'

export default defineComponent({
    name: 'GsCanvas',
    props: {
        variant: {
            type: Object,
            required: true
        },
        options: {
            type: Object,
            default: null
        },
        jsonData: {
            type: Object,
            default: null
        },
        compact: {
            type: Number,
            default: 0.8
        },
        showResolutionLines: {
            type: Boolean,
            default: true
        }
    },
    mounted() {
        this.initializeCanvas()
    },
    methods: {
        getPixelSize(size) {
            return size * getPixelSize(this.variant.unit)
        },
        initializeCanvas() {
            const width = this.variant.width
            const height = this.variant.height

            const designWidth = this.getPixelSize(width)
            const designHeight = this.getPixelSize(height)

            const clientWidth = this.$el.clientWidth
            const clientHeight = this.$el.clientHeight

            let editorWidth = clientWidth * this.compact
            let editorHeight = clientHeight * this.compact

            if (width / height > clientWidth / clientHeight) {
                editorHeight = height / width * editorWidth
            } else {
                editorWidth = width / height * editorHeight
            }

            const zoom = editorWidth / designWidth

            const artBoardSettings = this.$gsb.getArtBoardSettings()

            let jsonData = this.jsonData

            if (!jsonData) {
                jsonData = this.variant.pattern;
            }

            const options = {
                width: clientWidth,
                height: clientHeight,
                designWidth: designWidth,
                designHeight: designHeight,
                unit: this.variant.unit,
                variant: this.variant,
                showResolutionLines: this.showResolutionLines,
                jsonData: jsonData,
                zoom: zoom,
                name: this.$t('New Gang Sheet'),
                visualQuality: artBoardSettings.visualQuality,
                patternMode: this.options?.patternMode,
                printPattern: this.options?.productPattern?.printPattern,
            }

            const artboardMarginEnabled = this.options?.turnOnArtboardMargin ?? this.options?.turnOnMargin
            if (artboardMarginEnabled !== undefined) {
                options.artboardMarginEnabled = artboardMarginEnabled
            }

            const artboardMargin = this.options?.defaultArtboardMarginSize ?? this.options?.defaultMarginSize
            if (artboardMargin !== undefined) {
                const marginUnit = this.options?.defaultArtboardMarginUnit ?? 'in'
                options.artboardMargin = convertDimension(artboardMargin, marginUnit, this.variant.unit)
            }

            if (this.options?.defaultMarginSize !== undefined) {
                const marginUnit = this.options?.defaultMarginUnit ?? 'in'
                options.margin = convertDimension(this.options.defaultMarginSize, marginUnit, this.variant.unit)
            }

            if (this.options?.resolutionLevels) {
                options.resolutionLevels = this.options.resolutionLevels
            }

            if (this.options?.hideTerribleResolution !== undefined) {
                options.hideTerribleResolution = this.options.hideTerribleResolution
            }

            if (this.options?.turnOnMargin !== undefined) {
                options.marginEnabled = this.options.turnOnMargin
            }

            if (this.options?.autoResizeSingleImage !== undefined) {
                options.autoResizeSingleImage = this.options.autoResizeSingleImage
            }

            if (this.options?.ensureOptimalResolution !== undefined) {
                options.ensureOptimalResolution = Boolean(this.options.ensureOptimalResolution)
            }

            if (this.options?.enableFlipping !== undefined) {
                options.enableFlipping = Boolean(this.options.enableFlipping)
            }

            this.canvasInstance = new GangSheetCanvas(this.$el, options)
            this.canvasInstance.ready.then(() => {
                this.$emit('initialized', this.canvasInstance)
            })
        },
        resizeCanvas(newHeight) {
            this.variant.height = newHeight
        }
    }
})
</script>

<template>
    <canvas class="w-full h-full"/>
</template>

<style scoped>

</style>
