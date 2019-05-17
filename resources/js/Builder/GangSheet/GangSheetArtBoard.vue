<template>
    <div class="flex z-10 bg-builder w-full h-full relative"
         :class="{'pointer-events-none': loading || savingDesign || loadingDesign}">

        <div v-if="loading || loadingDesign" class="absolute inset-0 bg-builder z-50 flex items-center justify-center opacity-75">
            <spinner class="!w-8 !h-8"/>
        </div>

        <div v-if="autoNestMode" class="absolute inset-0 bg-builder z-50 flex items-center justify-center">
            <auto-nest ref="autoNest" class="pt-5 px-5" @close="autoNestMode = false"/>
        </div>

        <div v-show="!autoNestMode" class="flex flex-col relative w-full">

            <builder-alerts v-if="!loadingDesign"/>

            <div class="absolute max-xs:left-6 left-8 max-xs:bottom-6 bottom-8 z-30 flex space-x-2">
                <div class="cursor-pointer btn-builder px-0 py-0 rounded-full shadow-lg w-8 h-8" @click="handleRefresh">
                    <svg-icon type="mdi" :path="mdiRefresh" size="18" :class="{'animate-spin': refreshing}"/>
                </div>

                <button v-if="permissions.autoNest" @click="autoNestMode = true" :disabled="loading" class="btn-builder btn-sm text-xs">
                    {{ $t('Auto Build') }}
                </button>
            </div>

            <div class="w-full relative h-[40px] z-50">
                <div class="absolute max-md:left-0 max-md:w-full left-[-320px] xl:left-0 md:w-[calc(100%+320px)] h-full xl:w-full flex items-center bg-builder border-b">
                    <div class="p-1.5 w-42 shrink-0">
                        <div class="w-full choose-variant">
                            <variants v-model="variant.id" @change="handleSizeChange" :disabled="variantDisabled"/>
                        </div>
                    </div>
                    <div v-if="!loading" class="flex flex-1 w-1">
                        <div class="min-h-[40px] flex items-center">

                            <div class="border-l-2 px-1 h-full flex items-center ml-2">
                                <button
                                    title="Undo (Ctrl+Z)"
                                    :disabled="!hasUndoHistory"
                                    class="w-6 h-6 border rounded flex items-center justify-center cursor-pointer active:bg-white active:gs-text-primary active:border active:border-info disabled:border-none disabled:text-gray-400"
                                    @click="handleUndo"
                                    :class="{'gs-bg-primary': hasUndoHistory}"
                                >
                                    <svg-icon type="mdi" size="18" :path="mdiUndo"/>
                                </button>

                                <button
                                    title="Redo (Ctrl+Y)"
                                    :disabled="!hasRedoHistory"
                                    class="w-6 h-6 border rounded flex items-center justify-center cursor-pointer ml-2 active:bg-white active:gs-text-primary active:border active:border-info disabled:border-none disabled:text-gray-400"
                                    @click="handleRedo"
                                    :class="{'gs-bg-primary': hasRedoHistory}"
                                >
                                    <svg-icon type="mdi" :path="mdiRedo" size="18" :class="{'text-white': hasRedoHistory}"/>
                                </button>

                                <button
                                    title="Save"
                                    class="hidden items-center justify-center cursor-pointer ml-2 text-danger text-sm font-medium whitespace-nowrap"
                                    @click="handleDeleteAll"
                                >
                                    Delete All
                                </button>
                            </div>

                            <div class="border-l-2 px-1 h-full flex items-center">
                                <button
                                    title="Move mode"
                                    class="w-6 h-6 border rounded flex items-center justify-center cursor-pointer"
                                    @click="moveMode = !moveMode"
                                    :class="{'gs-bg-primary': moveMode}"
                                >
                                    <svg-icon type="mdi" size="16" :path="mdiHandBackLeftOutline" :class="{'text-white': moveMode}"/>
                                </button>

                                <button
                                    title="Grid view"
                                    class="w-6 h-6 border max-sm:hidden rounded flex items-center justify-center cursor-pointer ml-2"
                                    @click="gridMode = !gridMode"
                                    :class="{'gs-bg-primary': gridMode}"
                                >
                                    <svg-icon type="mdi" :path="mdiGrid" size="16" :class="{'text-white': gridMode}"/>
                                </button>
                            </div>

                            <div v-if="!this.patternMode" class="max-lg:hidden border-l-2 px-1 h-full flex items-center">
                                <setting-margin/>
                            </div>
                        </div>
                        <div v-if="objectSelected" class="max-md:hidden overflow-x-auto">
                            <control-bar v-if="objectSelected" :is-group-selection="isGroupSelection"/>
                        </div>
                    </div>
                    <div v-if="!loading && !this.patternMode" class="ml-auto h-[40px] flex items-center justify-center">
                        <auto-nest-button/>
                    </div>
                </div>
            </div>
            <div class="md:hidden overflow-x-auto h-[40px] border-b">
                <control-bar v-if="objectSelected" :is-group-selection="isGroupSelection"/>
                <div v-else class="h-[39px] border-l-2 md:hidden flex items-center text-gray-400 pl-2">
                    <svg-icon type="mdi" :path="mdiSelect" size="18" class="mr-2"/>
                    <span class="text-sm">{{ $t('Select an object to control.') }}</span>
                </div>
            </div>

            <div
                class="editor-root flex-1 h-1 w-full relative overflow-hidden transparent-pattern"
                ref="editorRoot"
                @mousedown="handleMouseDown"
                @mouseleave="handleMouseLeave"
                @touchstart="handleTouchStart($event, 'mousePressed')"
                :class="{'cursor-grab': isMoving || spaceKyePressed}"
            >
                <ruler :translate-y="translateY" :translate-x="translateX" :zoom="zoom" :width="variant.width"
                       :height="variant.height" :grid-mode="gridMode" :unit="variant.unit"/>

                <div class="absolute left-0 bottom-0 h-4 right-0 z-20 bg-builder border-t flex justify-center touch-none">
                    <div
                        class="btn-builder justify-between rounded-full px-0 w-48 cursor-pointer hover:bg-primary text-white user-select-none"
                        :style="{transform: `translate(${scrollX}px, 0px)`}"
                        @mousedown="scrollHEnabled = true"
                        @touchstart.prevent="handleTouchStart($event, 'scrollHEnabled')"
                    >
                        <svg-icon type="mdi" :path="mdiChevronLeft" size="18"/>
                        <svg-icon type="mdi" :path="mdiChevronRight" size="18"/>
                    </div>
                </div>
                <div
                    class="absolute right-0 h-full w-4 bottom-0 z-40 cursor-pointer bg-builder border-l flex flex-col justify-center touch-none">
                    <div
                        class="btn-builder px-0 py-0 flex-col justify-between rounded-full h-48 hover:bg-primary text-white user-select-none"
                        :style="{transform: `translate(0px, ${scrollY}px)`}"
                        @mousedown="scrollVEnabled = true"
                        @touchstart.prevent="handleTouchStart($event, 'scrollVEnabled')"
                    >
                        <svg-icon type="mdi" :path="mdiChevronUp" size="18"/>
                        <svg-icon type="mdi" :path="mdiChevronDown" size="18"/>
                    </div>
                </div>

                <selected-object-info/>

                <context-menu :object-selected="objectSelected"/>

                <slot v-if="$slots['right-panel']" name="right-panel"/>
                <working-designs v-else-if="!editMode"/>

                <div class="absolute w-max top-[24px] flex items-center right-[20px] z-30 bg-builder rounded max-sm:text-xs">
                    <div title="Fit to screen" class="btn-builder max-sm:rounded-sm rounded mr-2 py-0.5 px-1 cursor-pointer"
                         @click="handleRest(false)">
                        <svg-icon type="mdi" :size="16" :path="mdiFitToScreenOutline"/>
                    </div>
                    <div class="max-md:hidden btn-builder rounded mr-4 py-px px-2 cursor-pointer text-xs" @click="handleRest(true)">
                        <span title="reset editor view">100%</span>
                    </div>
                    <div class="max-sm:w-5 max-sm:h-5 w-6 h-[22px] bg-builder border rounded flex items-center justify-center cursor-pointer"
                         @click="handleZoomOut">
                        <svg-icon type="mdi" :path="mdiMagnifyMinusOutline" size="16"/>
                    </div>
                    <span class="px-1 w-14 text-center text-xs">{{ (zoom * 100).toFixed(2) }}%</span>
                    <div class="max-sm:w-5 max-sm:h-5 w-6 h-[22px] bg-builder border rounded flex items-center justify-center cursor-pointer"
                         @click="handleZoomIn">
                        <svg-icon type="mdi" :path="mdiMagnifyPlusOutline" size="16"/>
                    </div>
                </div>

                <div
                    v-if="renderComponent"
                    ref="editorRef" class="editor-wrap w-full h-full relative"
                    :class="{'pointer-events-none': this.spaceKyePressed || this.moveMode || zooming}"
                    @wheel.prevent="handleScroll"
                    @touchstart.prevent="handleEditorTouchStart"
                    @drop="handleDrop"
                >
                    <div class="editor-bound relative" :style="editorBoundStyle"/>
                    <div class="flex items-center justify-center absolute left-0 top-0 w-full h-full">
                        <gs-canvas :variant="canvasVariant" :json-data="currentDesign" :options="getCanvasOptions()" @initialized="handleCanvasInitialized"/>
                    </div>
                    <div class="snap-line-container absolute pointer-events-none" :style="snapLineContainerStyle">
                        <template v-if="snapEnabled && !isMoving">
                            <div
                                v-for="(positionX, index) in snapXPositions"
                                :key="index"
                                class="absolute w-px h-[5000px] -top-[2000px] bg-info z-50 "
                                :style="{left: positionX * zoom  + 'px'}"
                            ></div>
                            <div
                                v-for="(positionY, index) in snapYPositions"
                                :key="index"
                                class="absolute h-px w-[5000px] -left-[2000px] bg-info z-50"
                                :style="{top: positionY * zoom + 'px'}"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <approve-edit-request-modal v-if="isAdminEdit"/>
