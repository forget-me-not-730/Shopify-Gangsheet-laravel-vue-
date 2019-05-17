<script>
import {defineComponent} from 'vue'
import {getImageDimensions, getPixelSize, convertDimension} from '@/Builder/Utils/helpers'
import normalizeUrl from 'normalize-url'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import builderMixin from '@/Builder/Mixins/builderMixin'
import eventBus from '@/Builder/Utils/eventBus'
import {Sketch, Compact} from '@lk77/vue3-color'
import GsbImage from '@/Builder/Components/GsbImage.vue'
import {MODAL_NAMES, TOOLS} from '@/Builder/Utils/constants'
import {cloneDeep} from 'lodash'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiClose, mdiMinus, mdiPlus, mdiContentDuplicate, mdiDelete} from '@mdi/js'
import Spinner from '@/Components/Spinner.vue'
import AutoNestPreview from '@/Builder/Components/AutoNestPreview.vue'
import GsSelect from "@/Builder/Components/GsSelect.vue";
import FormatBoldIcon from "@/Builder/Icons/FormatBoldIcon.vue";
import FormatItalicIcon from "@/Builder/Icons/FormatItalicIcon.vue";
import FormatUnderlineIcon from "@/Builder/Icons/FormatUnderlineIcon.vue";
import FormatAlignLeftIcon from "@/Builder/Icons/FormatAlignLeftIcon.vue";
import FormatAlignCenterIcon from "@/Builder/Icons/FormatAlignCenterIcon.vue";
import FormatAlignRightIcon from "@/Builder/Icons/FormatAlignRightIcon.vue";

