<script>
import {defineComponent} from "vue";
import Modal from "@/Builder/Modals/Modal.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import {Sketch} from "@lk77/vue3-color";
import stickerMixin from "@/Builder/Mixins/stickerMixin";
import ToggleInput from "@/Builder/Components/ToggleInput.vue";
import {getDieCutPath} from "@/Builder/Apis/builderApi";
import eventBus from "@/Builder/Utils/eventBus";
import {CUT_LINES} from "@/Builder/Utils/constants";

export default defineComponent({
    name: "BackgroundSettingModal",
    components: {Sketch, CloseIcon, Modal, ToggleInput},
    mixins: [stickerMixin],
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        data: {
            type: [Object, null],
            default: null,
        },
    },
    data() {
        return {
            margin: 0.05,
            marginChanged: false,
            backgroundColor: "white",
        }
    },
    mounted() {
        if (_stickerCanvasEditor) {
            this.initialize(_stickerCanvasEditor)
        }
        eventBus.$on(eventBus.CANVAS_INITIALIZED, this.initialize)
        eventBus.$on(eventBus.STICKER_CLEARED, () => {
            this.margin = 0.05
            this.marginChanged = false
            this.cutLine = CUT_LINES.dieCut
            this.backgroundColor = "white"
            this.clearCanvas()
        })
    },
    unmounted() {
        eventBus.$off(eventBus.CANVAS_INITIALIZED)
        eventBus.$off(eventBus.STICKER_CLEARED)
    },
    watch: {
        open: {
            immediate: true,
            handler() {
            },
        },
        backgroundColor(newColor) {
            const rgbaColor = newColor.rgba

            if (rgbaColor && typeof rgbaColor === 'object') {
                this.backgroundColor = `rgba(${rgbaColor.r ?? 0},${rgbaColor.g ?? 0},${rgbaColor.b ?? 0},${rgbaColor.a ?? 1})`
                _stickerCanvasEditor.setBgColor(this.backgroundColor)
            }
        },
    },
    methods: {
        initialize(canvas) {
            this.margin = canvas.getMargin()
            this.backgroundColor = canvas.getBgColor()
            canvas.on({
                'canvas:cleared': () => {
                    this.margin = _stickerCanvasEditor.getMargin()
                    this.cutLine = _stickerCanvasEditor.cutLineType
                },
            })
        },
        clearCanvas() {
            _stickerCanvasEditor.setCutLineType(CUT_LINES.dieCut)
            _stickerCanvasEditor.setBgColor("white")
            _stickerCanvasEditor.setMargin(0.05)
        },
        confirmMargin() {
            _stickerCanvasEditor.setMargin(this.margin)
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
        handleClear() {
            _stickerCanvasEditor?.clearCanvas()
            eventBus.$emit(eventBus.STICKER_CLEARED)
            this.$emit('close')
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')">
        <div class="w-full h-full flex flex-col justify-end overflow-y-auto tiny-scroll-bar">
            <div class="rounded bg-white flex flex-col h-max w-full">
                <div class="flex justify-between items-center relative px-4 py-2">
                    <h6 class="text-lg font-bold">{{ $t('Settings') }}</h6>
                    <div class="cursor-pointer" @click="$emit('close')">
                        <close-icon/>
                    </div>
                </div>
                <hr>
                <div class="py-2 px-6">
                    <div class="w-full">
                        <div class="flex items-center justify-between text-xs">
                            <label>Margin: {{ margin }} {{ variant.unit }}</label>
                        </div>
                        <input v-model="margin" type="range" min="0.05" max="0.5" step="0.01" class="w-full" @input="handleMarginChange" @change="confirmMargin"/>
                    </div>
                    <div class="mt-2 w-full">
                        <div class="flex items-center justify-start text-xs">{{ $t('Background') }} {{ $t('Color') }}</div>
                        <Sketch v-model="backgroundColor" class="custom-color-picker"/>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs">{{ $t('Show ArtBoard Outline') }}</span>
                        <toggle-input v-model="showArtBoardOutline"/>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs">{{ $t('Show Image Outline') }}</span>
                        <toggle-input v-model="showImageOutline"/>
                    </div>

                    <div class="flex justify-end mt-4">
                        <button class="btn-builder btn-sm w-full" @click="handleClear">
                            Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
