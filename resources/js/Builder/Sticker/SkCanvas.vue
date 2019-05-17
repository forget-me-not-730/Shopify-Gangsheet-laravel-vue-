<script>
import {defineComponent} from 'vue'
import StickerCanvas from '@/Builder/Utils/StickerCanvas'
import {getPixelSize} from '@/Builder/Utils/helpers'
import {convertDimension} from '@/Builder/Utils/helpers'
import eventBus from '../Utils/eventBus';

export default defineComponent({
    name: 'SkCanvas',
    props: {
        options: {
            type: Object,
            default: null
        },
        jsonData: {
            type: Object,
            default: null
        },
        variant: {
            type: Object,
            required: true
        },
        compact: {
            type: Number,
            default: 0.8
        }
    },
    mounted() {
        this.initialize()
    },
    methods: {
        getPixelSize(size) {
            return size * getPixelSize(this.variant.unit)
        },
        initialize() {
            const width = this.variant.width
            const height = this.variant.height

            const stickerWidth = this.getPixelSize(width)
            const stickerHeight = this.getPixelSize(height)

            const clientWidth = this.$el.clientWidth
            const clientHeight = this.$el.clientHeight

            let editorWidth = clientWidth * this.compact
            let editorHeight = clientHeight * this.compact

            if (width / height > clientWidth / clientHeight) {
                editorHeight = height / width * editorWidth
            } else {
                editorWidth = width / height * editorHeight
            }

            const zoom = editorWidth / stickerWidth

            const options = {
                variant: this.variant,
                width: clientWidth,
                height: clientHeight,
                stickerWidth: stickerWidth,
                stickerHeight: stickerHeight,
                unit: this.unit,
                zoom: zoom,
                jsonData: this.jsonData,
                name: this.$t('New Sticker'),
                hasBorders: false,
            }

            if (this.options?.stickerOutlineWidth) {
                options.stickerOutlineWidth = convertDimension(this.options.stickerOutlineWidth, 'px', this.variant.unit)
            }

            if (this.options?.stickerOutlineColor) {
                options.stickerOutlineColor = this.options.stickerOutlineColor
            }

            new StickerCanvas(this.$el, options).ready.then((canvas) => {
                this.$emit('initialized', canvas)
                eventBus.$emit(eventBus.CANVAS_LOADED)
            })
        }
    }
})
</script>

<template>
    <canvas id="sk-canvas" class="w-full h-full"/>
</template>