export default defineComponent({
    name: 'AutoNest',
    components: {
        AutoNestPreview,
        Spinner,
        SvgIcon,
        GsbImage,
        GsSelect,
        Sketch,
        Compact,
        FormatAlignRightIcon,
        FormatAlignCenterIcon,
        FormatAlignLeftIcon,
        FormatUnderlineIcon,
        FormatItalicIcon,
        FormatBoldIcon
    },
    mixins: [gangSheetMixin, builderMixin],
    data() {
        return {
            objects: [],
            packs: [],
            loading: false,
            resolutionLevels: undefined,
            preview: false,
            margin: 0.125,
            artboardMargin: 0.125,
            marginEnabled: true,
            artboardMarginEnabled: true,

            fillColor: '#000000',
            strokeColor: '#375AA2',
            fontFamily: null,
            colorChange: null,
            curObj: null,
            curObjIndex: null,

            mdiClose,
            mdiMinus,
            mdiPlus,
            mdiContentDuplicate,
            mdiDelete
        }
    },
    mounted() {
        if (window._gangSheetCanvasEditor) {
            this.resolutionLevels = window._gangSheetCanvasEditor.resolutionLevels
            this.margin = _gangSheetCanvasEditor.getMargin()
            this.artboardMargin = _gangSheetCanvasEditor.getArtboardMargin()
            this.marginEnabled = this.margin > 0
            this.artboardMarginEnabled = this.artboardMargin > 0
            this.getObjects()
        } else {
            this.$emit('close')
        }

        eventBus.$on(eventBus.IMAGE_ADD, this.handleAddImage.bind(this))
        eventBus.$on(eventBus.IMAGE_REUPLOADED, this.getObjectsAndAddImage.bind(this))
        eventBus.$on(eventBus.ADD_TEXT_TO_AUTO_NEST, this.handleAddText)
    },
    unmounted() {
        eventBus.$off(eventBus.IMAGE_ADD, this.handleAddImage)
        eventBus.$off(eventBus.IMAGE_REUPLOADED, this.getObjectsAndAddImage)
        eventBus.$off(eventBus.ADD_TEXT_TO_AUTO_NEST, this.handleAddText)
    },
    computed: {
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
        activeColor() {
            if (!this.colorChange) {
                return null
            }
            const color = this[this.colorChange]
            return color?.startsWith('#') ? color : '#000000'
        },
    },
    methods: {
        handleAddImage(image) {
            this.addImage(image, 1)
        },
        getResolution(obj) {
            const resolutionX = obj.realWidth / (obj.width * getPixelSize(this.artBoardUnit) / 300)
            const resolutionY = obj.realHeight / (obj.height * getPixelSize(this.artBoardUnit) / 300)

            return Math.round(Math.min(resolutionX, resolutionY))
        },
        async addImage(image, quantity = 0) {
            if (_gangSheetCanvasEditor && image.url?.startsWith('http')) {
                let src = (image.thumb_url || image.url).trim()

                if (image.isGalleryImage && fabric.util.isSvg(image.url)) {
                    src = image.url
                }

                if (this.objects.every(_o => _o.url !== src && _o.originalUrl !== image.url)) {

                    if (!image.width || !image.height) {
                        const {width: iw, height: ih} = await getImageDimensions(src)
                        image.width = iw
                        image.height = ih
                    }

                    let width = Number((image.width / getPixelSize(this.artBoardUnit)).toFixed(2))
                    let height = Number((image.height / getPixelSize(this.artBoardUnit)).toFixed(2))

                    const maxWidth = _gangSheetCanvasEditor.designWidth / getPixelSize(this.artBoardUnit) - _gangSheetCanvasEditor.getMargin() * 2
                    const maxHeight = _gangSheetCanvasEditor.designHeight / getPixelSize(this.artBoardUnit) - _gangSheetCanvasEditor.getMargin() * 2

                    if (fabric.util.isSvg(src)) {
                        const rate = 300 / 96
                        width = (width * rate).toFixed(2)
                        height = (height * rate).toFixed(2)
                    }

                    const ratio = height / width

                    if (width > maxWidth) {
                        width = Number(maxWidth.toFixed(2))
                        height = Number((width * ratio).toFixed(2))
                    }

                    if (height > maxHeight) {
                        height = Number(maxHeight.toFixed(2))
                        width = Number((height / ratio).toFixed(2))
                    }

                    this.objects.push({
                        id: image.id,
                        imageId: image.id,
                        parentId: image.parentId,
                        isGalleryImage: image.isGalleryImage,
                        mimeType: image.mimeType,
                        type: 'image',
                        quantity: quantity,
                        url: src,
                        originalUrl: image.url,
                        width: width,
                        height: height,
                        realWidth: image.width,
                        realHeight: image.height,
                        lockedAspectRatio: true,
                        resolution: 300
                    })
                }
            }
        },
        getObjects() {
            this.$gsb.updateCanvasData()
            this.objects = fabric.util.getAutoNestObjectsFromDesigns(this.workingDesigns)

            for (let obj of this.objects) {
                obj.selectedFont = this.shopFonts.find(font => font.name === obj.fontFamily)
                if (!obj.selectedFont) {
                    obj.selectedFont = this.shopFonts[0]
                }
            }

            for (const image of this.images) {
                this.addImage(image)
            }
        },
        getObjectsAndAddImage() {
            this.$gsb.updateCanvasData()
            this.objects = fabric.util.getAutoNestObjectsFromDesigns(this.workingDesigns)

            for (const image of this.images) {
                this.addImage(image, 1)
            }
        },
        validateApplying() {
            if (this.loading) {
                return false
            }

            if (this.objects.length === 0) {
                this.$gsb.error('Please add/upload one or more images.')
                return false
            }

            const totalImageCount = this.objects.reduce((count, image) => {
                count += image.quantity
                return count
            }, 0)

            if (totalImageCount > 6000) {
                this.$gsb.error('The total number of images has exceeded its limit.');
                return false
            }

            const totalArea = this.objects.reduce((area, image) => {
                area += Number(image.width) * Number(image.height) * image.quantity
                return area
            }, 0)

            let limitArea = 22 * 240 * 30

            if (this.artBoardUnit === 'mm') {
                limitArea = 558.8 * 6096 * 30
            }

            if (this.artBoardUnit === 'cm') {
                limitArea = 55.88 * 609.6 * 30
            }

            if (totalArea > limitArea) {
                this.$gsb.error('The auto-generation request has exceeded its limit.');
                return false
            }

            return true
        },

        async handleApply() {

            if (!this.validateApplying()) {
                return
            }

            try {
                this.loading = true

                const margin = Number(this.marginEnabled ? this.margin : 0)
                const artBoardMargin = Number(this.artboardMarginEnabled ? this.artboardMargin : 0)

                const rectangles = fabric.util.getAutoNestRectsFromObjects(this.objects, margin)

                if (rectangles.length) {
                    const res = await axios.post(route('builder.auto-nest'), {
                        rectangles: rectangles,
                        margin: margin,
                        artboardMargin: artBoardMargin,
                        variants: this.$gsb.getVariantsForAutoNest(),
                        visibleVariants: this.$gsb.getSameTypeSizeVariants(this.visibleVariants, this.homeVariant),
                        hiddenVariants: this.$gsb.getSameTypeSizeVariants(this.hiddenVariants, this.homeVariant),
                    })

                    if (res.data.success) {
                        const packs = res.data.packs.filter(p => p.bins.length > 0)

                        if (packs.length > 0) {
                            if (!this.shopSettings.alwaysDisplayVariantsInAutoBuild && packs.length === 1) {
                                await this.$gsb.createDesignsFromBins(packs[0].bins, {margin, artBoardMargin})
                            } else {
                                this.packs = packs
                                this.$el.scrollTo(0, 0)
                                this.preview = true
                            }
                        } else {
                            this.$gsb.error('The entry image size is too large to fit inside the artboard.')
                        }
                    } else {
                        this.$gsb.error('Not able to generate the auto build. Please try again.')
                    }
                } else {
                    window.Toast.error({
                        message: 'Please add/upload one or more images.'
                    })
                }
            } catch (err) {
                console.error(err)
            } finally {
                this.loading = false
            }
        },
        handleWidthInput(e, obj) {
            if (Number(e.target.value) > 0) {
                let r = null
                const oldHeight = obj.height
                const oldWidth = obj.width
                if (Number(obj.height)) {
                    if (obj.type === 'i-text') {
                        r = obj.width / obj.height
                    } else {
                        r = obj.realWidth / obj.realHeight
                    }
                }

                obj.width = Number(e.target.value)

                if (obj.width > this.variant.width) {
                    obj.width = Number(this.variant.width)
                }

                if (obj.lockedAspectRatio && r) {
                    obj.height = Number((obj.width / r).toFixed(2))
                }

                if (obj.type === 'i-text') {
                    obj.resolution = 300
                    obj.scaleX *= obj.width / oldWidth
                    obj.scaleY *= obj.height / oldHeight
                } else if (fabric.util.isSvg(obj.originalUrl)) {
                    obj.resolution = 300
                } else {
                    obj.resolution = this.getResolution(obj)
                }
            }
        },
        handleHeightInput(e, obj) {
            if (Number(e.target.value) > 0) {
                let r = null
                const oldHeight = obj.height
                const oldWidth = obj.width
                if (Number(obj.width) > 0) {
                    if (obj.type === 'i-text') {
                        r = obj.height / obj.width
                    } else {
                        r = obj.realHeight / obj.realWidth
                    }
                }

                obj.height = Number(e.target.value)

                if (obj.height > this.variant.height) {
                    obj.height = Number(this.variant.height)
                }

                if (obj.lockedAspectRatio && r) {
                    obj.width = Number((obj.height / r).toFixed(2))
                }

                if (obj.type === 'i-text') {
                    obj.resolution = 300
                    obj.scaleX *= obj.width / oldWidth
                    obj.scaleY *= obj.height / oldHeight
                } else if (fabric.util.isSvg(obj.originalUrl)) {
                    obj.resolution = 300
                } else {
                    obj.resolution = this.getResolution(obj)
                }
            }
        },
        toggleFormatBold(obj) {
            obj.fontWeight = obj.fontWeight === 'bold' ? 'normal' : 'bold'
        },
        toggleFormatItalic(obj) {
            obj.fontStyle = obj.fontStyle === 'italic' ? 'normal' : 'italic'
        },
        toggleAlign(alignment, obj) {
            obj.textAlign = alignment
        },
        toggleColorPicker(e, picker, obj, index) {
            e.preventDefault()
            e.stopImmediatePropagation()

            if (this.colorChange === picker) {
                this.colorChange = null
                this.curObj = null
                this.curObjIndex = null
            } else {
                this.fillColor = obj.fill
                this.strokeColor = obj.strokeColor
                this.colorChange = picker
                this.curObj = obj
                this.curObjIndex = index
            }
        },
        handleOutsideClick() {
            this.colorChange = null
            this.curObj = null
        },
        handleUpdateColor(color) {
            this[this.colorChange] = color.hex
            this.curObj.fill = this.fillColor
            this.curObj.strokeColor = this.strokeColor
        },
        correctActiveColor(e) {
            if (isValidHex6(e.target.value)) {
                this.handleUpdateColor({hex: e.target.value})
            }

            const color = correctHex6(e.target.value)
            this.handleUpdateColor({hex: color})
        },
        handleUploadImage(from) {
            if (window.innerWidth <= 768) {
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.IMAGE_UPLOAD,
                    data: {
                        from: from - 1
                    }
                })
            } else {
                if (from === 1) {
                    eventBus.$emit(eventBus.IMAGE_UPLOAD)
                } else if (from === 2) {
                    this.tool = TOOLS.uploads
                } else if (from === 3) {
                    this.tool = TOOLS.gallery
                }
            }
        },
        handleDuplicate(index) {
            const newObject = cloneDeep(this.objects[index])
            newObject.id = new Date().getTime() + index

            this.objects.splice(index + 1, 0, newObject)
        },
        handleFontFamilyChange(font, obj) {
            if (obj && font && window._gangSheetCanvasEditor) {
                const canvas = window._gangSheetCanvasEditor;
                const ctx = canvas.contextContainer;

                const lines = obj.text.split('\n');
                let maxWidth = 0;
                let totalHeight = 0;

                lines.forEach(line => {
                    const {width, height} = fabric.util.measureText(ctx, line, font.value);
                    maxWidth = Math.max(maxWidth, width); // Get the widest line
                    totalHeight += height; // Accumulate total height
                });

                obj.fontFamily = font.value;
                obj.selectedFont = this.shopFonts.find(font => font.name === obj.fontFamily)
                obj.width = Number((maxWidth * obj.scaleX / getPixelSize(this.artBoardUnit)).toFixed(2));
                obj.height = Number((totalHeight * obj.scaleY / getPixelSize(this.artBoardUnit)).toFixed(2));
            }
        },
        handleTextChange(obj) {
            if (obj && window._gangSheetCanvasEditor) {
                if (obj.text.includes('\n')) {
                    return;
                }

                if (obj.text.length >= 30) {
                    obj.text = obj.text.slice(0, 30);
                    return;
                }

                const canvas = window._gangSheetCanvasEditor;
                const ctx = canvas.contextContainer;

                const {width, height} = fabric.util.measureText(ctx, obj.text, obj.fontFamily);

                obj.width = Number((width * obj.scaleX / getPixelSize(this.artBoardUnit)).toFixed(2));
                obj.height = Number((height * obj.scaleY / getPixelSize(this.artBoardUnit)).toFixed(2));
            }
        },
        handleAddText() {
            if (window._gangSheetCanvasEditor) {
                const canvas = window._gangSheetCanvasEditor;
                const text = 'Gang Sheet';
                const fontFamily = this.shopFonts[0]?.name || 'Arial';
                const fontSize = 40;

                const textObject = new fabric.IText(text, {
                    fontFamily: fontFamily,
                    fontSize: fontSize
                });

                const viewport = canvas.getViewPort();
                textObject.scaleToWidth(viewport.width * 0.5);

                const { width, height } = fabric.util.measureText(
                    canvas.contextContainer,
                    text,
                    fontFamily
                );

                this.objects.push({
                    id: new Date().getTime(),
                    type: 'i-text',
                    text: text,
                    quantity: 1,
                    width: Number((width * textObject.scaleX / getPixelSize(this.artBoardUnit)).toFixed(2)),
                    height: Number((height * textObject.scaleX / getPixelSize(this.artBoardUnit)).toFixed(2)),
                    fontFamily: fontFamily,
                    selectedFont: this.shopFonts[0],
                    fontWeight: 'normal',
                    fontStyle: 'normal',
                    textAlign: 'left',
                    fill: '#000000',
                    strokeWidth: 0,
                    scaleX: textObject.scaleX,
                    scaleY: textObject.scaleX,
                    resolution: 300
                });
            }
        }
    },
})

