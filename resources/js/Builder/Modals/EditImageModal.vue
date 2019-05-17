<script>
import {defineComponent} from 'vue'
import Modal from '@/Builder/Modals/Modal.vue'
import {removeBackground, uploadBase64Image} from '@/Builder/Apis/builderApi'
import normalizeUrl from 'normalize-url'
import Spinner from '@/Components/Spinner.vue'
import {Sketch} from '@lk77/vue3-color'
import {getSessionId, hexToRgb} from '@/Builder/Utils/helpers'
import builderMixin from '@/Builder/Mixins/builderMixin'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiInformationOutline} from '@mdi/js'
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default defineComponent({
    name: 'EditImageModal',
    components: {CloseIcon, Spinner, Modal, Sketch, SvgIcon},
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        },
        data: {
            type: [Object, null],
            default: null
        }
    },
    data() {
        return {
            newImageUrl: null,
            colorToRemove: '#000000',
            colorToReplace: '#000000',
            replaceColorEnabled: false,
            activeColorPicker: null,
            removingBackground: false,
            loading: false,
            openColorPicker: false,
            imageMouseDown: false,
            toleranceValue: 10,
            hasChanged: false,
            uploading: false,
            removeShadow: false,
            mdiInformationOutline,
            imageName: null,
        }
    },
    mounted() {
        document.addEventListener('click', this.handleRemoveOutsideClick);
        document.addEventListener('click', this.handleReplaceOutsideClick);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleRemoveOutsideClick);
        document.removeEventListener('click', this.handleReplaceOutsideClick);
    },
    watch: {
        open() {
            this.loading = false
            this.removingBackground = false
            this.replaceColorEnabled = false
            this.hasChanged = false
            this.uploading = false
            this.toleranceValue = 10
            if (this.open) {
                if (this.data) {
                    this.newImageUrl = this.data.url
                    // get file name form the url.
                    this.imageName = this.data.url.split('/').pop().split('#')[0].split('?')[0].split('.')[0]
                    this.loadImage()
                }
            }
        }
    },
    methods: {
        async loadImage() {
            if (this.loading || !this.newImageUrl) return

            return new Promise((resolve) => {
                this.loading = true
                this.$nextTick(() => {
                    const canvas = document.getElementById('gs-image-edit-canvas')
                    const hiddenCanvas = document.getElementById('gs-image-edit-canvas-hidden')
                    if (canvas && this.newImageUrl) {
                        const image = new Image()
                        image.crossOrigin = 'anonymous'
                        image.onload = () => {
                            canvas.height = canvas.width / image.width * image.height

                            hiddenCanvas.width = image.width
                            hiddenCanvas.height = image.height
                            hiddenCanvas.style.height = image.height + 'px'
                            hiddenCanvas.style.width = image.width + 'px'

                            const context = canvas.getContext('2d')
                            context.translate(0, 0)
                            context.drawImage(image, 0, 0, canvas.width, canvas.height)
                            context.restore()

                            // draw hidden canvas
                            const hiddenContext = hiddenCanvas.getContext('2d')
                            hiddenContext.translate(0, 0)
                            hiddenContext.drawImage(image, 0, 0, image.width, image.height)
                            hiddenContext.restore()

                            this.loading = false
                            resolve(true)
                        }

                        image.onerror = () => {
                            this.loading = false
                            resolve(false)
                        }

                        image.src = this.newImageUrl
                    }
                })
            })
        },
        async handleRemoveBackground() {
            this.removingBackground = true
            const res = await removeBackground(this.data.url)
            if (res && res.success) {
                const url = normalizeUrl(res.url)
                await fetch(url, {cache: 'reload', mode: 'no-cors'})
                this.newImageUrl = url
                this.loadImage().then(() => {
                    if (this.removeShadow) {
                        this.removeAlpha()
                    }
                    this.removingBackground = false
                    this.hasChanged = true
                })
            } else {
                this.removingBackground = false
                console.error(res.error)
            }
        },
        handleRemoveColorInput(color) {
            this.colorToRemove = color.hex
        },
        handleReplaceColorInput(color) {
            this.colorToReplace = color.hex
        },
        removeAlpha() {
            const canvas = document.getElementById('gs-image-edit-canvas')
            const hiddenCanvas = document.getElementById('gs-image-edit-canvas-hidden')

            this.removeAlphaFromCanvas(canvas)
            this.removeAlphaFromCanvas(hiddenCanvas)
        },
        removeAlphaFromCanvas(canvas) {
            const context = canvas.getContext('2d')

            const imageData = context.getImageData(0, 0, canvas.width, canvas.height)
            const data = imageData.data

            for (let y = 0; y < canvas.height; y++) {
                for (let x = 0; x < canvas.width; x++) {
                    const index = (y * canvas.width + x) * 4
                    const alpha = data[index + 3]

                    if (alpha < 200) {
                        data[index + 3] = 0
                    }
                }
            }

            context.putImageData(imageData, 0, 0)
        },
        removeColorFromCanvas(canvas, color) {
            const context = canvas.getContext('2d')

            // Get the Image Data:
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height)
            const data = imageData.data

            const rgb = hexToRgb(color)

            for (let y = 0; y < canvas.height; y++) {
                for (let x = 0; x < canvas.width; x++) {
                    const index = (y * canvas.width + x) * 4
                    const r = data[index]
                    const g = data[index + 1]
                    const b = data[index + 2]
                    const alpha = data[index + 3]

                    const colorDifference = Math.sqrt(
                        Math.pow(rgb.r - r, 2) +
                        Math.pow(rgb.g - g, 2) +
                        Math.pow(rgb.b - b, 2)
                    )

                    if (colorDifference <= this.toleranceValue) {
                        data[index + 3] = 0 // set alpha 0
                    }
                }
            }

            context.putImageData(imageData, 0, 0)
        },
        replaceColorFromCanvas(canvas, colorFrom, colorTo) {
            const context = canvas.getContext('2d')

            // Get the Image Data:
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height)
            const data = imageData.data

            const rgbFrom = hexToRgb(colorFrom)
            const rgbTo = hexToRgb(colorTo)

            for (let y = 0; y < canvas.height; y++) {
                for (let x = 0; x < canvas.width; x++) {
                    const index = (y * canvas.width + x) * 4
                    const r = data[index]
                    const g = data[index + 1]
                    const b = data[index + 2]
                    const alpha = data[index + 3]

                    if(alpha !== 0){
                        const colorDifference = Math.sqrt(
                            Math.pow(rgbFrom.r - r, 2) +
                            Math.pow(rgbFrom.g - g, 2) +
                            Math.pow(rgbFrom.b - b, 2)
                        )

                        if (colorDifference <= this.toleranceValue) {
                            data[index] = rgbTo.r
                            data[index + 1] = rgbTo.g
                            data[index + 2] = rgbTo.b
                            data[index + 3] = alpha
                        }
                    }
                }
            }

            context.putImageData(imageData, 0, 0)
        },
        removeColor(color) {
            const canvas = document.getElementById('gs-image-edit-canvas')
            const hiddenCanvas = document.getElementById('gs-image-edit-canvas-hidden')

            this.removeColorFromCanvas(canvas, color)
            this.removeColorFromCanvas(hiddenCanvas, color)

            this.hasChanged = true
        },
        replaceColor(colorFrom, colorTo) {
            const canvas = document.getElementById('gs-image-edit-canvas')
            const hiddenCanvas = document.getElementById('gs-image-edit-canvas-hidden')

            this.replaceColorFromCanvas(canvas, colorFrom, colorTo)
            this.replaceColorFromCanvas(hiddenCanvas, colorFrom, colorTo)

            this.hasChanged = true
        },
        handleOutsideClick(event) {
            const colorPickerElements = document.querySelectorAll('.color-picker-container');
            for (const element of colorPickerElements) {
                if (element.contains(event.target)) {
                    return;
                }
            }
            this.activeColorPicker = null;
        },
        mouseDownHandler(e) {
            this.imageMouseDown = true
            this.performAction(e)
        },
        pickCanvasColor(e) {
            if (!this.imageMouseDown) return false
            this.performAction(e)
        },
        performAction(e) {
            const canvas = e.target
            const context = canvas.getContext('2d')
            const rect = canvas.getBoundingClientRect()
            const x = ((e.pageX - rect.left - window.pageXOffset) * canvas.width) / rect.width
            const y = ((e.pageY - rect.top - window.pageYOffset) * canvas.height) / rect.height
            const imageData = context.getImageData(x, y, 1, 1).data
            const r = imageData[0]
            const g = imageData[1]
            const b = imageData[2]
            this.colorToRemove = '#' + (0x1000000 + (r << 16) + (g << 8) + b).toString(16).slice(1)
        },
        handleRevertChange() {
            this.newImageUrl = this.data.url
            this.loadImage()
            this.hasChanged = false
        },
        handleApply() {

            if (this.uploading) return

            const canvas = document.getElementById('gs-image-edit-canvas-hidden')
            if (canvas) {
                this.uploading = true
                const imageData = canvas.toDataURL()
                uploadBase64Image({
                    image: imageData,
                    user_id: this.shop.id,
                    parent_id: this.data.id,
                    customer_id: this.customer?.id || '',
                    session_id: getSessionId(),
                    image_name: this.imageName,
                    type: 'remove_bg',
                }).then(res => {
                    if (res.success) {
                        const image = {
                            id: res.image.id,
                            url: normalizeUrl(res.image.url),
                            thumb_url: normalizeUrl(res.image.thumb_url),
                            width: res.image.width,
                            height: res.image.height,
                            resolution: res.image.resolution,
                        }

                        this.$emit('confirm', image)
                        this.$emit('close')
                    }
                }).finally(() => {
                    this.uploading = false
                })
            }
        }
    }
})
</script>

