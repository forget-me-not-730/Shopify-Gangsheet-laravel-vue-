<template>
    <div class="w-full h-full relative">
        <div v-if="hasActiveObject && !autoNestMode" class="text-xs pb-2 pt-2">
            <div v-if="type === 'i-text'" class="space-y-1">
                <input v-model="text"
                       @input="handleTextChange"
                       class="w-full hidden items-center rounded-md border-0 pr-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:sm:leading-6">

                <gbs-select v-model="fontFamily" :options="fontNames" :search="true">
                    <template #selected="{selected}">
                        <span class="text-xs" :style="{fontFamily: selected}">{{ selected }}</span>
                    </template>
                    <template #option="{option}">
                        <span class="text-xs" :style="{fontFamily: option}">{{ option }}</span>
                    </template>
                </gbs-select>

                <div class="flex items-center justify-between">
                    <div class="flex space-x-1">
                        <button
                            :disabled="!selectedFont.bold"
                            :class="{'bg-black text-white': this.bold}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer disabled:text-gray-300 disabled:cursor-not-allowed"
                            @click="toggleFormatBold">
                            <svg-icon type="mdi" :path="mdiFormatBold" size="16"/>
                        </button>
                        <button
                            :disabled="!selectedFont.italic"
                            :class="{'bg-black text-white': this.italic}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer disabled:text-gray-300 disabled:cursor-not-allowed"
                            @click="toggleFormatItalic">
                            <svg-icon type="mdi" :path="mdiFormatItalic" size="16"/>
                        </button>
                        <div
                            :class="{'bg-black text-white': this.underline}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer"
                            @click="toggleFormatUnderline">
                            <svg-icon type="mdi" :path="mdiFormatUnderline" size="16"/>
                        </div>

                        <button
                            :class="{'bg-black text-white': this.alignLeft}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer"
                            @click="toggleAlign('left')">
                            <svg-icon type="mdi" :path="mdiFormatAlignLeft" size="16"/>
                        </button>
                        <button
                            :class="{'bg-black text-white': this.alignCenter}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer"
                            @click="toggleAlign('center')">
                            <svg-icon type="mdi" :path="mdiFormatAlignCenter" size="16"/>
                        </button>
                        <button
                            :class="{'bg-black text-white': this.alignRight}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer"
                            @click="toggleAlign('right')">
                            <svg-icon type="mdi" :path="mdiFormatAlignRight" size="16"/>
                        </button>
                    </div>
                </div>

                <div class="flex items-center">
                    <span class="w-28">{{ $t('Stroke Width') }}: </span>
                    <div class="flex-1">
                        <input v-model="strokeWidth" type="range" @input="handleStrokeWidthChange" :min="0" :max="10" :step="1" class="w-full"/>
                    </div>
                </div>
            </div>

            <div class="font-bold mt-3 hidden">{{ $t('Dimensions') }}</div>
            <div class="space-y-2 mt-3">
                <div class="flex items-center">
                    <label class="w-32 shrink-0">{{ $t('Width') }}: </label>
                    <div
                        class="w-full flex items-center rounded-md border-0 pr-2 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:sm:leading-6">
                        <input type="number" v-model="width" @input="handleInputWidth" min="0.2" step="0.2"
                               class="w-full !text-sm block border-0 bg-transparent py-1.5 placeholder:text-gray-400 focus:ring-0 sm:sm:leading-6">
                        {{ artBoardUnit }}
                    </div>
                </div>
                <div class="flex items-center">
                    <label class="w-32 shrink-0">{{ $t('Height') }}: </label>
                    <div
                        class="w-full flex items-center rounded-md border-0 pr-2 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:sm:leading-6">
                        <input type="number" v-model="height" @input="handleInputHeight" min="0.2" step="0.2"
                               class="w-full !text-sm block border-0 py-1.5 bg-transparent placeholder:text-gray-400 focus:ring-0 sm:sm:leading-6">
                        {{ artBoardUnit }}
                    </div>
                </div>

                <div class="grid grid-cols-2 text-xs">
                    <div
                        :class="{'bg-gray-100': colorChange === 'fillColor'}"
                        class="h-8 border border-r-0 flex items-center justify-center cursor-pointer"
                        @click="toggleColorPicker($event,'fillColor')">
                        <span class="w-4 h-4 border mr-1" :style="{backgroundColor: fillColor}"></span>
                        {{ $t('Text Color') }}
                    </div>
                    <div
                        :class="{'bg-gray-100': colorChange === 'strokeColor'}"
                        class="h-8 border flex items-center justify-center cursor-pointer"
                        @click="toggleColorPicker($event,'strokeColor')">
                        <span class="w-4 h-4 border mr-1" :style="{backgroundColor: strokeColor}"></span>
                        {{ $t('Stroke') }}
                    </div>
                </div>
            </div>

            <div v-if="colorChange">
                <div class="flex items-center mt-3">
                    <span class="pt-1 w-32 shrink-0">{{ colorChange == 'fillColor' ? $t('Text Color') : $t('Stroke Color') }}:</span>
                    <input class="inp-builder w-full flex items-center !text-sm rounded-md py-1.5 bg-transparent" type="text" v-model="activeColor" placeholder="Select a Color"/>
                </div>
                <div class="w-full border border-gray-300 rounded-lg p-1 mt-3">
                    <Sketch :model-value="activeColor" @update:modelValue="handleUpdateColor($event)" class="custom-color-picker disable-alpha" :disable-alpha="true"/>
                </div>
            </div>
        </div>
        <div v-else class="flex items-center justify-center flex-col pt-8">
            <svg xmlns="http://www.w3.org/2000/svg" width="170" height="167" viewBox="0 0 170 167" fill="none"
                 class="ml-8 w-20 sm:w-44 h-20 sm:h-44">
                <rect x="1" y="1" width="129" height="129" rx="4" stroke="#E7E7EC" stroke-width="2"/>
                <path
                    d="M97.0143 94.0143L116.054 145.083L121.845 124.336L137.737 140.227L143.227 134.737L127.336 118.845L148.083 113.054L97.0143 94.0143Z"
                    stroke="#E7E7EC" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p class="text-sm sm:text-base">{{ $t('Select an object to edit.') }}</p>
        </div>
    </div>