</script>

<template>
    <div class="w-full h-full overflow-y-auto tiny-scroll-bar pb-20">
        <div class="flex justify-between">
            <div class="font-medium sm:text-lg flex">
                {{ $t(preview ? 'Preview' : 'Auto Build - Upload Multiple Images At Once') }}
            </div>
            <div class=" cursor-pointer" @click="autoNestMode = false">
                <svg-icon type="mdi" :path="mdiClose"/>
            </div>
        </div>

        <div class="flex flex-wrap my-2 md:space-x-4">
            <div class="flex items-center cursor-pointer max-md:w-full">
                <label class="text-sm flex items-center">
                    <input v-model="artboardMarginEnabled" id="margin-checkbox" type="checkbox" class="mr-1" :disabled="builderSettings.lockArtboardMargin">
                    {{ $t('With Artboard Margin') }}
                </label>
                <template v-if="artboardMarginEnabled">
                    <input v-model="artboardMargin" type="number" :step="artBoardUnit === 'mm' ? 1 : 0.1" min="0" class="inp-builder w-20 ml-2" :disabled="builderSettings.lockArtboardMargin"/>
                    <span class="ml-1">{{ artBoardUnit }}</span>
                </template>
            </div>

            <div class="flex items-center cursor-pointer max-md:mt-2 max-md:w-full">
                <label class="text-sm flex items-center">
                    <input v-model="marginEnabled" id="margin-checkbox" type="checkbox" class="mr-1" :disabled="builderSettings.lockMargin">
                    {{ $t('With Image Margin') }}
                </label>
                <template v-if="marginEnabled">
                    <input v-model="margin" type="number" :step="artBoardUnit === 'mm' ? 1 : 0.1" min="0" class="inp-builder w-20 ml-2" :disabled="builderSettings.lockArtboardMargin"/>
                    <span class="ml-1">{{ artBoardUnit }}</span>
                </template>
            </div>
        </div>

        <div v-if="preview">
            <auto-nest-preview :packs="packs" :margin="marginEnabled ? margin : 0" :artboardMargin="artboardMarginEnabled ? artboardMargin : 0" @back="preview = false" @success="$emit('close')"/>
        </div>
        <div v-else>
            <div class="w-full flex-col pt-20 text-gray-400 flex justify-center items-center text-center" v-if="objects.length === 0">
                {{ $t('No Images uploaded, Please upload your images or select from the gallery.') }}

                <div class="mt-3 flex max-sm:flex-col max-sm:space-y-2 max-sm:w-full sm:space-x-2 items-center justify-center">
                    <button class="btn-builder max-sm:w-full justify-center" @click="handleUploadImage(1)">{{ $t('Upload Image(s)') }}</button>
                    <button class="btn-builder max-sm:w-full justify-center" @click="handleUploadImage(2)">{{ $t('From My Images') }}</button>
                    <button class="btn-builder max-sm:w-full justify-center" @click="handleUploadImage(3)">{{ $t('From Gallery') }}</button>
                </div>
            </div>

            <div class="grid grid-cols-[repeat(auto-fill,minmax(320px,1fr))] gap-3">
                <div v-for="(obj, index) in objects" :key="obj.id" class="border border-gray-300 w-full flex flex-col">
                    <div class="flex items-start w-full p-1">
                        <div class="w-44 h-44 shrink-0 bg-gray-400 bg-opacity-50 flex-center mr-2 relative overflow-hidden" :class="{'opacity-20 bg-gray-100 !cursor-pointer': !Boolean(obj.quantity)}">
                            <div v-if="obj.type.includes('text')"
                                    :style="{
                                    whiteSpace: 'nowrap',
                                    maxWidth: '100%',
                                    fontFamily: obj.fontFamily,
                                    fontWeight: obj.fontWeight,
                                    fontStyle: obj.fontStyle,
                                    textAlign: obj.textAlign,
                                    color: obj.fill,
                                    '-webkit-text-stroke': obj.strokeWidth + 'px ' + obj.strokeColor }">
                                <div v-for="text in obj.text.split('\n')" class="w-full">
                                    {{ text }}
                                </div>
                            </div>
                            <gsb-image v-else :src="obj.url" class="w-full h-full object-contain"/>
                        </div>
                        <div class="flex flex-col justify-start text-xs space-y-1 flex-1 w-px" :class="{'opacity-20 bg-builder !cursor-pointer': !Boolean(obj.quantity) }">
                            <div class="flex items-center">
                                <label class="w-14 shrink-0">{{ $t('Width') }}: </label>
                                <div class="flex flex-1 inp-builder"
                                     :class="{'border-red-500': Number(obj.width) === 0 }">
                                    <input type="number" :value="obj.width" @input="handleWidthInput($event, obj)" step="1" min="0.01" :disabled="obj.quantity === 0 || obj.type === 'n-text'"
                                           style="line-height: 1"
                                           class="w-full inp-no-style">
                                    {{ artBoardUnit }}
                                </div>
                            </div>
                            <div class="flex items-center">
                                <label class="w-14 shrink-0">{{ $t('Height') }}: </label>
                                <div class="flex flex-1 inp-builder"
                                     :class="{'border-red-500': Number(obj.height) === 0 }">
                                    <input type="number" :value="obj.height" @input="handleHeightInput($event, obj)" step="1" min="0.01" :disabled="obj.quantity === 0 || obj.type === 'n-text'"
                                           style="line-height: 1"
                                           class="w-full inp-no-style">
                                    {{ artBoardUnit }}
                                </div>
                            </div>
                            <div v-if="obj.type.includes('i-text')">
                                <input
                                    v-model="obj.text"
                                    @input="handleTextChange(obj)"
                                    class="w-full mb-1 inp-builder text-sm"
                                    style="line-height: 1"
                                    type="text"
                                    :disabled="obj.text.includes('\n')"
                                />
                                <gs-select :model-value="fontFamilies.find(font => font.value === obj.fontFamily)" :options="fontFamilies"
                                           @update:model-value="(font) => handleFontFamilyChange(font, obj)" :search="true"/>

                                <div class="flex items-center justify-between mt-1">
                                    <div class="flex space-x-1">
                                        <button
                                            :disabled="obj.selectedFont?.bold"
                                            :class="{'gs-bg-primary': obj.fontWeight === 'bold'}"
                                            class="w-7 h-7 border rounded flex items-center justify-center cursor-pointer disabled:text-gray-300 disabled:cursor-not-allowed"
                                            @click="toggleFormatBold(obj)">
                                            <format-bold-icon size="16"/>
                                        </button>
                                        <button
                                            :disabled="obj.selectedFont?.italic"
                                            :class="{'gs-bg-primary': obj.fontStyle === 'italic'}"
                                            class="w-7 h-7 border rounded flex items-center justify-center cursor-pointer disabled:text-gray-300 disabled:cursor-not-allowed"
                                            @click="toggleFormatItalic(obj)">
                                            <format-italic-icon size="16"/>
                                        </button>
                                    </div>
                                    <div
                                        :class="{'gs-bg-primary': colorChange === 'fillColor' && index === curObjIndex}"
                                        class="h-7 border px-1 flex items-center justify-center cursor-pointer"
                                        @click="toggleColorPicker($event, 'fillColor', obj, index)">
                                        <span class="w-4 h-4 border border-gray-300 mr-1" :style="{backgroundColor: obj.fill}"></span>
                                        {{ $t('Text Color') }}
                                    </div>
                                </div>
                                <div v-if="colorChange && index === curObjIndex" class="relative flex w-full" :class="{'justify-end': colorChange === 'strokeColor'}">
                                    <div v-click-outside="handleOutsideClick" class="absolute top-1 w-full z-50 bg-builder border border-gray-300">
                                        <div v-if="customTextColors.length > 0">
                                            <Compact :model-value="activeColor" class="font-color-picker"
                                                     @update:modelValue="handleUpdateColor" :palette="customTextColors"/>
                                        </div>
                                        <div v-else>
                                            <div class="flex items-center justify-between">
                                                <span class="w-full text-xs py-1 px-2 border-r border-r-gray-300 focus:border-r-gray-300">
                                                    {{ colorChange === 'fillColor' ? $t('Text Color') : $t('Stroke') }}: </span>
                                                <input class="w-2/3 text-right text-xs py-1 inp-no-style px-2 uppercase focus:border-r-gray-300" maxlength="7" v-model="activeColor"
                                                       @change="correctActiveColor"/>
                                            </div>
                                            <hr/>
                                            <Sketch :model-value="activeColor" class="custom-color-picker disable-alpha"
                                                    @update:modelValue="handleUpdateColor" :disable-alpha="true"/>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="space-y-1" :class="{'hidden': obj.type.includes('text')}">
                                <div class="flex items-center">
                                    <label class="flex items-center pointer-events-auto">
                                        <input type="checkbox"
                                               @change="obj.lockedAspectRatio = !obj.lockedAspectRatio"
                                               :checked="obj.lockedAspectRatio"
                                               class="mr-2">
                                        <span>{{ $t('Lock Aspect Ratio') }}</span>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div v-if="obj.resolution >= resolutionLevels.optimal" class="flex items-center">
                                        <span class="w-4 h-4 bg-green-500 mr-2"></span>
                                        <span>{{ $t('Optimal Resolution') }}</span>
                                    </div>
                                    <div v-else-if="obj.resolution >= resolutionLevels.good" class="flex items-center">
                                        <span class="w-4 h-4 bg-orange-500 mr-2"></span>
                                        <span>{{ $t('Good Resolution') }}</span>
                                    </div>
                                    <div v-else class="flex items-center">
                                        <span class="w-4 h-4 bg-red-500 mr-2"></span>
                                        <span>{{ $t('Bad Resolution') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-2 mt-auto border-t">
                        <div class="flex items-center rounded-lg">
                            <div class="btn-builder rounded-r-none p-0 w-7 h-7 cursor-pointer" @click="obj.quantity > 0 && obj.quantity--">
                                <svg-icon type="mdi" :path="mdiMinus" size="20"/>
                            </div>
                            <input type="number" v-model="obj.quantity" step="1" min="0"
                                   class="w-12 text-center border gs-border-primary focus:gs-border-primary px-0 h-7 border-x-0 inp-no-style appearance-none">
                            <div class="btn-builder rounded-l-none w-7 h-7 p-0 cursor-pointer" @click="obj.quantity++">
                                <svg-icon type="mdi" :path="mdiPlus" size="16" fill="currentColor"/>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="handleDuplicate(index)" class="btn-builder btn-sm px-2 cursor-pointer">
                                <svg-icon type="mdi" :path="mdiContentDuplicate" size="14"/>
                                {{ $t('Duplicate') }}
                            </button>
                            <button @click="objects.splice(index, 1)" class="btn-danger btn-sm px-2 cursor-pointer">
                                <svg-icon type="mdi" :path="mdiDelete" size="14"/>
                                {{ $t('Remove') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="objects.length" class="py-4 flex items-center justify-center space-x-4">
                <button :disabled="loading" class="btn-builder py-2 px-6" @click="handleApply">
                    <spinner v-if="loading" class="mr-2"/>
                    {{ $t('Apply') }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
