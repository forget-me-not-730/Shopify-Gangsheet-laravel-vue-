<template>
    <div class="w-full h-full relative overflow-y-auto tiny-scroll-bar">
        <div class="text-xs pb-2 pt-2">
            <div class="w-full">
                <div class="space-y-2 mt-3">
                    <div class="flex items-center justify-between mt-2">
                        <label>{{ $t('Saturation') }}: </label>
                        <div
                            class="flex items-center rounded-sm border-0 pr-2 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:sm:leading-6">
                            <input type="number" v-model="saturationImage" min="-1" max="1" step="0.01"
                                   class="w-20 text-xs block border-0 bg-transparent py-1.5 placeholder:text-gray-400 focus:ring-0 sm:sm:leading-6"
                                   @input="handleSaturationImage">
                            <input type="range" v-model="saturationImage" :min="-1" :max="1" :step="0.01" class="w-40 h-2"
                                   @input="handleSaturationImage"/>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <label>{{ $t('Contrast') }}: </label>
                        <div
                            class="flex items-center rounded-sm border-0 pr-2 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:sm:leading-6">
                            <input type="number" v-model="contrastImage" min="-1" max="1" step="0.01"
                                   class="w-20 text-xs block border-0 bg-transparent py-1.5 placeholder:text-gray-400 focus:ring-0 sm:sm:leading-6"
                                   @input="handleContrastImage">
                            <input type="range" v-model="contrastImage" :min="-1" :max="1" :step="0.01" class="w-40 h-2"
                                   @input="handleContrastImage"/>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <label>{{ $t('Brightness') }}: </label>
                        <div
                            class="flex items-center rounded-sm border-0 pr-2 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:sm:leading-6">
                            <input type="number" v-model="brightnessImage" min="-1" max="1" step="0.01"
                                   class="w-20 block text-xs border-0 bg-transparent py-1.5 placeholder:text-gray-400 focus:ring-0 sm:sm:leading-6"
                                   @input="handleBrightnessImage">
                            <input type="range" v-model="brightnessImage" :min="-1" :max="1" :step="0.01" class="w-40 h-2"
                                   @input="handleBrightnessImage"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full mt-4 flex justify-end">
                <button class="btn-builder btn-sm w-full" @click="clearFilters">
                    Reset
                </button>
            </div>
            <div v-if="showFilters" class="w-full grid grid-cols-2 gap-2 mt-2">
                <div class="w-full border flex flex-col items-center justify-center cursor-pointer text-gray-500 rounded"
                     @click="clearFilters"
                     @touchstart="clearFilters"
                     :class="{'border-blue-500': selectedImageIndex === null}"
                >
                    <cancel-icon size="80"/>
                    <span>None</span>
                </div>
                <template v-for="(filter, index) in filters" :key="index">
                    <div class="w-full border border-gray-300 cursor-pointer rounded"
                         @click="handleFilteredImageClick(index)"
                         @touchstart="handleFilteredImageClick(index)"
                         :class="{'border-blue-500': selectedImageIndex === index}">
                        <filtered-image :src="imageSrc" :filters="filter"/>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import eventBus from '@/Builder/Utils/eventBus'
import FilteredImage from "@/Builder/Sticker/FilteredImage.vue";
import {FILTERS} from "@/Builder/Utils/constants";
import CancelIcon from '@/Builder/Icons/CancelIcon.vue'

export default {
    name: 'ImageSettingPanel',
    components: {FilteredImage, CancelIcon},
    data() {
        return {
            saturationImage: 0,
            contrastImage: 0,
            brightnessImage: 0,
            background: null,
            filters: FILTERS,
            selectedImageIndex: null,
            showFilters: false
        }
    },
    watch: {
        saturationImage(newValue) {
            if (!newValue) {
                this.handleSaturationImage()
            }
        },
        contrastImage(newValue) {
            if (!newValue) {
                this.handleContrastImage()
            }
        },
        brightnessImage(newValue) {
            if (!newValue) {
                this.handleBrightnessImage()
            }
        }
    },
    computed: {
        imageSrc() {
            return this.background ? this.background.src : null;
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
                'after:render': listener
            })
        },
        updateDimensions(canvas) {
            const background = canvas.getBackground();
            if (background) {
                this.background = background

                const filters = background.get('filters') || [];
                this.saturationImage = 0;
                this.contrastImage = 0;
                this.brightnessImage = 0;

                filters.forEach(filter => {
                    if (filter.type === 'Saturation') {
                        this.saturationImage = filter.saturation || this.saturationImage;
                    }
                    if (filter.type === 'Contrast') {
                        this.contrastImage = filter.contrast || this.contrastImage;
                    }
                    if (filter.type === 'Brightness') {
                        this.brightnessImage = filter.brightness || this.brightnessImage;
                    }
                });
            }
        },
        handleFilteredImageClick(index) {
            const selected = _stickerCanvasEditor.getBackground()
            const filter = this.filters[index]

            selected.filters = []
            selected.set({opacity: 1}).setCoords()

            const brightnessFilter = new fabric.Image.filters.Brightness({brightness: filter.brightness})
            const contrastFilter = new fabric.Image.filters.Contrast({contrast: filter.contrast})
            const saturationFilter = new fabric.Image.filters.Saturation({saturation: filter.saturation})
            selected.filters.push(brightnessFilter, contrastFilter, saturationFilter)

            if (filter.color) {
                const blendColorFilter = new fabric.Image.filters.BlendColor({color: filter.color, mode: "overlay"})
                selected.filters.push(blendColorFilter)
            }

            if (filter.opacity) {
                selected.set({opacity: filter.opacity}).setCoords()
            }

            selected.applyFilters()
            _stickerCanvasEditor.renderAll()

            this.selectedImageIndex = index
        },
        handleSaturationImage() {
            const saturation = parseFloat(this.saturationImage);
            const selected = _stickerCanvasEditor.getBackground();
            selected.filters = selected.filters.filter(filter => !(filter instanceof fabric.Image.filters.Saturation));
            if (saturation !== 0) {
                const filter = new fabric.Image.filters.Saturation({saturation: saturation})
                selected.filters.push(filter);
            }
            selected.applyFilters();
            _stickerCanvasEditor.renderAll();
        },
        handleContrastImage() {
            const contrast = parseFloat(this.contrastImage);
            const selected = _stickerCanvasEditor.getBackground();
            selected.filters = selected.filters.filter(filter => !(filter instanceof fabric.Image.filters.Contrast));
            if (contrast !== 0) {
                const filter = new fabric.Image.filters.Contrast({contrast: contrast})
                selected.filters.push(filter);
            }
            selected.applyFilters();
            _stickerCanvasEditor.renderAll();
        },
        handleBrightnessImage() {
            const brightness = parseFloat(this.brightnessImage);
            const selected = _stickerCanvasEditor.getBackground()
            selected.filters = selected.filters.filter(filter => !(filter instanceof fabric.Image.filters.Brightness));
            if (brightness !== 0) {
                const filter = new fabric.Image.filters.Brightness({brightness: brightness})
                selected.filters.push(filter);
            }
            selected.applyFilters();
            _stickerCanvasEditor.renderAll();
        },
        clearFilters() {
            const selected = _stickerCanvasEditor.getBackground()
            selected.filters = []
            selected.set({opacity: 1}).setCoords()
            selected.applyFilters()
            _stickerCanvasEditor.renderAll()

            this.selectedImageIndex = null;
        }
    }
}
</script>

<style>

</style>
