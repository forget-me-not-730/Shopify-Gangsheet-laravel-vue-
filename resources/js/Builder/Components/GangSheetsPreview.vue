<script>
import {defineComponent} from 'vue'
import GsCanvas from '@/Builder/Components/GsCanvas.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiSquareEditOutline, mdiCheckboxMarkedCircleOutline, mdiHome, mdiCloseCircleOutline} from '@mdi/js'
import eventBus from '@/Builder/Utils/eventBus'
import {cloneDeep} from 'lodash'
import confirmationMixin from '@/Builder/Mixins/confirmationMixin'
import designSubmitMixin from '@/Builder/Mixins/designSubmitMixin'
import {ART_BOARD_TYPES, MODAL_NAMES} from '@/Builder/Utils/constants'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'

export default defineComponent({
    name: 'GangSheetsPreview',
    mixins: [gangSheetMixin, confirmationMixin, designSubmitMixin],
    components: {SvgIcon, GsCanvas},
    data() {
        return {
            loading: false,
            previewWidth: 0,
            editNameIndex: null,
            canvases: {},

            mdiSquareEditOutline,
            mdiHome,
            mdiCheckboxMarkedCircleOutline,
            mdiCloseCircleOutline
        }
    },
    computed: {
        showQuantity() {
            if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                return false
            }

            return this.builderSettings.enableQuantity
        }
    },
    mounted() {
        eventBus.$on(eventBus.DESIGNS_CONFIRM_AND_ADD_TO_CART, this.saveAndAddToCartAll)
    },
    unmounted() {
        eventBus.$off(eventBus.DESIGNS_CONFIRM_AND_ADD_TO_CART, this.saveAndAddToCartAll)
    },
    methods: {
        cloneDeep,
        isLoadingCanvas() {
            return Object.keys(this.canvases).length !== this.workingDesigns.length
        },
        updateCanvas(id, canvas) {
            this.canvases[id] = canvas;
        },
        handleDesignNameUpdate(e, designIndex) {
            const name = e.target.textContent.trim().replace('\n', ' ').substring(0, 100) || 'Untitled'
            this.workingDesigns[designIndex].name = name
            this.editNameIndex = null
            e.target.innerHTML = name
        },
        previewItemStyle(variant) {
            const height = 300
            const previewWidth = height / variant.height * variant.width
            return {
                width: `min(100%,${previewWidth + 'px'})`
            }
        },
        handleEditDesignNameClick(e, index) {
            e.stopImmediatePropagation()
            this.editNameIndex = index
            this.$refs.designNameRef[index]?.focus()
        },
        handleInitialized(canvas, design) {
            this.updateCanvas(design.id, canvas);
        },
        handleDesignRemove(index) {
            const removeDesign = () => {
                this.workingDesigns.splice(index, 1)
                this.workingDesignIndex = 0
            }

            if (this.workingDesigns[index].objects.length) {
                this.confirmation = {
                    title: 'Remove Design',
                    description: 'Are you really sure you want to remove the design?',
                    onConfirm: () => {
                        removeDesign()
                    }
                }
            } else {
                removeDesign()
            }
        },
        handleEditDesign(index) {
            if (this.product?.art_board_type !== ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                this.variant = this.workingDesigns[index].meta.variant
            }
            this.workingDesignIndex = index
            this.gangSheetsPreview = false
        },
        async saveAndAddToCartAll() {

            if (this.loading) {
                return
            }

            if (this.isLoadingCanvas()) {
                return window.Toast.error({
                    message: 'Please wait for the designs to load.'
                })
            }

            if (this.workingDesigns.some(d => d.objects.length === 0 || d.objects.every(obj => obj.isPattern))) {
                return window.Toast.error({
                    message: 'There are some blank designs.'
                })
            }

            if (this.workingDesigns.some(d => Number(d.quantity) < 1)) {
                return window.Toast.error({
                    message: 'Please input quantity.'
                })
            }

            if (this.workingDesigns.some(d => d.objects.some(obj => !obj.isPattern && obj.type.includes('text') && obj.text?.toLowerCase() === 'gang sheet'))) {
                this.confirmation = {
                    title: 'Confirm',
                    description: 'Seems like there is placeholder text on the design.\n Are you sure you want to save it?',
                    onConfirm: () => {
                        this.handleSave()
                    }
                }
            } else {
                await this.handleSave()
            }
        },
        async handleSave() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.AGREE_ALL,
                data: {
                    hasQualityConfirm: this.workingDesigns.some(d => d.qualityError)
                },
                onChange: async (createNew) => {

                    this.loading = true
                    NProgress.start()

                    const postDesigns = await Promise.all(this.workingDesigns.map(async (d) => {
                        const canvas = this.canvases[d.id]
                        const thumbnail = await canvas.exportThumbnail()

                        return {
                            json: d,
                            thumbnail
                        }
                    }))

                    eventBus.$emit(eventBus.DESIGNS_ADD_TO_CART, {
                        designs: postDesigns,
                        createNew,
                        callback: () => {
                            if (createNew) {
                                NProgress.done()
                                this.workingDesigns = []
                                this.workingDesignIndex = 0
                                this.hasDesignChange = false
                                this.savingDesign = false
                                this.gangSheetsPreview = false
                            }
                        }
                    })
                }
            })
        },
        variantLabel(variant) {
            return variant.title || variant.label || `${variant.width} ${variant.unit} x ${variant.height} ${variant.unit}`
        }
    }
})
</script>