</template>

<script>
import eventBus from '@/Builder/Utils/eventBus'
import Spinner from '@/Builder/Components/Spinner.vue'
import {defineAsyncComponent} from 'vue'
import {
    mdiFitToScreenOutline,
    mdiRefresh,
    mdiUndo,
    mdiRedo,
    mdiHandBackLeftOutline,
    mdiGrid,
    mdiChevronDown,
    mdiChevronUp,
    mdiChevronLeft,
    mdiChevronRight,
    mdiMagnifyMinusOutline,
    mdiMagnifyPlusOutline,
    mdiSelect,
    mdiFullscreen
} from '@mdi/js'
import SvgIcon from '@jamescoyle/vue-icon'
import GsCanvas from '../Components/GsCanvas.vue'
import rerenderMixin from '@/Builder/Mixins/rerenderMixin'
import {clearStorageDesignForVariant, getSessionId} from '@/Builder/Utils/helpers'
import {getDesign, saveDraftDesign} from '@/Builder/Apis/builderApi'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import {cloneDeep} from 'lodash'

import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import snapMixin from '@/Builder/Mixins/snapMixin'

import ControlBar from '../Components/ControlBar.vue'
import SelectedObjectInfo from '../Components/SelectedObjectInfo.vue'
import Variants from '../Components/Variants.vue'
import Ruler from '../Components/Ruler.vue'
import WorkingDesigns from '../Components/WorkingDesigns.vue'
import SettingMargin from '../ArtboardSettings/SettingMargin.vue'
import SettingArtboardMargin from '../ArtboardSettings/SettingArtboardMargin.vue'
import SettingBackground from '../ArtboardSettings/SettingBackground.vue'
import SettingSnapOnOff from '../ArtboardSettings/SettingSnapOnOff.vue'
import SettingVisualQuality from '@/Builder/ArtboardSettings/SettingVisualQuality.vue'
import artBoardMixin from "@/Builder/Mixins/artBoardMixin";
import AutoNestButton from "@/Builder/GangSheet/AutoNestButton.vue";
import ContextMenu from '../Components/ContextMenu.vue'

