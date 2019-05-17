<script>
import {defineComponent} from 'vue'
import Variants from '@/Builder/Components/Variants.vue'
import StickerCutLines from './StickerCutLines.vue'
import stickerMixin from '@/Builder/Mixins/stickerMixin'
import eventBus from '@/Builder/Utils/eventBus'
import ToggleInput from "@/Builder/Components/ToggleInput.vue";
export default defineComponent({
    name: 'StickerRightBar',
    components: {StickerCutLines, Variants, ToggleInput},
    mixins: [stickerMixin],
    computed: {
        variantDisabled() {
            return Boolean(this.order)
        }
    },
    watch: {
        showImageOutline(value) {
            _stickerCanvasEditor.setShowImageOutline(value)
            _stickerCanvasEditor.renderAll()
        }
    },
    methods: {
        handleSizeChange(variantId) {
            this.updateStickerCanvasData()

            const variant = this.variants.find(variant => variant.id.toString() === variantId.toString())

            if (variant) {
                this.variant = variant
                if (_stickerCanvasEditor) {
                    if (this.editMode && _stickerCanvasEditor.designId) {
                        this.oldDesignId = _stickerCanvasEditor.designId
                        _stickerCanvasEditor.setDesignId(null)
                    } else {
                        let url = new URL(window.location.href)
                        const params = url.searchParams
                        params.set('variant', variant.id)
                        history.pushState(null, '', `${url.pathname}?${params.toString()}`)
                    }
                    _stickerCanvasEditor.setVariant(variant)
                }
                this.variantUpdated = true
            }
        },
        handleClear() {
            _stickerCanvasEditor?.clearCanvas()
            eventBus.$emit(eventBus.STICKER_CLEARED)
        }
    }
})
</script>

<template>
    <div class="p-4 w-[280px] shrink-0 hidden sm:flex flex-col bg-white rounded border border-gray-200">
        <h5 class="text-xs p-1">Size</h5>
        <div class="h-8 w-full mt-1">
            <variants v-model="variant.id" @change="handleSizeChange" :disabled="variantDisabled"/>
        </div>

        <div class="w-full mt-4">
            <sticker-cut-lines/>
        </div>

        <div class="w-full mt-4">
            <div class="flex items-center justify-between">
                <span class="text-xs">{{ $t('Show ArtBoard Outline') }}</span>
                <toggle-input v-model="showArtBoardOutline"/>
            </div>
        </div>

        <div class="w-full mt-4">
            <div class="flex items-center justify-between">
                <span class="text-xs">{{ $t('Show Image Outline') }}</span>
                <toggle-input v-model="showImageOutline"/>
            </div>
        </div>

        <div class="flex-1"></div>

        <button class="btn-builder" @click="handleClear">
            Clear
        </button>
    </div>
</template>

<style scoped>

</style>
