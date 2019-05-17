<script>
import {defineComponent} from 'vue'
import stickerMixin from "@/Builder/Mixins/stickerMixin";
import {CUT_LINES, MODAL_NAMES, TOOLS} from "@/Builder/Utils/constants";
import builderMixin from "@/Builder/Mixins/builderMixin";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiEllipse, mdiFormatText, mdiImage, mdiPencil, mdiRectangle, mdiSquareRounded, mdiPalette} from "@mdi/js";
import {getDieCutPath} from "@/Builder/Apis/builderApi";
import Variants from "@/Builder/Components/Variants.vue";
import eventBus from "@/Builder/Utils/eventBus";
import FontSettingPanel from "@/Builder/Sticker/FontSettingPanel.vue";
import {Sketch} from "@lk77/vue3-color";

export default defineComponent({
    name: "StickerMobileBar",
    components: {Sketch, FontSettingPanel, Variants, SvgIcon},
    mixins: [stickerMixin, builderMixin],
    data() {
        return {
            margin: 0.05,
            hasBackground: false,
            marginChanged: false,
            tools: TOOLS,
            cutLine: CUT_LINES.dieCut,
            cutLines: CUT_LINES,
            backgroundColor: "white",
            mdiRectangle,
            mdiSquareRounded,
            mdiEllipse,
            mdiFormatText,
            mdiImage,
            mdiPencil,
            mdiPalette
        }
    },
    computed: {
        variantDisabled() {
            return Boolean(this.order)
        }
    },
    mounted: function () {
        this.tool = this.tools.main
        this.checkCanvasBackground()
        eventBus.$on(eventBus.STICKER_EDIT, () => {
            this.hasBackground = true
        })
        eventBus.$on(eventBus.STICKER_CLEARED, () => {
            this.hasBackground = false
            this.tool = this.tools.main
        })
    },
    watch: {
        backgroundColor(newColor) {
            const rgbaColor = newColor.rgba

            if (rgbaColor && typeof rgbaColor === 'object') {
                this.backgroundColor = `rgba(${rgbaColor.r ?? 0},${rgbaColor.g ?? 0},${rgbaColor.b ?? 0},${rgbaColor.a ?? 1})`
                _stickerCanvasEditor.setBgColor(this.backgroundColor)
            }
        },
    },
    methods: {
        async handleCutLineClick(cutLine) {
            this.cutLine = cutLine
            _stickerCanvasEditor.setCutLineType(cutLine)
        },
        confirmMargin() {
            if (_stickerCanvasEditor.backgroundSrc) {
                this.loadingDesign = true
                this.marginChanged = false

                getDieCutPath({
                    image_url: _stickerCanvasEditor.backgroundSrc,
                    outline: _stickerCanvasEditor.getDieCutMargin()
                }).then((paths) => {
                    _stickerCanvasEditor.setDieCutPath(paths)
                }).finally(() => {
                    this.loadingDesign = false
                })
            }
        },
        handleMarginChange() {
            this.marginChanged = _stickerCanvasEditor.getMargin() !== Number(this.margin)
            _stickerCanvasEditor.setMargin(this.margin)
        },
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
        handleImageClick() {
            this.tool = this.tools.main
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.SELECT_IMAGE
            })
        },
        handleEditClick() {
            this.tool = this.tools.edit
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.IMAGE_EDIT
            })
        },
        handleTextClick() {
            this.tool = this.tools.text
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.TEXT_EDIT
            })
        },
        handleBackgroundClick() {
            this.tool = this.tools.settings
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.BACKGROUND_SETTINGS
            })
        },
        handleAddText() {
            if (_stickerCanvasEditor) {
                _stickerCanvasEditor.addText('Sticker', this.defaultFont)
                this.$emit('close')
            }
        },
        checkCanvasBackground() {
            if (this.currentSticker) {
                this.hasBackground = true
            }
        },
    }
})
</script>

<template>
    <div class="flex sm:hidden w-full flex-col justify-between">
        <div class="w-full p-2 flex flex-col gap-2">
            <div class="bg-white p-2 rounded space-y-2 border border-gray-300">
                <div class="flex gap-2">
                    <div class="h-8 w-1/2">
                        <variants v-model="variant.id" @change="handleSizeChange" :disabled="variantDisabled"/>
                    </div>
                    <div class="w-1/2 grid grid-cols-4 border border-gray-300 rounded">
                        <div
                            class="flex justify-center items-center border border-transparent hover:gs-border-primary cursor-pointer"
                            :class="[cutLine === cutLines.dieCut ? 'gs-bg-primary text-white': '']"
                            @click="handleCutLineClick(cutLines.dieCut)"
                        >
                            <img src="/assets/icons/Image-Diecut.png" alt="" class="h-6 w-6 object-contain">
                        </div>
                        <div
                            class="flex justify-center items-center border border-transparent hover:gs-border-primary cursor-pointer"
                            :class="[cutLine === cutLines.rectCut ? 'gs-bg-primary text-white': '']"
                            @click="handleCutLineClick(cutLines.rectCut)"
                        >
                            <div class="h-6 w-6 flex items-center justify-center">
                                <svg-icon type="mdi" :path="mdiRectangle" size="18"/>
                            </div>
                        </div>
                        <div
                            class="flex justify-center items-center border border-transparent hover:gs-border-primary cursor-pointer"
                            :class="[cutLine === cutLines.ellipseCut ? 'gs-bg-primary text-white': '']"
                            @click="handleCutLineClick(cutLines.ellipseCut)"
                        >
                            <div class="h-6 w-6 flex items-center justify-center">
                                <svg-icon type="mdi" :path="mdiEllipse" size="18"/>
                            </div>
                        </div>
                        <div
                            class="flex justify-center items-center border border-transparent hover:gs-border-primary cursor-pointer"
                            :class="[cutLine === cutLines.roundedCut ? 'gs-bg-primary text-white': '']"
                            @click="handleCutLineClick(cutLines.roundedCut)"
                        >
                            <div class="h-6 w-6 flex items-center justify-center">
                                <svg-icon type="mdi" :path="mdiSquareRounded" size="18"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full absolute bottom-0 bg-white flex items-center justify-around shadow z-10 border-t">
            <div class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r"
                 :class="{'gs-bg-primary border-l-gray-300  border-r-gray-300': tool === this.tools.main}"
                 @click="handleImageClick"
            >
                <svg-icon type="mdi" :path="mdiImage" size="18"/>
                <span class="text-xs">Image</span>
            </div>
            <div class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r"
                 :class="[tool === this.tools.text ? 'gs-bg-primary border-l-gray-300  border-r-gray-300': '', { 'text-gray-400': !hasBackground }]"
                 @click="hasBackground ? handleTextClick() : null"
            >
                <svg-icon type="mdi" :path="mdiFormatText" size="18" class="mr-1"/>
                <span class="text-xs">Text</span>
            </div>
            <div class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r"
                 :class="[tool === this.tools.edit ? 'gs-bg-primary border-l-gray-300  border-r-gray-300': '', { 'text-gray-400': !hasBackground }]"
                 @click="hasBackground ? handleEditClick() : null"
            >
                <svg-icon type="mdi" :path="mdiPencil" size="18"/>
                <span class="text-xs">Edit</span>
            </div>
            <div class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r"
                 :class="[tool === this.tools.settings ? 'gs-bg-primary border-l-gray-300  border-r-gray-300': '', { 'text-gray-400': !hasBackground }]"
                 @click="hasBackground ? handleBackgroundClick() : null"
            >
                <svg-icon type="mdi" :path="mdiPalette" size="18"/>
                <span class="text-xs">Settings</span>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