</template>

<script>
import builderMixin from '@/Builder/Mixins/builderMixin'
import eventBus from '@/Builder/Utils/eventBus'
import Spinner from '@/Components/Spinner.vue'
import GbsSelect from '@/Components/Select.vue'
import {Sketch, Compact} from '@lk77/vue3-color'
import SvgIcon from '@jamescoyle/vue-icon'
import {
    mdiFormatBold,
    mdiFormatItalic,
    mdiFormatUnderline,
    mdiFormatAlignLeft,
    mdiFormatAlignCenter,
    mdiFormatAlignRight,
    mdiInformationOutline,
    mdiLock,
    mdiLockOpenVariant
} from '@mdi/js'

export default {
    name: 'FontSettingPanel',
    components: {GbsSelect, Spinner, Sketch, SvgIcon, Compact},
    mixins: [builderMixin],
    data() {
        return {
            canvas: null,
            hasActiveObject: false,
            width: 0,
            height: 0,

            keepAspectRatioEnabled: true,

            // for removing background
            type: null,

            // for text settings
            text: '',
            fillColor: '#000000',
            strokeColor: '#375AA2',

            fontFamily: 'Oswald',
            colorChange: null,
            strokeWidth: 0,
            bold: false,
            italic: false,
            underline: false,
            alignLeft: false,
            alignCenter: false,
            alignRight: false,

            mdiFormatBold,
            mdiFormatItalic,
            mdiFormatUnderline,
            mdiFormatAlignLeft,
            mdiFormatAlignCenter,
            mdiFormatAlignRight,
            mdiInformationOutline,
            mdiLockOpenVariant,
            mdiLock
        }
    },
    computed: {
        maxAllowedFiles() {
            return Number(this.variant?.maxAllowedFileCount || -1)
        },
        dupeAvailable() {
            return this.maxAllowedFiles === -1 || this.maxAllowedFiles > 1
        },
        activeColor() {
            if (!this.colorChange) {
                return null
            }
            const color = this[this.colorChange]
            return color?.startsWith('#') ? color : '#000000'
        },
        fontNames() {
            return this.shopFonts.map(font => font.name)
        },
        selectedFont() {
            return this.shopFonts.find(font => font.name === this.fontFamily)
        },
        customTextColors() {
            return this.shopSettings.customTextColors.filter(c => c && this.shopSettings.useCustomTextColors);
        }
    },
    watch: {
        fontFamily() {
            this.handleFontFamilyChange()
        },
    },
    mounted() {
        if (window._stickerCanvasEditor) {
            this.addEventListeners(window._stickerCanvasEditor)
        }
        eventBus.$on(eventBus.CANVAS_INITIALIZED, this.addEventListeners)
    },
    unmounted() {
        eventBus.$off(eventBus.CANVAS_INITIALIZED, this.addEventListeners)
    },
    methods: {
        addEventListeners(canvas) {
            const listener = () => {
                this.updateDimensions(canvas)
            }
            listener()
            canvas.on({
                'selection:created': listener,
                'selection:updated': listener,
                'selection:cleared': listener,
                'object:removed': listener,
                'object:scaling': listener
            })
        },
        updateDimensions(canvas) {
            const activeObject = canvas.getActiveObject()
            if (activeObject) {
                this.type = activeObject.type

                if (activeObject.type === 'i-text') {
                    this.text = activeObject.text
                    this.colorChange = null
                    this.fillColor = activeObject.fill
                    this.strokeColor = activeObject.stroke
                    this.strokeWidth = activeObject.strokeWidth
                    this.underline = activeObject.underline
                    this.alignLeft = activeObject.textAlign === 'left';
                    this.alignCenter = activeObject.textAlign === 'center';
                    this.alignRight = activeObject.textAlign === 'right';
                    this.fontFamily = activeObject.fontFamily

                    if (this.selectedFont.bold) {
                        this.bold = activeObject.fontWeight === 'bold'
                    } else {
                        this.bold = false
                        activeObject.set('fontWeight', 'normal')
                    }

                    if (this.selectedFont.italic) {
                        this.italic = activeObject.fontStyle === 'italic'
                    } else {
                        this.italic = false
                        activeObject.set('fontStyle', 'normal')
                    }
                }

                this.hasActiveObject = true
                this.keepAspectRatioEnabled = activeObject.get('rockedRatio')
                const {width, height} = activeObject.getDimensions()
                this.width = width.toFixed(2)
                this.height = height.toFixed(2)
            } else {
                this.hasActiveObject = false
            }
        },
        handleInputWidth(e) {
            const canvas = _stickerCanvasEditor
            const width = Number(e.target.value)
            const activeObject = canvas.getActiveObject()
            if (activeObject && !isNaN(width) && width > 0) {
                const rect1 = activeObject.getRect()

                const scaleRatio = width / activeObject.getDimensions().width
                activeObject.set('scaleX', activeObject.scaleX * scaleRatio)
                if (this.keepAspectRatioEnabled) {
                    activeObject.set('scaleY', activeObject.scaleY * scaleRatio)
                    this.height = (this.height * scaleRatio).toFixed(2)
                }

                const rect2 = activeObject.getRect()
                activeObject.set({
                    left: activeObject.left - (rect2.left - rect1.left),
                    top: activeObject.top - (rect2.top - rect1.top)
                })
                canvas.renderAll()
            }
        },
        handleInputHeight(e) {
            const canvas = _stickerCanvasEditor
            const activeObject = canvas.getActiveObject()
            const height = Number(e.target.value)
            if (activeObject && !isNaN(height) && height > 0) {
                const rect1 = activeObject.getRect()

                const scaleRatio = height / activeObject.getDimensions().height
                activeObject.set('scaleY', activeObject.scaleY * scaleRatio)
                if (this.keepAspectRatioEnabled) {
                    activeObject.set('scaleX', activeObject.scaleX * scaleRatio)
                    this.width = (this.width * scaleRatio).toFixed(2)
                }

                const rect2 = activeObject.getRect()
                activeObject.set({
                    left: activeObject.left - (rect2.left - rect1.left),
                    top: activeObject.top - (rect2.top - rect1.top)
                })
                canvas.renderAll()
            }
        },
        handleKeepAspectRatio() {
            const canvas = _stickerCanvasEditor
            this.keepAspectRatioEnabled = !this.keepAspectRatioEnabled
            canvas.rockSelectionRatio(this.keepAspectRatioEnabled)
            canvas.renderAll()
        },
        handleFontFamilyChange() {
            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                selected.reInitDimensions(this.fontFamily)
                this.reloadSizeValue(selected)
                _stickerCanvasEditor.renderAll()
            }
        },
        reloadSizeValue(selected) {
            const {width, height} = selected.getDimensions()
            this.width = width.toFixed(2)
            this.height = height.toFixed(2)
        },
        handleTextChange(e) {
            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('text', e.target.value)
                _stickerCanvasEditor.renderAll()
            }
        },
        handleUpdateColor(color) {
            this[this.colorChange] = color.hex
            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                selected.set({
                    fill: this.fillColor,
                    stroke: this.strokeColor,
                })
                _stickerCanvasEditor.renderAll()
            }
        },
        handleOutsideClick() {
            this.colorChange = null
        },
        handleStrokeWidthChange(e) {
            const value = Number(e.target.value)
            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('strokeWidth', value)
                _stickerCanvasEditor.renderAll()
            }
        },
        toggleFormatBold() {
            this.bold = !this.bold
            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                if (this.bold) {
                    selected.set('fontWeight', 'bold')
                } else {
                    selected.set('fontWeight', 'normal')
                }

                _stickerCanvasEditor.renderAll()
            }
        },
        toggleFormatItalic() {
            this.italic = !this.italic
            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                if (this.italic) {
                    selected.set('fontStyle', 'italic')
                } else {
                    selected.set('fontStyle', 'normal')
                }
                _stickerCanvasEditor.renderAll()
            }
        },
        toggleFormatUnderline() {
            this.underline = !this.underline

            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('underline', this.underline)
                _stickerCanvasEditor.renderAll()
            }
        },
        toggleAlign(alignment) {
            this.alignLeft = alignment === 'left';
            this.alignCenter = alignment === 'center';
            this.alignRight = alignment === 'right';

            const selected = _stickerCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('textAlign', alignment);
                _stickerCanvasEditor.renderAll();
            }
        },
        toggleColorPicker(e, picker) {
            e.preventDefault()
            e.stopImmediatePropagation()

            if (this.colorChange === picker) {
                this.colorChange = null
            } else {
                this.colorChange = picker
            }
        }
    }
}
</script>

<style>
.vc-sketch {
    width: 100% !important
}
.font-color-picker {
    width: 152px !important;
}
</style>
