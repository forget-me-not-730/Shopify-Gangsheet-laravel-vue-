<script>
import {defineComponent} from 'vue'
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";

export default defineComponent({
    name: "ImageCounter",
    mixins: [gangSheetSettingMixin],
    props: {
        url: {
            type: [String, undefined, null],
            default: null
        },
        canva_id: {
            type: [String, Number],
            default: null
        }
    },
    data() {
        return {
            imageCount: 0
        }
    },
    watch: {
        url() {
            this.updateUsedImageCount()
        }
    },
    methods: {
        initializeSettings(canvas) {
            this.updateUsedImageCount()
            canvas.on({
                'object:added': this.updateUsedImageCount,
                'object:removed': this.updateUsedImageCount,
                'object:autofill': this.updateUsedImageCount,
                'image:update': this.updateUsedImageCount
            })
        },
        updateUsedImageCount() {
            if (_gangSheetCanvasEditor) {
                if (typeof this.url === 'string' && this.url.startsWith('http')) {
                    const imageUrl = this.url.trim().replace(/\\/g, '/');

                    this.imageCount = _gangSheetCanvasEditor._objects.reduce((count, obj) => {
                        if (obj.type === 'image') {
                            let imgSrc = obj.getSourceUrl().trim().replace(/\\/g, '/');

                            if (imgSrc) {
                                if (imgSrc === imageUrl) {
                                    count++
                                }
                            }
                        }

                        return count
                    }, 0)
                }

                if (this.canva_id) {
                    this.imageCount = _gangSheetCanvasEditor.getObjects('image').reduce((count, obj) => {
                        if (obj.canvaId === this.canva_id) {
                            count++
                        }

                        return count
                    }, 0)
                }
            } else {
                this.imageCount = 0
            }
        },
    }
})
</script>

<template>
    <span>
        {{ imageCount }}
    </span>
</template>

<style scoped>

</style>
