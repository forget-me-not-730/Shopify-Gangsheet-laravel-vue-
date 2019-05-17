<template>
    <div class="w-full h-full relative">
        <div v-if="hasActiveObject && !autoNestMode" class="text-xs pb-2 pt-2">
            <div class="text-xs font-thin space-x-1 flex justify-center">
                <button v-if="showRemoveBackgroundButton"
                        @click="openEditImageModal"
                        class="btn-builder capitalize w-40 rounded py-1 px-1 disabled:bg-gray-400">
                    {{ $t('Remove Background') }}
                </button>
                <button v-if="showCropImageButton"
                        @click="openCropImageModal"
                        class="btn-builder capitalize w-40 rounded py-1 px-1 disabled:bg-gray-400">
                    {{ $t('Crop') }}
                </button>
            </div>

            <div v-if="type === 'i-text'">
                <gs-select v-model="fontFamily" :options="fontFamilies" @input="handleFontFamilyChange" :search="true"/>

                <div class="flex items-center justify-between mt-2">
                    <div class="flex space-x-1">
                        <button
                            :disabled="!selectedFont?.bold"
                            :class="{'gs-bg-primary': this.bold}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer disabled:text-gray-300 disabled:cursor-not-allowed"
                            @click="toggleFormatBold">
                            <format-bold-icon size="16"/>
                        </button>
                        <button
                            :disabled="!selectedFont?.italic"
                            :class="{'gs-bg-primary': this.italic}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer disabled:text-gray-300 disabled:cursor-not-allowed"
                            @click="toggleFormatItalic">
                            <format-italic-icon size="16"/>
                        </button>
                        <button
                            :class="{'gs-bg-primary': this.underline}"
                            class="hidden w-8 h-8 border rounded items-center justify-center cursor-pointer"
                            @click="toggleFormatUnderline">
                            <format-underline-icon size="16"/>
                        </button>
                        <button
                            :class="{'gs-bg-primary': this.alignLeft}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer"
                            @click="toggleAlign('left')">
                            <format-align-left-icon size="16"/>
                        </button>
                        <button
                            :class="{'gs-bg-primary': this.alignCenter}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer"
                            @click="toggleAlign('center')">
                            <format-align-center-icon size="16"/>
                        </button>
                        <button
                            :class="{'gs-bg-primary': this.alignRight}"
                            class="w-8 h-8 border rounded flex items-center justify-center cursor-pointer"
                            @click="toggleAlign('right')">
                            <format-align-right-icon size="16"/>
                        </button>
                    </div>
                </div>

                <div v-if="textLineCount > 1" class="flex items-center mt-2">
                    <label class="w-32 shrink-0">{{ $t('Line Space') }}: </label>
                    <div
                        class="w-full flex items-center rounded-md border-0 pr-2 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:sm:leading-6">
                        <input type="number" v-model="lineSpace" @input="handleLineSpaceInput" min="0" step="0.2" @keypress="preventNegative"
                               class="w-full !text-sm block border-0 bg-transparent py-1.5 placeholder:text-gray-400 focus:ring-0 sm:sm:leading-6">
                        {{ artBoardUnit }}
                    </div>
                </div>

                <div class="flex items-center mt-2">
                    <span class="w-28">{{ $t('Stroke Width') }}: </span>
                    <div class="flex-1">
                        <input v-model="strokeWidth" type="range" @input="handleStrokeWidthChange" :min="0" :max="10" :step="0.1" class="w-full"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 text-xs mt-2">
                    <div
                        :class="{'gs-bg-primary': colorChange === 'fillColor'}"
                        class="h-8 border border-r-0 flex items-center justify-center cursor-pointer"
                        @click="toggleColorPicker($event,'fillColor')">
                        <span class="w-4 h-4 border border-gray-300 mr-1" :style="{backgroundColor: fillColor}"></span>
                        {{ $t('Text Color') }}
                    </div>
                    <div
                        :class="{'gs-bg-primary': colorChange === 'strokeColor'}"
                        class="h-8 border flex items-center justify-center cursor-pointer"
                        @click="toggleColorPicker($event,'strokeColor')">
                        <span class="w-4 h-4 border border-gray-300 mr-1" :style="{backgroundColor: strokeColor}"></span>
                        {{ $t('Stroke') }}
                    </div>
                </div>
                <div v-if="colorChange" class="relative flex w-full" :class="{'justify-end': colorChange === 'strokeColor'}">
                    <div v-click-outside="handleOutsideClick" class="absolute top-2 w-full z-50 bg-builder border border-gray-300">
                        <div v-if="customTextColors.length > 0">
                            <Compact :model-value="activeColor" class="font-color-picker"
                                     @update:modelValue="handleUpdateColor" :palette="customTextColors"/>
                        </div>
                        <div v-else>
                            <div class="flex items-center justify-between">
                                <span class="w-full text-xs py-1 px-2 border-r border-r-gray-300 focus:border-r-gray-300">{{ colorChange === 'fillColor' ? $t('Text Color') : $t('Stroke') }}: </span>
                                <input class="w-1/2 text-right text-xs py-1 inp-no-style px-2 uppercase focus:border-r-gray-300" maxlength="7" v-model="activeColor"
                                       @change="correctActiveColor"/>
                            </div>
                            <hr/>
                            <Sketch :model-value="activeColor" class="custom-color-picker disable-alpha"
                                    @update:modelValue="handleUpdateColor" :disable-alpha="true"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full">
                <div class="font-bold mt-3 hidden">{{ $t('Dimensions') }}</div>
                <div class="space-y-2 mt-3">
                    <div class="flex items-center">
                        <label class="w-32 shrink-0">{{ $t('Width') }}: </label>
                        <div class="w-full inp-builder">
                            <input type="number" v-model="width" @input="handleInputWidth" @keypress="preventNegative" min="0.2" step="0.2"
                                   class="inp-no-style w-full py-0.5">
                            {{ artBoardUnit }}
                        </div>
                    </div>
                    <div class="flex items-center">
                        <label class="w-32 shrink-0">{{ $t('Height') }}: </label>
                        <div class="w-full inp-builder">
                            <input type="number" v-model="height" @input="handleInputHeight" @keypress="preventNegative" min="0.2" step="0.2"
                                   class="inp-no-style w-full py-0.5">
                            {{ artBoardUnit }}
                        </div>
                    </div>
                    <div class="flex items-center">
                        <label class="w-32 shrink-0">{{ $t('Aspect Ratio') }}: </label>
                        <div class="w-full inp-builder px-3 py-1 flex items-center justify-between">
                            {{ (width / height).toFixed(2) }}
                            <div class="cursor-pointer" @click="handleKeepAspectRatio">
                                <lock-icon v-if="keepAspectRatioEnabled" size="16"/>
                                <lock-open-variant-icon v-else size="16"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template v-if="dupeAvailable">
                <div class="mt-2">
                    <div class="flex items-center justify-between">
                        <span>{{ $t('Auto Duplicate') }}: </span>
                        <toggle-input v-model="autoPositioning"/>
                    </div>
                    <template v-if="autoPositioning">
                        <div class="flex items-center justify-between mt-2 pl-4">
                            <label>{{ $t('Image Quantity') }}: </label>
                            <input :hidden="isImageGroup" type="number" v-model="imageQuantity" min="0" class="w-32 inp-builder py-1">
                        </div>
                        <div v-if="maxAllowedFiles > 1"
                             class="text-warning text-xs text-right">
                            <information-outline-icon/>
                            {{ $t('Image quantity can be max') }} {{ maxAllowedFiles }}
                        </div>
                        <div class="flex items-center justify-between mt-2 pl-4">
                            <label class="items-center flex" :class="[builderSettings.lockMargin ? 'cursor-not-allowed' : 'cursor-pointer']">
                                <input v-model="marginEnabled" type="checkbox" class="focus:ring-0 rounded-sm w-4 h-4" :disabled="builderSettings.lockMargin">
                                <span class="ml-2">{{ $t('Apply Margin') }}</span>
                            </label>
                            <div v-if="marginEnabled" class="w-32 inp-builder">
                                <input type="number"
                                       v-model="margin"
                                       :step="artBoardUnit === 'mm' ? 1 : 0.1"
                                       min="0"
                                       @input="handleMarginChange"
                                       class="w-full inp-no-style py-0.5"
                                       :disabled="builderSettings.lockMargin">
                                {{ artBoardUnit }}
                            </div>
                        </div>
                    </template>
                    <div class="flex items-center justify-end mt-2">
                        <button class="btn-builder btn-sm" @click="handleDuplicate">
                            {{ $t('Duplicate') }}
                        </button>
                    </div>
                </div>
            </template>

            <template v-if="colorOverlayAvailable">
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span>{{ $t('Change Image Color') }}: </span>
                        <toggle-input v-model="applyOverlayColor"/>
                    </div>
                    <div v-if="applyOverlayColor" class="mt-2">
                        <div class="flex items-center justify-between pr-2 border border-gray-300">
                            <input class="w-20 text-xs py-1 px-2 uppercase inp-no-style border-r border-r-gray-300 focus:border-r-gray-300" maxlength="7" v-model="overlayColor"
                                   @change="correctOverlayColor"/>
                            <div class="flex flex-wrap max-sm:space-y-2 sm:space-x-5 text-xs">
                                <label class="space-x-2 flex items-center cursor-pointer">
                                    <input type="radio" value="blend" v-model="overlayFilter"/>
                                    <span>{{ $t('Blend') }}</span>
                                </label>
                                <label class="space-x-2 flex items-center cursor-pointer">
                                    <input type="radio" value="overlay" v-model="overlayFilter"/>
                                    <span>{{ $t('Overlay') }}</span>
                                </label>
                            </div>
                        </div>

                        <div v-click-outside="handleOutsideClick" class="mt-2 flex w-full justify-end">
                            <Compact v-if="customImageOverlayColors.length > 0" :model-value="overlayColor" class="font-color-picker border border-gray-300"
                                     @update:modelValue="handleUpdateOverlayColor" :palette="customImageOverlayColors"/>
                            <Sketch v-else :model-value="overlayColor" class="custom-color-picker disable-alpha border border-gray-300"
                                    :disable-alpha="true" @update:modelValue="handleUpdateOverlayColor"/>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span>{{ $t('Gamma') }}: </span>
                        <toggle-input v-model="gammaActive"/>
                    </div>
                    <div v-if="gammaActive">
                        <div class="flex items-center justify-between mt-2">
                            <label class="w-20 pl-4">{{ $t('Red') }}: </label>
                            <div class="inp-builder flex-1 flex">
                                <input v-model="gammaRed" type="number" @change="handleGammaImage" min="0.01" max="2.2" step="0.01" class="w-16 py-1 pl-1 inp-no-style">
                                <input v-model="gammaRed" type="range" @change="handleGammaImage" :min="0.01" :max="2.2" :step="0.01" class="flex-1"/>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <label class="w-20 pl-4">{{ $t('Green') }}: </label>
                            <div class="inp-builder flex-1 flex">
                                <input v-model="gammaGreen" type="number" @change="handleGammaImage" min="0.01" max="2.2" step="0.01" class="w-16 py-1 pl-1 inp-no-style">
                                <input v-model="gammaGreen" type="range" @change="handleGammaImage" :min="0.01" :max="2.2" :step="0.01" class="flex-1"/>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <label class="w-20 pl-4">{{ $t('Blue') }}: </label>
                            <div class="inp-builder flex-1 flex">
                                <input v-model="gammaBlue" type="number" @change="handleGammaImage" min="0.01" max="2.2" step="0.01" class="w-16 py-1 pl-1 inp-no-style">
                                <input v-model="gammaBlue" type="range" @change="handleGammaImage" :min="0.01" :max="2.2" :step="0.01" class="flex-1"/>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <label class="w-20">{{ $t('Contrast') }}:</label>
                        <div class="inp-builder flex-1 flex">
                            <input v-model="contrastImage" type="number" @change="handleContrastImage" :min="-1" :max="1" step="0.01" class="w-16 py-1 pl-1 inp-no-style">
                            <input v-model="contrastImage" type="range" @change="handleContrastImage" :min="-1" :max="1" :step="0.01" class="flex-1"/>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <label class="w-20">{{ $t('Brightness') }}:</label>
                        <div class="inp-builder flex-1 flex">
                            <input v-model="brightnessImage" type="number" @change="handleBrightnessImage" :min="-1" :max="1" step="0.01" class="w-16 py-1 pl-1 inp-no-style">
                            <input v-model="brightnessImage" type="range" @change="handleBrightnessImage" :min="-1" :max="1" :step="0.01" class="flex-1"/>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div v-else class="flex items-center justify-center flex-col pt-8">
            <svg xmlns="http://www.w3.org/2000/svg" width="170" height="167" viewBox="0 0 170 167" fill="none"
                 class="ml-8">
                <rect x="1" y="1" width="129" height="129" rx="4" stroke="#E7E7EC" stroke-width="1"/>
                <path
                    d="M97.0143 94.0143L116.054 145.083L121.845 124.336L137.737 140.227L143.227 134.737L127.336 118.845L148.083 113.054L97.0143 94.0143Z"
                    stroke="white" stroke-width="10"
                    stroke-linecap="round" stroke-linejoin="round"/>
                <path
                    d="M97.0143 94.0143L116.054 145.083L121.845 124.336L137.737 140.227L143.227 134.737L127.336 118.845L148.083 113.054L97.0143 94.0143Z"
                    stroke="#E7E7EC" stroke-width="1"
                    stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p>{{ $t('Select an object to edit.') }}</p>
        </div>
    </div>