<template>
    <Modal :open="open" @close="$emit('close')">
        <div class="bg-builder border flex flex-col text-left sm:rounded w-full max-h-full max-sm:h-full">
            <div class="flex justify-between items-center relative px-4 py-2">
                <h1 class="text-xl font-bold">{{ $t('Edit Image') }}</h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="p-4 flex-1 h-1 overflow-y-auto">
                <div class="flex max-md:flex-col max-md:space-y-4 md:space-x-4">
                    <div class="flex-1">
                        <div class="relative min-h-[500px]">
                            <div class="absolute">
                                <spinner v-if="loading"/>
                            </div>
                            <canvas
                                id="gs-image-edit-canvas"
                                class="transparent-pattern w-full border border-gray-300 cursor-crosshair"
                                style="width: 100% !important"
                                @mouseup="imageMouseDown = false"
                                @mousedown="mouseDownHandler"
                                @mousemove="pickCanvasColor"
                            />
                            <canvas hidden="hidden" id="gs-image-edit-canvas-hidden"/>
                        </div>
                    </div>
                    <div class="flex flex-col items-start w-full md:max-w-[240px]">
                        <button class="btn-builder btn-sm" @click="handleRemoveBackground" :disabled="removingBackground || hasChanged">
                            <spinner v-if="removingBackground" class="mr-2"/>
                            {{ $t('Remove Background') }}
                        </button>
                        <label v-if="!hasChanged" class="space-x-2 flex items-center text-sm mt-1 ml-1">
                            <input type="checkbox" v-model="removeShadow" :checked="removeShadow">
                            <span>{{ $t('Remove Shadow') }}</span>
                        </label>
                        <p class="flex px-1 items-center text-info">
                            <svg-icon type="mdi" :path="mdiInformationOutline" size="16" class="mr-1"/>
                            <small>{{ $t('Detect background using AI') }}</small>
                        </p>

                        <button class="btn-builder btn-sm mt-4" @click="removeColor('#ffffff')">
                            {{ $t('Remove All White') }}
                        </button>
                        <button class="btn-builder btn-sm mt-2" @click="removeColor('#000000')">
                            {{ $t('Remove All Black') }}
                        </button>
                        <div class="flex items-center justify-between w-full mt-4 z-10">
                            <button class="btn-builder btn-sm" @click="removeColor(colorToRemove)">
                                {{ $t('Remove Color') }}
                            </button>
                            <input v-model="colorToRemove" maxlength="7" class="w-24 h-8 inp-builder"/>
                            <div class="w-7 h-7 rounded border border-gray-300 cursor-pointer relative color-picker-container"
                                :style="{backgroundColor: colorToRemove}"
                                @click="activeColorPicker = 'remove'"
                                v-click-outside="handleOutsideClick"
                            >
                                <div v-if="activeColorPicker === 'remove'"
                                    class="ml-auto border rounded-lg bg-gray-300 p-1 absolute top-full right-0 mt-2 z-50"
                                    style="width: 200px"
                                    >
                                    <Sketch
                                        :value="colorToRemove"
                                        @update:modelValue="handleRemoveColorInput"
                                        class="custom-color-picker disable-alpha"
                                        :disable-alpha="true"
                                    />
                                </div>
                            </div>
                        </div>
                        <p class="flex px-1 items-center text-info">
                            <svg-icon type="mdi" :path="mdiInformationOutline" size="16" class="mr-1"/>
                            <small>{{ $t('Pick a color from the image.') }}</small>
                        </p>
                        <div class="w-full">
                            <span class="text-sm w-28">{{ $t('Tolerance') }}: {{ toleranceValue }}</span>
                            <div class="flex-1 w-full mt-2">
                                <input v-model="toleranceValue" type="range" :min="0" :max="100" :step="1" class="w-full"/>
                            </div>
                        </div>
                        <label class="space-x-2 flex items-center text-sm mt-3 ml-1">
                            <input type="checkbox" v-model="replaceColorEnabled">
                            <span>{{ $t('Enable Color Replacement') }}</span>
                        </label>
                        <div v-if="replaceColorEnabled" class="flex items-center justify-between w-full mt-2">
                            <button class="btn-builder btn-sm" @click="replaceColor(colorToRemove, colorToReplace)">
                                    {{ $t('Replace Color') }}
                            </button>
                            <input v-model="colorToReplace" maxlength="7" class="w-24 h-8 inp-builder"/>
                            <div class="w-7 h-7 rounded border border-gray-300 cursor-pointer relative color-picker-container"
                            :style="{backgroundColor: colorToReplace}"
                            @click="activeColorPicker = 'replace'"
                            v-click-outside="handleOutsideClick"
                            >
                                <div v-if="activeColorPicker === 'replace'"
                                    class="ml-auto border rounded-lg bg-gray-300 p-1 absolute top-full right-0 mt-2 z-50"
                                    style="width: 200px"
                                    >
                                    <Sketch
                                        :value="colorToReplace"
                                        @update:modelValue="handleReplaceColorInput"
                                        class="custom-color-picker disable-alpha"
                                        :disable-alpha="true"
                                    />
                                </div>
                            </div>
                        </div>
                        <p v-if="replaceColorEnabled" class="flex px-1 items-center text-info">
                            <svg-icon type="mdi" :path="mdiInformationOutline" size="16" class="mr-1"/>
                            <small>{{ $t('Replace selected color with this color') }}</small>
                        </p>

                        <div class="flex flex-1 py-5"></div>
                        <div class="!mt-auto flex justify-end w-full space-x-2">
                            <button v-if="hasChanged" class="btn-builder font-thin text-sm" @click="handleRevertChange">{{ $t('Revert Change') }}</button>
                            <button class="btn-builder font-thin text-sm" @click="handleApply" :disabled="uploading || !hasChanged">
                                <spinner v-if="uploading" class="mr-2"/>
                                {{ $t('Apply') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style>
.vc-sketch {
    width: 200px !important
}
</style>