<template>
    <div class="w-full h-full bg-builder">
        <div class="w-full overflow-y-auto tiny-scroll-bar p-5 h-full pb-20">
            <div class="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-4 w-full mx-auto max-w-7xl">
                <div v-for="(design, index) in workingDesigns" :key="design.id" class="w-full flex flex-col items-center rounded border p-1">
                    <div class="w-full pointer-events-none" :style="previewItemStyle(design.meta.variant)"
                         :class="[design.objects.length ? 'bg-gray-100' : 'bg-red-100 border border-red-300', {'border border-red-300': design.qualityError}]">
                        <div class="w-full relative" :style="{paddingTop: design.meta.variant.height / design.meta.variant.width * 100 + '%'}">
                            <div v-if="design.objects.length" class="absolute inset-0">
                                <gs-canvas :variant="design.meta.variant" :json-data="cloneDeep(design)" :options="shop.settings" :compact="1" :show-resolution-lines="false"
                                           @initialized="handleInitialized($event, design, index)"/>
                            </div>
                            <div v-else class="absolute inset-0 whitespace-nowrap flex items-center justify-center text-gray-400">
                                No Images
                            </div>
                        </div>
                    </div>
                    <hr class="w-full mt-auto"/>
                    <div class="p-1 w-full">
                        <div class="mb-2 text-xs">
                            <div class="flex items-center space-x-2">
                                <div v-if="!design.imagesError">
                                    <svg-icon type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-green-500"/>
                                </div>
                                <div v-else>
                                    <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="18" class="text-red-500"/>
                                </div>
                                <span>{{ $t('No overlapping images.') }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div v-if="!design.resolutionError">
                                    <svg-icon type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-green-500"/>
                                </div>
                                <div v-else>
                                    <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="18" class="text-red-500"/>
                                </div>
                                <span>{{ $t('No low-resolution images.') }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div v-if="!design.artBoardError">
                                    <svg-icon type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-green-500"/>
                                </div>
                                <div v-else>
                                    <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="18" class="text-red-500"/>
                                </div>
                                <span>{{ $t('No items overlapping the artboard.') }}</span>
                            </div>
                        </div>

                        <hr class="mb-2 mt-auto"/>

                        <div class="w-full flex items-center justify-between mb-1 text-sm">
                            <div class="flex items-center pl-1 text-xs text-text-500" :class="{'font-bold': index === 0, 'text-yellow-600': design.meta.variant.visible === 'Hidden'}">
                                <svg-icon v-if="index === 0" type="mdi" :path="mdiHome" size="18" class="mr-1"/>
                                <span>{{ variantLabel(design.meta.variant) }}</span>
                            </div>
                            <div v-if="showQuantity" class="flex items-center space-x-4">
                                <span>Qty:</span>
                                <input type="number" v-model="design.quantity" step="1" min="1" class="inp-builder  h-7 w-16" :class="{'border-red-500': !Boolean(design.quantity)}">
                            </div>
                        </div>
                        <div class="w-full flex justify-between mb-1 font-medium">
                            <div :contenteditable="editNameIndex === index" ref="designNameRef" class="p-1 flex-1" :class="{'cursor-text ring-2 ring-gray-900 rounded': editNameIndex === index}"
                                 @focusout="handleDesignNameUpdate($event, index)">
                                {{ design.name }}
                            </div>

                            <div v-if="editNameIndex !== index" class="pt-2 ml-2 cursor-pointer" @click="handleEditDesignNameClick($event, index)">
                                <svg-icon type="mdi" :path="mdiSquareEditOutline" size="20"/>
                            </div>
                        </div>

                    </div>

                    <hr class="mb-2 w-full"/>

                    <div class="flex w-full items-center justify-end space-x-2">
                        <button :disabled="loading" class="btn-builder btn-sm" @click="handleEditDesign(index)">{{ $t('Edit') }}</button>
                        <button :disabled="loading" v-if="workingDesigns.length > 1 && index > 0" class="btn-danger btn-sm" @click="handleDesignRemove(index)">{{ $t('Remove') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