</template>

<script>
import builderMixin from '@/Builder/Mixins/builderMixin'
import eventBus from '@/Builder/Utils/eventBus'
import Spinner from '@/Components/Spinner.vue'
import GbsSelect from '@/Components/Select.vue'
import {Sketch, Compact} from '@lk77/vue3-color'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import {convertDimension, isValidHex6, correctHex6} from '@/Builder/Utils/helpers'
import ToggleInput from "@/Builder/Components/ToggleInput.vue";
import GsSelect from "@/Builder/Components/GsSelect.vue";
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";
import InformationOutlineIcon from "@/Builder/Icons/InformationOutlineIcon.vue";
import LockIcon from "@/Builder/Icons/LockIcon.vue";
import LockOpenVariantIcon from "@/Builder/Icons/LockOpenVariantIcon.vue";
import FormatBoldIcon from "@/Builder/Icons/FormatBoldIcon.vue";
import FormatItalicIcon from "@/Builder/Icons/FormatItalicIcon.vue";
import FormatUnderlineIcon from "@/Builder/Icons/FormatUnderlineIcon.vue";
import FormatAlignLeftIcon from "@/Builder/Icons/FormatAlignLeftIcon.vue";
import FormatAlignCenterIcon from "@/Builder/Icons/FormatAlignCenterIcon.vue";
import FormatAlignRightIcon from "@/Builder/Icons/FormatAlignRightIcon.vue";
import NavigationIcon from "@/Builder/Icons/NavigationIcon.vue";