const ApproveEditRequestModal = defineAsyncComponent(() => import('../Modals/ApproveEditRequestModal.vue'))
const AutoNest = defineAsyncComponent(() => import('../Components/AutoNest.vue'))
const BuilderAlerts = defineAsyncComponent(() => import('../Components/BuilderAlerts.vue'))

window._gangSheetCanvasEditor = null

export default {
    name: 'Editor',
    components: {
        AutoNestButton,
        SettingVisualQuality,
        GsCanvas,
        ApproveEditRequestModal,
        BuilderAlerts,
        WorkingDesigns,
        AutoNest,
        Variants,
        SettingMargin,
        SettingArtboardMargin,
        SelectedObjectInfo,
        SettingSnapOnOff,
        SettingBackground,
        ControlBar,
        Ruler,
        Spinner,
        SvgIcon,
        ContextMenu
    },
    mixins: [gangSheetMixin, rerenderMixin, snapMixin, artBoardMixin],
    data() {
        return {
            designWidth: 0,
            designHeight: 0,
            screenWidth: 0,
            screenHeight: 0,
            editor: null,

            objectSelected: false,

            editBoundBorderWidth: 1,

            isGroupSelection: false,
            downloadingImage: false,

            loading: true,

            objectOffBoarding: false,

            refreshing: false,

            mdiFitToScreenOutline,
            mdiRefresh,
            mdiUndo,
            mdiRedo,
            mdiGrid,
            mdiHandBackLeftOutline,
            mdiChevronDown,
            mdiChevronUp,
            mdiChevronLeft,
            mdiChevronRight,
            mdiMagnifyMinusOutline,
            mdiSelect,
            mdiMagnifyPlusOutline,
            mdiFullscreen
        }
    },
    computed: {
        editorBoundStyle() {
            return {
                width: this.designWidth * this.zoom + this.editBoundBorderWidth * 2 + 'px',
                height: this.designHeight * this.zoom + this.editBoundBorderWidth * 2 + 'px',
                transform: `translate(${this.translateX}px, ${this.translateY}px)`,
                '--border-width': this.editBoundBorderWidth + 'px',
                '--border-color': this.objectOffBoarding ? '#ff0000' : '#2590c0'
            }
        },
        snapLineContainerStyle() {
            return {
                width: Math.round(this.designWidth * this.zoom) + this.editBoundBorderWidth * 2 + 'px',
                height: Math.round(this.designHeight * this.zoom) + this.editBoundBorderWidth * 2 + 'px',
                transform: `translate(${this.translateX}px, ${this.translateY}px)`,
            }
        },
        variantDisabled() {
            if (this.token) {
                return false
            }

            if (this.applyDiscountPrice && this.variant.height >= this.discountThreshold && this.workingDesigns.length > 1) {
                return true
            }

            if (this.variant.visible === 'Hidden') {
                return true
            }

            if (this.isWooCommerce && this.editMode) {
                return true
            }

            if (!this.isStandalone && (this.editMode && window.top === window.self)) {
                return true
            }

            return Boolean(this.order) || this.autoNestMode
        },
        canvasVariant() {
            let variant = {...this.variant}
            if (!this.patternMode)
                variant.pattern = null
            return variant
        },
    },
    watch: {
        gridMode() {
            window._gangSheetCanvasEditor.setGridMode(this.gridMode)
        },
        async variant() {
            this.loading = true
            await this.forceRerender()
        }
    },
    mounted() {
        eventBus.$on(eventBus.SAVE_DRAFT_DESIGN, this.handleSaveDraftDesign)
        eventBus.$on(eventBus.OPEN_NEW_DESIGN, this.handleOpenNewDesign)

        window.parent.postMessage({
            action: 'gsb-loaded'
        }, '*')
    },
    unmounted() {
        eventBus.$off(eventBus.SAVE_DRAFT_DESIGN, this.handleSaveDraftDesign)
        eventBus.$off(eventBus.OPEN_NEW_DESIGN, this.handleOpenNewDesign)

        if (window._gangSheetCanvasEditor?.isEmpty()) {
            clearStorageDesignForVariant(this.variant.id)
        } else {
            this.$gsb.updateCanvasData()
        }

        window._gangSheetCanvasEditor = null
    },
    methods: {
        async handleRefreshBuilder(designJson) {
            if (designJson) {
                window._gangSheetCanvasEditor.clear()
                this.variant = designJson.meta.variant
                clearStorageDesignForVariant(this.variant.id)
                this.currentDesign = designJson
                this.images = designJson.meta.images ?? []
            } else {
                await this.$gsb.updateCanvasData()
            }
            this.loading = true
            await this.forceRerender()
        },
        async handleOpenDesign(designId) {
            this.$gsb.updateCanvasData()
            await this.openDesign(designId)
        },
        async handleOpenNewDesign() {
            if (this.hasDesignChange) {
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.CONFIRM_DESIGN_SAVE,
                    onChange: async (save) => {
                        if (save) {
                            await this.saveDraftDesign(true)
                        } else {
                            await this.openNewDesign()
                        }
                    }
                })
            } else {
                await this.openNewDesign()
            }
        },
        handleApplyPattern() {
            if (this.builderSettings.productPattern?.enabled ?? false) {
                _gangSheetCanvasEditor.applyPattern(this.variant.pattern)
            } else {
                _gangSheetCanvasEditor.removePattern()
            }
            _gangSheetCanvasEditor.renderAll()
        },
        async handleSaveDraftDesign(option = {}) {
            await this.saveDraftDesign()
            if (typeof option.callback === 'function') {
                option.callback()
            }
        },
        async handleCanvasInitialized(canvas) {
            window._gangSheetCanvasEditor = canvas

            this.hasUndoHistory = false
            this.hasRedoHistory = false
            this.hasDesignChange = false

            this.refreshing = false

            this.screenWidth = canvas.width
            this.screenHeight = canvas.height

            this.designWidth = canvas.designWidth
            this.designHeight = canvas.designHeight

            this.zoom = canvas.getZoom()

            if (this.variantUpdated && !this.patternMode) {
                canvas.fitDesignToTopLeft()
                if (this.shopSettings.autoResizeSingleImage) {
                    canvas.fitImageToWidth()
                }
                this.variantUpdated = false
            }

            this.loading = false

            if (this.patternMode) {
                canvas.showResolutionLines = false
                canvas.showMarginOutlines = false
                canvas.renderAll()
            } else {
                canvas.setVariant(this.variant)
                this.handleApplyPattern()
            }

            this.objectOffBoarding = canvas.artBoardError;

            await this.$nextTick(() => {
                if (!this.patternMode) {
                    canvas.checkDesignOverlapping()
                    canvas.checkMarginOverlapping()
                }
                this.$gsb.updateCanvasData()
                eventBus.$emit(eventBus.CANVAS_INITIALIZED, canvas)
            })

            this.originalTransform = canvas.viewportTransform.slice()
            this.onCanvasTransform(this.originalTransform)

            const handleSelectionCreation = () => {
                const selected = canvas.getActiveObject()
                if (selected && selected._objects) {
                    this.isGroupSelection = true
                } else {
                    this.isGroupSelection = false
                    this.backgroundRemoved = selected.backgroundRemoved
                }
                this.objectSelected = true
            }

            const handleSelectionCleared = () => {
                this.objectSelected = false
            }

            canvas.on({
                'selection:created': handleSelectionCreation,
                'selection:updated': handleSelectionCreation,
                'selection:cleared': handleSelectionCleared,
                'object:snap': () => {
                    this.handleSnap(canvas)
                },
                'mouse:down': this.enableSnapLines,
                'mouse:up': this.disableSnapLines,
                'history:append': this.updateHistory,
                'history:redo': this.updateHistory,
                'history:undo': this.updateHistory,
                'history:clear': this.updateHistory,
                'object:off-board': ({status}) => {
                    this.objectOffBoarding = status
                    this.editBoundBorderWidth = status ? 2 : 1
                }
            })

            this.loadingDesign = false
        },
        handleRest(full) {
            const transform = this.originalTransform.slice()
            if (full) {
                transform[0] = 1
                transform[3] = 1
                this.zoom = 1
            } else {
                this.zoom = transform[0]
            }

            window._gangSheetCanvasEditor.setViewportTransform(transform)
            this.onCanvasTransform(transform)
        },
        handleDeleteAll() {
            if (window.confirm('Are you really want to delete all images?')) {
                window._gangSheetCanvasEditor.clear()
                this.handleRest()
            }
        },
        handleMoveByKey(direction) {
            window._gangSheetCanvasEditor?.moveActiveObject(direction)
        },
        handleRefresh() {
            this.refreshing = true
            this.loadingDesign = true
            if (window._gangSheetCanvasEditor) {
                window._gangSheetCanvasEditor.getObjects().forEach(object => {
                    object.set('selectable', true)
                })
            }
            eventBus.$emit(eventBus.REFRESH_BUILDER)
        },
        handleVariantChange(variantId) {
            const index = this.variants.findIndex(variant => variant.id.toString() === variantId.toString());
            const variant = this.variants[index];
            if (variant) {
                this.variant = variant

                if (this.patternMode) {
                    this.workingDesignIndex = index
                    _gangSheetCanvasEditor.setVariant(variant)
                    this.images = this.currentDesign ? this.currentDesign.meta.images ?? [] : []
                } else {
                    if (!this.token) {
                        if (this.editMode && _gangSheetCanvasEditor.designId) {
                            this.oldDesignId = _gangSheetCanvasEditor.designId
                            _gangSheetCanvasEditor.setDesignId(null)
                        } else {
                            let url = new URL(window.location.href)
                            const params = url.searchParams
                            params.set('variant', variant.id)
                            history.pushState(null, '', `${url.pathname}?${params.toString()}`)
                        }
                    }

                    _gangSheetCanvasEditor.setVariant(variant)
                    this.variantUpdated = true
                }
            }
        },
        handleSizeChange(variantId) {
            this.objectSelected = false
            this.$gsb.updateCanvasData()
            this.handleVariantChange(variantId)
        },
        calculateDistance(touch1, touch2) {
            const dx = touch1.clientX - touch2.clientX
            const dy = touch1.clientY - touch2.clientY
            return Math.sqrt(dx * dx + dy * dy)
        },
        async saveDraftDesign(openNewDesign) {
            await this.$gsb.updateCanvasData()

            if (this.currentDesign.objects.length === 0) {
                return
            }

            if (this.savingDesign === false) {
                this.savingDesign = true
                _gangSheetCanvasEditor.clearHistory()
                NProgress.start()

                const thumbnail = await _gangSheetCanvasEditor.exportThumbnail()

                const data = {
                    shop_id: this.shop.id,
                    design_id: this.currentDesign.designId,
                    json: this.currentDesign,
                    product_id: this.product.id,
                    variant_id: this.variant.id,
                    customer_id: this.customer?.id || null,
                    thumbnail: thumbnail,
                    session_id: getSessionId()
                }
                const res = await saveDraftDesign(data)

                _gangSheetCanvasEditor.setDesignId(res.design_id)
                _gangSheetCanvasEditor.clearHistory()
                this.hasDesignChange = false
                this.$gsb.updateCanvasData()
                NProgress.done()

                window.Toast.success({
                    message: 'Design saved successfully.'
                })

                if (openNewDesign) {
                    await this.openNewDesign()
                }

                this.savingDesign = false
            }
        },
        async openDesign(designId) {
            this.loadingDesign = true
            eventBus.$emit(eventBus.CLOSE_MODAL_ALL)
            const design = await getDesign(designId)
            if (design) {
                const designJson = design.data

                if (design.status === 'draft') {
                    designJson.designId = design.id
                } else {
                    designJson.designId = null
                }

                let variant = this.visibleVariants.find(v => {
                    return v.id?.toString() === (design.variant_id || design.size_id)?.toString()
                })

                if (designJson.meta.variant.visible === 'Hidden' && !this.homeVariant.height < this.discountThreshold) {
                    variant = this.variant

                    for (const __v of this.visibleVariants) {
                        if (Math.abs(__v.height - designJson.meta.variant.height) < Math.abs(variant.height - designJson.meta.variant.height)) {
                            variant = __v
                        }
                    }
                }

                variant = cloneDeep(variant || this.variant)

                if (!designJson.meta) {
                    designJson.meta = {}
                }

                designJson.meta.variant = variant

                this.images = designJson.meta.image || []

                if (this.currentDesign.objects.length === 0) {
                    designJson.name = design.name
                    this.currentDesign = designJson
                } else {
                    this.workingDesigns.push(designJson)
                    this.workingDesignIndex = this.workingDesigns.length - 1
                }

                this.variant = variant
            }
        },
        async openNewDesign() {
            eventBus.$emit(eventBus.CLOSE_MODAL_ALL)
            this.images = []
            this.hasDesignChange = false
            this.currentDesign = this.$gsb.getEmptyDesign()
            this.variant = cloneDeep(this.variant)
        },
        getCanvasOptions() {
            return {
                ...this.builderSettings,
                patternMode: this.patternMode
            }
        }
    }
}
</script>
