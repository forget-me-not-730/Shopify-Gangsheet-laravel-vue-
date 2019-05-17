<script>
import {defineComponent} from 'vue'
import rerenderMixin from '@/Builder/Mixins/rerenderMixin'
import GsCanvas from '@/Builder/Components/GsCanvas.vue'
import Spinner from '@/Builder/Components/Spinner.vue'
import {clearStorageDesignForVariant} from '@/Builder/Utils/helpers'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiHome} from '@mdi/js'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import {BUILD_TYPES} from "@/Builder/Utils/constants";
import builderMixin from '../Mixins/builderMixin'

export default defineComponent({
    name: 'AutoNestPreview',
    components: {Spinner, GsCanvas, SvgIcon},
    mixins: [gangSheetMixin, rerenderMixin, builderMixin],
    props: {
        packs: {
            type: Array,
            required: true
        },
        margin: {
            type: Number,
            required: 0
        },
        artboardMargin: {
            type: Number,
            required: 0
        }
    },
    data() {
        return {
            variant_id: null,
            jsons: [],
            variantCountMap: {},
            variantSheetsMap: {},
            loading: true,

            previewWidth: 0,

            mdiHome
        }
    },
    computed: {
        previewPacks() {
            const endPack = this.packs[this.packs.length - 1]
            if (this.shopSettings.autoBuildSetUp === 'recommended') {
                return [endPack]
            }
            return this.packs
        },
        availableVariants() {
            return this.previewPacks.map(p => p.variant)
        },
        selectedVariant() {
            return this.availableVariants.find(v => v.id === this.variant_id)
        }
    },
    watch: {
        async variant_id() {
            this.loading = true
            this.jsons = []
            this.previewWidth = 0

            await this.forceRerender()
        }
    },
    mounted() {
        this.variant_id = this.previewPacks[0].variant.id
        this.getImagesFromPacks()
    },
    methods: {
        getImagesFromPacks() {

            this.variantSheetsMap = {}
            this.variantCountMap = {}

            this.previewPacks.forEach(p => {
                this.variantSheetsMap[p.variant.id] = []
                this.variantCountMap[p.variant.id] = []

                p.bins.forEach(b => {
                    const index = this.variantCountMap[p.variant.id].findIndex(v => v.variant.id === b.variant.id)

                    if (index === -1) {
                        this.variantCountMap[p.variant.id].push({
                            variant: b.variant,
                            count: 1
                        })
                    } else {
                        this.variantCountMap[p.variant.id][index].count++
                    }

                    this.variantSheetsMap[p.variant.id].push({
                        variant: b.variant,
                        rects: b.rects,
                    })
                })
            })
        },
        async handleInitialized(canvas, sheet, index) {
            const sortedRects = sheet.rects.sort((a, b) => {
                return b.y - a.y
            })

            const viewPort = canvas.getViewPort()

            canvas.renderOnAddRemove = false

            const images = []

            await Promise.all(sortedRects.map(async (rect) => {
                const image = await fabric.util.makeObjectFromPack(rect, {
                    viewPort,
                    margin: this.margin,
                    artBoardMargin: this.artboardMargin,
                    unit: this.homeVariant.unit
                })
                canvas.add(image)

                if (!image.type.includes('text') && !images.find(i => i.id === image.id)) {
                    images.push({
                        id: image.id,
                        parentId: image.parentId,
                        isGalleryImage: image.isGalleryImage,
                        mimeType: image.mimeType,
                        url: rect.url,
                        thumb_url: image.src,
                        width: image.realWidth,
                        height: image.realHeight,
                    })
                }
            }))

            canvas.renderOnAddRemove = true

            canvas.renderAll()

            canvas.setImages(images)
            canvas.setBuildType(BUILD_TYPES.AUTO_BUILD)

            canvas.name = `Gang Sheet - (${index + 1})`

            this.jsons[index] = canvas.exportFinalJson()

            if (this.jsons.length === this.variantSheetsMap[this.variant_id].length) {
                this.loading = false
            }
        },
        async handleContinue() {
            if (this.loading) {
                return
            }

            this.loading = true
            clearStorageDesignForVariant()
            this.workingDesigns = this.jsons.filter(js => Boolean(js))
            this.workingDesignIndex = 0
            this.variant = this.selectedVariant
            this.loading = false
            this.openWorkingDesigns = true
            this.$emit('success')
        },
        previewItemStyle(sheet) {
            const height = 400

            if (!this.previewWidth) {
                this.previewWidth = height / sheet.variant.height * sheet.variant.width
            }

            return {
                width: `min(100%, ${this.previewWidth + 'px'})`
            }
        },
        getPatternVariant(variant) {
            const newVariant = {
                ...variant,
            }
            if (variant.pattern) {
                newVariant.pattern = {
                    ...variant.pattern,
                    objects: (variant.pattern.objects ?? []).map(obj => ({...obj, isPattern: true}))
                }
            }
            return newVariant;
        }
    }
})
</script>

<template>
    <div class="w-full h-full">
        <div class="h-full w-full flex max-sm:flex-col">
            <div class="max-sm:w-full w-[280px] space-y-2">
                <label class="w-full border p-1 rounded flex items-center justify-between cursor-pointer" v-for="v in availableVariants">
                    <span class="flex items-start w-full p-1 text-sm">
                        <input type="radio" class="mr-3 mt-0.5" :value="v.id" v-model="variant_id"/>
                        <div class="w-full space-y-1">
                            <div v-for="(vMap, index) in variantCountMap[v.id]" class="flex w-full justify-between">
                                <div class="flex items-center px-1 text-gray-500" :class="{'font-bold': index === 0, 'text-yellow-600': vMap.variant.visible === 'Hidden'}">
                                    <span> {{ vMap.variant.title || vMap.variant.label }} </span>
                                </div>
                                <span> x {{ vMap.count }}</span>
                            </div>
                        </div>
                    </span>
                </label>

                <div class="py-4 w-full flex items-center justify-end">
                    <button class="btn-builder-outline justify-center py-2 flex-1 px-0" @click="$emit('back')">{{ $t('Back & Adjust') }}</button>
                    <button class="btn-builder ml-2 py-2 px-0 flex-1" @click="handleContinue" :disabled="loading">
                        <Spinner v-if="loading" class="mr-1"/>
                        {{ $t('Continue') }}
                    </button>
                </div>
            </div>
            <div class="flex-1 sm:pl-2">
                <div v-if="renderComponent" class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-2">
                    <div v-for="(sheet, index) in variantSheetsMap[this.variant_id]" :key="index" class="w-full flex flex-col items-center pointer-events-none border p-1">
                        <div class="w-full text-center mb-1">
                            {{ sheet.variant.width }}{{ sheet.variant.unit }} x {{ sheet.variant.height }}{{ sheet.variant.unit }}
                        </div>
                        <hr class="w-full mb-1"/>
                        <div class="w-full bg-gray-100" :style="previewItemStyle(sheet)">
                            <div class="w-full relative" :style="{paddingTop: sheet.variant.height / sheet.variant.width * 100 + '%'}">
                                <div class="absolute inset-0">
                                    <gs-canvas :variant="getPatternVariant(sheet.variant)" :options="builderSettings" :compact="1" :show-resolution-lines="false"
                                               @initialized="handleInitialized($event, sheet, index)"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
