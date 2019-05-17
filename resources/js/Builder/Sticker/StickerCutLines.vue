<script>
import {defineComponent} from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiEllipse, mdiRectangle, mdiSquareRounded} from '@mdi/js'
import {CUT_LINES} from '@/Builder/Utils/constants'
import {getDieCutPath} from '@/Builder/Apis/builderApi'
import eventBus from '@/Builder/Utils/eventBus'
import builderMixin from '@/Builder/Mixins/builderMixin'
import {Sketch} from "@lk77/vue3-color";
import {convertDimension} from "@/Builder/Utils/helpers";

export default defineComponent({
    name: 'StickerCutLines',
    mixins: [builderMixin],
    components: {Sketch, SvgIcon},
    data() {
        return {
            margin: 0.05,
            marginChanged: false,
            cutLines: CUT_LINES,
            backgroundColor: "white",
            mdiSquareRounded,
            mdiRectangle,
            mdiEllipse
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
            this.cutLineType = CUT_LINES.dieCut
            this.backgroundColor = "white"
            this.clearCanvas()
        })
    },
    unmounted() {
        eventBus.$off(eventBus.CANVAS_INITIALIZED)
        eventBus.$off(eventBus.STICKER_CLEARED)
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
        initialize(canvas) {
            this.margin = canvas.getMargin()
            this.backgroundColor = canvas.getBgColor()
            canvas.on({
                'canvas:cleared': () => {
                    this.margin = _stickerCanvasEditor.getMargin()
                    this.cutLineType = _stickerCanvasEditor.cutLineType
                },
            })
        },
        clearCanvas() {
            _stickerCanvasEditor.setCutLineType(CUT_LINES.dieCut)
            _stickerCanvasEditor.setBgColor("white")
            _stickerCanvasEditor.setMargin(0.05)
        },
        async handleCutLineClick(cutLine) {
            this.cutLineType = cutLine
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
    }
})
</script>

<template>
    <div>
        <div class="w-full p-1">
            <h5 class="text-xs">Cut Line</h5>
            <div class="grid grid-cols-4 mt-2 gap-1">
                <div
                    class="aspect-square shadow p-1 flex flex-center-col rounded border border-transparent hover:gs-border-primary cursor-pointer"
                    :class="[cutLineType === cutLines.dieCut ? 'gs-bg-primary text-gray-200': '']"
                    @click="handleCutLineClick(cutLines.dieCut)"
                >
                    <img src="/assets/icons/Image-Diecut.png" alt="" class="h-8 w-8 object-contain" :class="{ 'invert': cutLineType === cutLines.dieCut}">
                    <span class="text-[9px]">Die-cut</span>
                </div>
                <div
                    class="aspect-square shadow p-1 flex flex-center-col rounded border border-transparent hover:gs-border-primary cursor-pointer"
                    :class="[cutLineType === cutLines.rectCut ? 'gs-bg-primary text-gray-200': '']"
                    @click="handleCutLineClick(cutLines.rectCut)"
                >
                    <div class="h-8 w-8 flex items-center justify-center">
                        <svg-icon type="mdi" :path="mdiRectangle" size="36"/>
                    </div>
                    <span class="text-[9px]">Rect</span>
                </div>
                <div
                    class="aspect-square shadow p-1 flex flex-center-col rounded border border-transparent hover:gs-border-primary cursor-pointer"
                    :class="[cutLineType === cutLines.ellipseCut ? 'gs-bg-primary text-gray-200': '']"
                    @click="handleCutLineClick(cutLines.ellipseCut)"
                >
                    <div class="h-8 w-8 flex items-center justify-center">
                        <svg-icon type="mdi" :path="mdiEllipse" size="36"/>
                    </div>
                    <span class="text-[9px]">Ellipsis</span>
                </div>
                <div
                    class="aspect-square shadow p-1 flex flex-center-col rounded border border-transparent hover:gs-border-primary cursor-pointer"
                    :class="[cutLineType === cutLines.roundedCut ? 'gs-bg-primary text-gray-200': '']"
                    @click="handleCutLineClick(cutLines.roundedCut)"
                >
                    <div class="h-8 w-8 flex items-center justify-center">
                        <svg-icon type="mdi" :path="mdiSquareRounded" size="36"/>
                    </div>
                    <span class="text-[9px]">Rounded</span>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <div class="w-full p-1">
                <div class="flex items-center justify-between h-8 text-xs">
                    <label>Margin: {{ margin }} {{ variant.unit }}</label>
                </div>
                <input v-model="margin" type="range" min="0.05" max="0.5" step="0.01" class="w-full" @input="handleMarginChange" @change="confirmMargin"/>
            </div>
            <div class="mt-2 w-full p-1">
                <div class="text-xs pb-2">{{ $t('Background') }} {{ $t('Color') }}</div>
                <Sketch v-model="backgroundColor" class="custom-color-picker"/>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