export default {
    name: 'SettingPanel',
    components: {
        NavigationIcon,
        FormatAlignRightIcon,
        FormatAlignCenterIcon,
        FormatAlignLeftIcon,
        FormatUnderlineIcon,
        FormatItalicIcon,
        FormatBoldIcon,
        LockOpenVariantIcon,
        LockIcon,
        InformationOutlineIcon,
        GsSelect,
        GbsSelect,
        Spinner,
        Sketch,
        Compact,
        ToggleInput
    },
    mixins: [builderMixin, gangSheetSettingMixin],
    data() {
        return {
            hasActiveObject: false,
            width: 0,
            height: 0,
            lineSpace: 0,
            margin: 0,
            marginEnabled: false,
            isGroupSelection: false,
            isImageGroup: false,
            isAutoFillApplied: false,

            autoPositioning: false,
            imageQuantity: 1,
            keepAspectRatioEnabled: true,

            // for removing background
            removingBackground: false,
            backgroundRemoved: false,
            type: null,
            isGalleryImage: null,
            enableColorOverlay: false,

            // for color overlay
            applyOverlayColor: false,
            overlayColor: '#330000',
            colorOverlayAvailable: false,
            overlayFilter: 'blend',
            gammaRed: 1,
            gammaGreen: 1,
            gammaBlue: 1,
            contrastImage: 0,
            brightnessImage: 0,
            gammaActive: false,

            // for text settings
            text: '',
            textLineCount: 1,
            fillColor: '#000000',
            strokeColor: '#375AA2',
            selectedFont: null,
            fontFamily: null,
            colorChange: null,
            strokeWidth: 0,
            bold: false,
            italic: false,
            underline: false,
            alignLeft: false,
            alignCenter: false,
            alignRight: false,
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
        showRemoveBackgroundButton() {
            if (this.shop.settings?.disableBackgroundRemoveTool) {
                return false
            }
            return this.type === 'image' && !this.isGalleryImage
        },
        showCropImageButton() {
            return this.type === 'image' && !this.isGalleryImage
        },
        fontFamilies() {
            return this.shopFonts.map(font => {
                return {
                    label: font.name,
                    value: font.name
                }
            })
        },
        customTextColors() {
            return this.shopSettings.customTextColors.filter(c => c && this.shopSettings.useCustomTextColors);
        },
        customImageOverlayColors() {
            return this.shopSettings.customImageOverlayColors.filter(c => c && this.shopSettings.useCustomImageOverlayColors);
        },
    },
    watch: {
        imageQuantity() {
            if (this.imageQuantity < 0) {
                this.imageQuantity = 1
            }
            this.isAutoFillApplied = false
        },
        margin() {
            this.isAutoFillApplied = false
        },
        applyOverlayColor() {
            const selected = window._gangSheetCanvasEditor.getActiveObject()
            if (selected && selected.type === 'image') {
                selected.changeImageColor(this.applyOverlayColor ? this.overlayColor : null, this.overlayFilter)
            }
        },
        overlayFilter() {
            const selected = window._gangSheetCanvasEditor.getActiveObject()
            if (selected && selected.type === 'image') {
                selected.changeImageColor(selected?.overlayColor, this.overlayFilter)
            }
        },
        gammaActive(newValue) {
            if (!newValue) {
                this.gammaRed = 1;
                this.gammaGreen = 1;
                this.gammaBlue = 1;
                this.handleGammaImage()
            }
        }
    },
    methods: {
        initializeSettings(canvas) {
            this.marginEnabled = Boolean(canvas.marginEnabled)
            if (this.builderSettings.defaultAutoDuplicateMarginSize) {
                this.margin = convertDimension(this.builderSettings.defaultAutoDuplicateMarginSize, 'in', this.artBoardUnit)
            } else {
                this.margin = canvas.getMargin()
            }
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

            canvas.on('text:changed', () => {
                const activeObject = canvas.getActiveObject();
                if (activeObject && activeObject.type === 'i-text') {
                    this.textLineCount = activeObject.text.split('\n').length;
                }
            });
        },
        openEditImageModal() {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected && selected.type === 'image') {
                const originId = selected.id
                const originUrl = selected.getOriginalUrl()
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.EDIT_IMAGE,
                    data: {
                        url: originUrl,
                        id: originId,
                        parentId: selected.parentId
                    },
                    onChange: async (newImage) => {
                        const objects = _gangSheetCanvasEditor.getObjects('image')

                        for (const object of objects) {
                            if (originUrl === object.getOriginalUrl()) {

                                const newUrl = this.artBoardSettings.visualQuality ? newImage.url : newImage.thumb_url

                                await object.setSrc(newUrl, () => {
                                    object.set({
                                        id: newImage.id,
                                        parentId: originId,
                                        url: newImage.url,
                                        thumb_url: newImage.thumb_url,
                                        realWidth: newImage.width,
                                        realHeight: newImage.height
                                    })
                                    _gangSheetCanvasEditor.renderAll()
                                })
                            }
                        }

                        const index = this.images.findIndex(img => img.url === originUrl)
                        if (index > -1) {
                            this.images[index].parentId = originId
                            this.images[index].id = newImage.id
                            this.images[index].thumb_url = newImage.thumb_url
                            this.images[index].url = newImage.url
                            this.images[index].width = newImage.width
                            this.images[index].height = newImage.height
                            _gangSheetCanvasEditor.setImages(this.images)
                        }
                    }
                })
            }
        },
        openCropImageModal() {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected && selected.type === 'image') {
                const originId = selected.id
                const originUrl = selected.getOriginalUrl()
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.CROP_IMAGE,
                    data: {
                        url: originUrl,
                        id: originId,
                        parentId: selected.parentId
                    },
                    onChange: async (newImage) => {
                        const objects = _gangSheetCanvasEditor.getObjects('image')

                        for (const object of objects) {
                            if (originUrl === object.getOriginalUrl()) {

                                const newUrl = this.artBoardSettings.visualQuality ? newImage.url : newImage.thumb_url

                                await object.setSrc(newUrl, () => {
                                    object.set({
                                        id: newImage.id,
                                        parentId: originId,
                                        thumb_url: newImage.thumb_url,
                                        url: newImage.url,
                                        realWidth: newImage.width,
                                        realHeight: newImage.height
                                    })
                                    _gangSheetCanvasEditor.renderAll()
                                })
                            }
                        }

                        const index = this.images.findIndex(img => img.url === originUrl)
                        if (index > -1) {
                            this.images[index].parentId = originId
                            this.images[index].id = newImage.id
                            this.images[index].thumb_url = newImage.thumb_url
                            this.images[index].url = newImage.url
                            this.images[index].width = newImage.width
                            this.images[index].height = newImage.height
                            _gangSheetCanvasEditor.setImages(this.images)
                        }
                    }
                })
            }
        },
        async handleRemoveBackground() {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected && !this.removingBackground) {
                this.removingBackground = true
                const res = await selected.removeBackground()
                _gangSheetCanvasEditor.renderAll()
                this.removingBackground = false
                if (res) {
                    // while working on background transparency, selected object can be changed.
                    const selected = _gangSheetCanvasEditor.getActiveObject()
                    if (selected) {
                        this.backgroundRemoved = selected.backgroundRemoved
                    }
                } else {
                    window.Toast.error({
                        message: 'We are not able to remove the background of this image.'
                    })
                }
            }
        },
        updateDimensions(canvas) {
            const activeObject = canvas.getActiveObject()
            Object.assign(this.$data, this.$options.data.apply(this));
            if (activeObject) {
                this.type = activeObject.type
                this.isGalleryImage = activeObject.isGalleryImage
                this.isGroupSelection = activeObject.type === 'activeSelection'
                this.enableColorOverlay = activeObject.enableColorOverlay

                this.isImageGroup = false
                this.isAutoFillApplied = false
                this.imageQuantity = 1

                if (activeObject.type === 'i-text') {
                    this.text = activeObject.text
                    this.textLineCount = this.text.split('\n').length;
                    this.fillColor = activeObject.fill
                    this.strokeColor = activeObject.stroke
                    this.backgroundColor = activeObject.backgroundColor
                    this.strokeWidth = activeObject.strokeWidth
                    this.underline = activeObject.underline
                    this.alignLeft = activeObject.textAlign === 'left';
                    this.alignCenter = activeObject.textAlign === 'center';
                    this.alignRight = activeObject.textAlign === 'right';
                    this.fontFamily = this.fontFamilies.find(font => font.value === activeObject.fontFamily)
                    if (!this.fontFamily) {
                        this.fontFamily = this.fontFamilies[0]
                    }
                    this.selectedFont = this.shopFonts.find(font => font.name === activeObject.fontFamily)
                    if (!this.selectedFont) {
                        this.selectedFont = this.shopFonts[0]
                    }

                    if (this.selectedFont?.bold) {
                        this.bold = activeObject.fontWeight === 'bold'
                    } else {
                        activeObject.set('fontWeight', 'normal')
                    }

                    if (this.selectedFont?.italic) {
                        this.italic = activeObject.fontStyle === 'italic'
                    } else {
                        activeObject.set('fontStyle', 'normal')
                    }
                }

                this.colorOverlayAvailable = activeObject.type === 'image' &&
                    this.enableColorOverlay &&
                    !fabric.util.isSvg(activeObject.getSourceUrl())

                if (this.colorOverlayAvailable) {
                    if (activeObject.overlayColor) {
                        this.overlayColor = activeObject.overlayColor
                    } else if (this.shopSettings?.useCustomImageOverlayColors && this.shopSettings?.customImageOverlayColors?.length > 0) {
                        this.overlayColor = this.shopSettings.customImageOverlayColors[0]
                    }

                    this.overlayFilter = activeObject.overlayFilter ?? 'blend'
                    this.applyOverlayColor = Boolean(activeObject.overlayColor)

                    const filters = activeObject.get('filters') || [];
                    filters.forEach(filter => {
                        const filterType = filter.type.toLowerCase()
                        if (filterType === 'gamma') {
                            const {gamma} = filter;
                            this.gammaRed = gamma[0];
                            this.gammaGreen = gamma[1];
                            this.gammaBlue = gamma[2];
                            this.gammaActive = true;
                        } else if (filterType === 'contrast') {
                            this.contrastImage = filter.contrast;
                        } else if (filterType === 'brightness') {
                            this.brightnessImage = filter.brightness;
                        }
                    });
                }

                this.hasActiveObject = true
                this.keepAspectRatioEnabled = activeObject.get('rockedRatio')
                this.backgroundRemoved = activeObject.get('backgroundRemoved')

                const {width, height} = activeObject.getDimensions()
                this.width = width.toFixed(2)
                this.height = height.toFixed(2)
            }
        },
        handleInputWidth(e) {
            const canvas = _gangSheetCanvasEditor
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
            const canvas = _gangSheetCanvasEditor
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
        handleDuplicate() {
            const canvas = _gangSheetCanvasEditor
            let quantity = Number(this.imageQuantity || 1)
            if (this.maxAllowedFiles > -1) {
                const maxFiles = Math.max(this.maxAllowedFiles - canvas.size(), 0)
                if (quantity > maxFiles) {
                    quantity = maxFiles
                }
            }
            if (quantity > 0) {
                canvas.autoFill(quantity, this.marginEnabled ? this.margin : 0)
                this.isAutoFillApplied = true
            } else {
                window.Toast.error({
                    message: 'Artboard is fully filled.'
                })
            }
        },
        updateLineSpace(selected) {
            if (_gangSheetCanvasEditor) {
                this.lineSpace = convertDimension(selected.getLineSpace(), 'px', this.artBoardUnit)
            }
        },
        handleLineSpaceInput(e) {
            const canvas = _gangSheetCanvasEditor
            if (canvas && e.target.value) {
                const selected = canvas.getActiveObject()
                if (selected) {
                    selected.set('lineSpace', convertDimension(Number(e.target.value), this.artBoardUnit, 'px'))
                    selected.objectCaching = false
                    canvas.renderAll()
                    selected.objectCaching = true
                }
            }
        },
        preventNegative(event) {
            if (event.key === '-') {
                event.preventDefault();
            }
        },
        handleKeepAspectRatio() {
            const canvas = _gangSheetCanvasEditor
            this.keepAspectRatioEnabled = !this.keepAspectRatioEnabled
            canvas.rockSelectionRatio(this.keepAspectRatioEnabled)
            canvas.renderAll()
        },
        handleMarginChange(e) {
            if (Number(e.target.value) < 0) {
                this.margin = 0
            }
        },
        handleFontFamilyChange(font) {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected && font) {
                selected.reInitDimensions(font.value)
                this.reloadSizeValue(selected)
                this.updateLineSpace(selected)

                _gangSheetCanvasEditor.renderAll()
            }
        },
        reloadSizeValue(selected) {
            const {width, height} = selected.getDimensions()
            this.width = width.toFixed(2)
            this.height = height.toFixed(2)
        },
        handleTextChange(e) {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('text', e.target.value)
                _gangSheetCanvasEditor.renderAll()
            }
        },
        handleGammaImage() {
            const gammaRed = Number(this.gammaRed);
            const gammaGreen = Number(this.gammaGreen);
            const gammaBlue = Number(this.gammaBlue);
            const gamma = [gammaRed, gammaGreen, gammaBlue];
            const selected = _gangSheetCanvasEditor?.getActiveObject()
            if (selected?.type === 'image') {
                selected.filters = (selected.filters || []).filter(filter => !(filter instanceof fabric.Image.filters.Gamma));
                if (!(gammaRed === 1 && gammaGreen === 1 && gammaBlue === 1)) {
                    const filter = new fabric.Image.filters.Gamma({gamma: gamma})
                    selected.filters.push(filter);
                }
                selected.applyFilters();
                _gangSheetCanvasEditor.renderAll();
            }
        },
        handleContrastImage() {
            const contrast = Number(this.contrastImage);
            const selected = _gangSheetCanvasEditor?.getActiveObject()
            if (selected) {
                selected.filters = selected.filters.filter(filter => !(filter instanceof fabric.Image.filters.Contrast));
                if (contrast) {
                    const filter = new fabric.Image.filters.Contrast({contrast: contrast})
                    selected.filters.push(filter);
                }
                selected.applyFilters();
                _gangSheetCanvasEditor.renderAll();
            }
        },
        handleBrightnessImage() {
            const brightness = Number(this.brightnessImage);
            const selected = _gangSheetCanvasEditor?.getActiveObject()
            if (selected) {
                selected.filters = selected.filters.filter(filter => !(filter instanceof fabric.Image.filters.Brightness));
                if (brightness) {
                    const filter = new fabric.Image.filters.Brightness({brightness: brightness})
                    selected.filters.push(filter);
                }
                selected.applyFilters();
                _gangSheetCanvasEditor.renderAll();
            }
        },
        handleUpdateColor(color) {
            this[this.colorChange] = color.hex
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                selected.set({
                    fill: this.fillColor,
                    stroke: this.strokeColor,
                })
                _gangSheetCanvasEditor.renderAll()
            }
        },
        handleUpdateOverlayColor(color) {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected?.type === 'image') {
                selected.changeImageColor(color.hex, this.overlayFilter)
                if (color.hex) {
                    this.overlayColor = color.hex
                }
            }
        },
        handleChangeOverlayColorHex(e) {
            e.target.classList.remove('ring-1')
            e.target.classList.remove('ring-red-500')
            if (isValidHex6(e.target.value)) {
                this.handleUpdateOverlayColor({hex: e.target.value})
            } else {
                e.target.classList.add('ring-1')
                e.target.classList.add('ring-red-500')
            }
        },
        handleOutsideClick() {
            this.colorChange = null
        },
        handleStrokeWidthChange(e) {
            const value = Number(e.target.value)
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('strokeWidth', value)
                _gangSheetCanvasEditor.renderAll()
            }
        },
        toggleFormatBold() {
            this.bold = !this.bold
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                if (this.bold) {
                    selected.set('fontWeight', 'bold')
                } else {
                    selected.set('fontWeight', 'normal')
                }

                _gangSheetCanvasEditor.renderAll()
            }
        },
        toggleFormatItalic() {
            this.italic = !this.italic
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                if (this.italic) {
                    selected.set('fontStyle', 'italic')
                } else {
                    selected.set('fontStyle', 'normal')
                }
                _gangSheetCanvasEditor.renderAll()
            }
        },
        toggleFormatUnderline() {
            this.underline = !this.underline

            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('underline', this.underline)
                _gangSheetCanvasEditor.renderAll()
            }
        },
        toggleAlign(alignment) {
            this.alignLeft = alignment === 'left';
            this.alignCenter = alignment === 'center';
            this.alignRight = alignment === 'right';

            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                selected.set('textAlign', alignment);
                _gangSheetCanvasEditor.renderAll();
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
        },
        correctOverlayColor() {
            if (isValidHex6(this.overlayColor)) {
                this.handleUpdateOverlayColor({hex: this.overlayColor})
            }

            this.overlayColor = correctHex6(this.overlayColor)
            this.handleUpdateOverlayColor({hex: this.overlayColor})
        },
        correctActiveColor(e) {
            if (isValidHex6(e.target.value)) {
                this.handleUpdateColor({hex: e.target.value})
            }

            const color = correctHex6(e.target.value)
            this.handleUpdateColor({hex: color})
        }
    }
}
</script>

<style>
.vc-sketch {
    width: 100% !important
}

.vc-compact.font-color-picker {
    width: 152px !important;
}
</style>
