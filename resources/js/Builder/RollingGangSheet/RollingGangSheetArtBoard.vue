<script>
import {defineComponent} from 'vue'
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import rerenderMixin from "@/Builder/Mixins/rerenderMixin";
import snapMixin from "@/Builder/Mixins/snapMixin";
import Spinner from "@/Components/Spinner.vue";
import BuilderAlerts from "@/Builder/Components/BuilderAlerts.vue";
import ControlBar from "@/Builder/Components/ControlBar.vue";
import SettingMargin from "@/Builder/ArtboardSettings/SettingMargin.vue";
import UndoIcon from "@/Builder/Icons/UndoIcon.vue";
import RedoIcon from "@/Builder/Icons/RedoIcon.vue";
import HandBackLeftOutlineIcon from "@/Builder/Icons/HandBackLeftOutlineIcon.vue";
import GridIcon from "@/Builder/Icons/GridIcon.vue";
import SelectedObjectInfo from "@/Builder/Components/SelectedObjectInfo.vue";
import GsCanvas from "@/Builder/Components/GsCanvas.vue";
import ChevronLeftIcon from "@/Builder/Icons/ChevronLeftIcon.vue";
import ChevronRightIcon from "@/Builder/Icons/ChevronRightIcon.vue";
import ChevronUpIcon from "@/Builder/Icons/ChevronUpIcon.vue";
import ChevronDownIcon from "@/Builder/Icons/ChevronDownIcon.vue";
import FitToScreenOutlineIcon from "@/Builder/Icons/FitToScreenOutlineIcon.vue";
import MagnifyMinusOutlineIcon from "@/Builder/Icons/MagnifyMinusOutlineIcon.vue";
import MagnifyPlusOutlineIcon from "@/Builder/Icons/MagnifyPlusOutlineIcon.vue";
import eventBus from "@/Builder/Utils/eventBus";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import LockOutlineIcon from "@/Builder/Icons/LockOutlineIcon.vue";
import LockOpenOutlineIcon from "@/Builder/Icons/LockOpenOutlineIcon.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import ReloadIcon from "@/Builder/Icons/ReloadIcon.vue";
import artBoardMixin from "@/Builder/Mixins/artBoardMixin";
import RollingCanvasDesigns from "@/Builder/Components/RollingCanvasDesigns.vue";
import {convertDimension} from '@/Builder/Utils/helpers'
import Ruler from "@/Builder/Components/Ruler.vue";
import AutoNestRollingGangSheetButton from "@/Builder/GangSheet/AutoNestRollingGangSheetButton.vue";
import AutoNestForRolling from "@/Builder/Components/AutoNestForRolling.vue";
import {cloneDeep} from "lodash";
import {v4 as uuidv4} from 'uuid'
import {EXTRA_PROPERTIES} from "@/Builder/Utils/constants";
import {getDesign} from "@/Builder/Apis/builderApi";
import ContextMenu from "@/Builder/Components/ContextMenu.vue";

window._gangSheetCanvasEditor = null

export default defineComponent({
    name: "RollingGangSheetArtBoard",
    components: {
        ContextMenu,
        AutoNestForRolling,
        AutoNestRollingGangSheetButton,
        Ruler,
        RollingCanvasDesigns,
        ReloadIcon,
        SvgIcon,
        LockOpenOutlineIcon,
        LockOutlineIcon,
        CloseIcon,
        MagnifyPlusOutlineIcon,
        MagnifyMinusOutlineIcon,
        FitToScreenOutlineIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        ChevronRightIcon,
        ChevronLeftIcon,
        GsCanvas,
        SelectedObjectInfo,
        GridIcon,
        HandBackLeftOutlineIcon,
        RedoIcon,
        UndoIcon,
        SettingMargin,
        ControlBar,
        BuilderAlerts,
        Spinner
    },
    mixins: [gangSheetMixin, rerenderMixin, snapMixin, artBoardMixin],
    data() {
        return {
            loading: true,
            lockHeight: false,

            designWidth: 0,
            designHeight: 0,
            screenWidth: 0,
            screenHeight: 0,
            editor: null,

            objectSelected: false,
            isGroupSelection: false,
            editBoundBorderWidth: 1,
            objectOffBoarding: false,

            gangSheetSize: null,
            startGangSheetHeight: 40,
            minGangSheetHeight: 20,
            maxGangSheetHeight: 360,
            initialGangSheetHeight: 0
        }
    },
    computed: {
        translateYOffset() {
            return this.viewPortOffsetY * (this.zoom / this.originalTransform[0]) / 2
        },
        editorBoundStyle() {
            return {
                width: this.designWidth * this.zoom + this.editBoundBorderWidth * 2 + 'px',
                height: this.designHeight * this.zoom + this.editBoundBorderWidth * 2 + 'px',
                transform: `translate(${this.translateX}px, ${this.translateY + this.translateYOffset}px)`,
                '--border-width': this.editBoundBorderWidth + 'px',
                '--border-color': this.objectOffBoarding ? '#ff0000' : '#2590c0'
            }
        },
        snapLineContainerStyle() {
            return {
                width: Math.round(this.designWidth * this.zoom) + this.editBoundBorderWidth * 2 + 'px',
                height: Math.round(this.designHeight * this.zoom) + this.editBoundBorderWidth * 2 + 'px',
                transform: `translate(${this.translateX}px, ${this.translateY + this.translateYOffset}px)`,
            }
        }
    },
    watch: {
        lockHeight() {
            if (window._gangSheetCanvasEditor) {
                this.autoSize = !this.lockHeight
                window._gangSheetCanvasEditor.autoSize = !this.lockHeight
            }
        },
        workingDesignIndex() {
            this.initGangSheetSize()
        },
        gangSheetSize() {
            this.loading = true
            this.forceRerender()
        }
    },
    beforeMount() {
        this.initGangSheetSize()
        if (this.editMode) {
            this.lockHeight = true
        }
    },
    mounted() {
        this.variant = this.variants[0]
        eventBus.$on(eventBus.CANVAS_RESIZE, this.initGangSheetSize)
    },
    beforeUnmount() {
        eventBus.$off(eventBus.CANVAS_RESIZE, this.initGangSheetSize)

        this.$gsb.updateCanvasData()
        window._gangSheetCanvasEditor = null
    },
    methods: {
        initGangSheetSize() {
            if (window._gangSheetCanvasEditor) {
                window._gangSheetCanvasEditor.isReady = false
            }

            this.minGangSheetHeight = this.productSettings.minHeight
            this.maxGangSheetHeight = this.productSettings.maxHeight
            this.startGangSheetHeight = this.productSettings.startHeight

            if (this.currentDesign) {
                this.gangSheetSize = this.currentDesign.meta.variant

                const startHeight = this.workingDesignIndex > 0 ? this.minGangSheetHeight : this.startGangSheetHeight
                if (this.gangSheetSize.height < startHeight) {
                    this.gangSheetSize.height = startHeight
                }
            } else {
                this.gangSheetSize = {
                    id: this.variant.id,
                    width: this.productSettings.printerWidth,
                    height: this.startGangSheetHeight,
                    unit: this.artBoardUnit
                }
            }

            if (!this.gangSheetSize.id) {
                this.gangSheetSize.id = this.variant.id
            }
        },
        async handleRefreshBuilder(designJson) {
            if (designJson) {
                this.currentDesign = designJson
                this.images = designJson.meta.images ?? []
                this.initGangSheetSize()
            } else {
                await this.$gsb.updateCanvasData()
                await this.forceRerender()
            }
        },
        handleCanvasInitialized(canvas) {
            window._gangSheetCanvasEditor = canvas

            canvas.autoSize = !this.editMode
            this.autoSize = true

            this.hasUndoHistory = false
            this.hasRedoHistory = false
            this.hasDesignChange = false

            this.screenWidth = canvas.width
            this.screenHeight = canvas.height

            this.designWidth = canvas.designWidth
            this.designHeight = canvas.designHeight

            this.zoom = canvas.getZoom()

            if (this.variantUpdated) {
                canvas.fitDesignToTopLeft()
                this.variantUpdated = false
            }

            this.objectOffBoarding = canvas.artBoardError;

            this.$nextTick(() => {
                canvas.checkDesignOverlapping()
                canvas.checkMarginOverlapping()
                this.$gsb.updateCanvasData()
                eventBus.$emit(eventBus.CANVAS_INITIALIZED, canvas)
                canvas.isReady = true
            })

            this.originalTransform = canvas.viewportTransform.slice()
            this.onCanvasTransform(this.originalTransform)

            this.initialGangSheetHeight = this.gangSheetSize.height
            this.viewPortOffsetY = canvas.viewPortOffsetY * this.originalTransform[0];

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

            const resizeGangSheetHeight = (e, event) => {
                if (canvas.autoSize && canvas.isReady) {
                    const viewport = canvas.getViewPort()

                    const currentHeightPx = canvas.getCurrentHeight(e.target)
                    const currentHeight = convertDimension(currentHeightPx, 'px', this.artBoardUnit)
                    const startHeight = this.workingDesignIndex > 0 ? this.minGangSheetHeight : this.startGangSheetHeight
                    const startHeightPx = convertDimension(startHeight, this.artBoardUnit, 'px')
                    let newHeight = null

                    if (event === 'object:before-add') {
                        const rect = e.target.getBoundaryRect()
                        const artBoardMargin = canvas.getArtboardMarginPx()
                        const nextHeight = convertDimension(rect.bottom - viewport.top + artBoardMargin, 'px', this.artBoardUnit)
                        if (nextHeight > this.maxGangSheetHeight) {
                            this.$gsb.updateCanvasData()
                            const artBoardMarginPx = canvas.getArtboardMarginPx()
                            const objectJson = e.target.toJSON(EXTRA_PROPERTIES)
                            objectJson.left = viewport.left + rect.width / 2 + artBoardMarginPx
                            objectJson.top = viewport.top + rect.height / 2 + artBoardMarginPx

                            const newDesign = cloneDeep(this.currentDesign)
                            newDesign.objects = [objectJson]
                            newDesign.meta.variant.height = this.minGangSheetHeight
                            newDesign.id = uuidv4()
                            newDesign.designId = null

                            this.workingDesigns.push(newDesign)
                            this.workingDesignIndex = this.workingDesigns.length - 1

                            this.forceRerender()
                        }
                    } else {
                        if (event === 'object:removed') {
                            if (canvas.isEmpty() && this.workingDesignIndex > 0) {
                                this.workingDesigns.splice(this.workingDesignIndex, 1)
                                this.workingDesignIndex = this.workingDesigns.length - 1
                                this.forceRerender()
                            }

                            if (viewport.height > startHeightPx) {
                                const artBoardMargin = canvas.getArtboardMargin()
                                newHeight = Math.max(currentHeight + artBoardMargin, startHeight)
                            }
                        } else {
                            if (e.target) {

                                if (event === 'object:added' && e.target.type.includes('text')) {
                                    e.target.reInitDimensions()
                                }

                                const rect = e.target.getBoundaryRect()
                                const artBoardMargin = canvas.getArtboardMargin()
                                const artBoardMarginPx = canvas.getArtboardMarginPx()

                                const deltaY = rect.bottom - viewport.bottom + artBoardMarginPx
                                newHeight = Number(this.gangSheetSize.height) + convertDimension(deltaY, 'px', this.artBoardUnit)

                                newHeight = Math.max(newHeight, currentHeight + artBoardMargin)
                            }
                        }

                        if (newHeight && newHeight !== this.gangSheetSize.height && this.maxGangSheetHeight >= newHeight) {
                            newHeight = Math.max(newHeight, startHeight)
                            this.gangSheetSize.height = Number(newHeight.toFixed(2))
                            canvas.updateDesignHeight(this.gangSheetSize.height)

                            if (event === 'object:added' && currentHeight > startHeight) {
                                const transformY = convertDimension(newHeight - currentHeight, this.artBoardUnit, 'px') * this.zoom
                                this.moveCanvasTransform(0, -transformY)
                                this.scrollY -= transformY / this.scrollYRate
                            }
                        }
                    }
                }
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
                },
                'design:height:change': () => {
                    this.designHeight = canvas.designHeight
                    this.viewPortOffsetY = canvas.viewPortOffsetY * this.originalTransform[0];

                    this.variant = this.$gsb.getTieredVariant()
                    canvas.variant.id = this.variant.id

                    this.$nextTick(() => {
                        this.$gsb.updateCanvasData()
                    })
                },
                'object:moving': (e) => {
                    resizeGangSheetHeight(e, 'object:moving')
                },
                'object:added': (e) => {
                    resizeGangSheetHeight(e, 'object:added')
                },
                'object:removed': (e) => {
                    resizeGangSheetHeight(e, 'object:removed')
                },
                'object:modified': (e) => {
                    resizeGangSheetHeight(e, 'object:modified')
                },
                'object:moved': (e) => {
                    resizeGangSheetHeight(e, 'object:moved')
                },
                'object:rotating': (e) => {
                    resizeGangSheetHeight(e, 'object:rotating')
                },
                'object:updated': (e) => {
                    resizeGangSheetHeight(e, 'object:rotating')
                },
                'object:before-add': (e) => {
                    resizeGangSheetHeight(e, 'object:before-add')
                }
            })

            this.loadingDesign = false
            this.loading = false
        },
        handleRest(full) {
            if (full) {
                const transform = this.originalTransform.slice()
                transform[0] = 1
                transform[3] = 1
                this.zoom = 1
                _gangSheetCanvasEditor.setViewportTransform(transform)
                this.onCanvasTransform(transform)
            } else {
                this.$gsb.updateCanvasData()
                this.forceRerender()
            }
        },
        handleGangSheetHeightChange(e) {
            this.gangSheetSize.height = Number(e.target.value)

            if (this.gangSheetSize.height < this.minGangSheetHeight) {
                this.gangSheetSize.height = this.minGangSheetHeight
            }

            if (this.gangSheetSize.height > this.maxGangSheetHeight) {
                this.gangSheetSize.height = this.maxGangSheetHeight
            }

            this.initialGangSheetHeight = this.gangSheetSize.height

            this.$gsb.updateCanvasData()
            this.forceRerender()
        },
        async handleRefresh() {
            this.loadingDesign = true
            await this.$gsb.updateCanvasData()
            await this.forceRerender()
        },
        handleDelete() {
            if (_gangSheetCanvasEditor) {
                _gangSheetCanvasEditor.deleteActiveObjects()
            }
        },
        handleMoveByKey(direction) {
            _gangSheetCanvasEditor?.moveActiveObject(direction)
        },
        handleSizeChange(variantId) {
            this.objectSelected = false
            this.$gsb.updateCanvasData()
            this.handleVariantChange(variantId)
        },
        toggleLockHeight() {
            if (!this.editMode) {
                this.lockHeight = !this.lockHeight
            }
        },
        async handleOpenDesign(designId) {
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

                this.images = designJson.meta.image || []

                this.workingDesigns = [designJson]
                this.workingDesignIndex = 0
                this.initGangSheetSize()
            }
        },
    }
})
</script>

<template>
    <div class="flex flex-col z-10 bg-builder flex-1 w-full max-md:h-1 md:w-1 relative"
         :class="{'pointer-events-none': loading || savingDesign || loadingDesign}">

        <div v-if="loading || loadingDesign" class="absolute inset-0 bg-builder z-50 flex items-center justify-center opacity-75">
            <spinner class="!w-8 !h-8"/>
        </div>

        <div v-if="autoNestMode" class="absolute inset-0 bg-builder z-50 flex items-center justify-center p-2 md:p-5">
            <auto-nest-for-rolling ref="autoNest" @close="autoNestMode = false"/>
        </div>

        <template v-else>
            <builder-alerts v-if="!loadingDesign"/>

            <div class="absolute max-xs:left-6 left-8 max-xs:bottom-6 bottom-8 z-30 flex space-x-2">
                <div class="cursor-pointer btn-builder px-0 py-0 rounded-full shadow-lg w-7 h-7" @click="handleRefresh">
                    <reload-icon size="18"/>
                </div>

                <button v-if="permissions.autoNest" @click="autoNestMode = true" :disabled="loading" class="btn-builder btn-sm text-xs">
                    {{ $t('Auto Build') }}
                </button>
            </div>

            <div class="w-full relative h-[40px] z-40">
                <div class="absolute max-md:left-0 max-md:w-full left-[-320px] xl:left-0 md:w-[calc(100%+320px)] h-full xl:w-full flex items-center bg-builder border-b">
                    <div class="p-1.5 w-42 shrink-0">
                        <div class="w-full flex items-center choose-variant text-sm">
                            <div class="inp-builder w-20 py-0 h-7 flex justify-center opacity-60">
                                <lock-outline-icon/>
                                <div class="flex-1 pl-2">
                                    {{ gangSheetSize.width }}
                                </div>
                            </div>
                            <close-icon size="16" class="text-gray-700 mx-1"/>
                            <div class="inp-builder w-20 py-0 h-7 flex justify-center">
                                <div class="cursor-pointer" @click="toggleLockHeight" :class="{'opacity-60': editMode}">
                                    <lock-outline-icon v-if="lockHeight"/>
                                    <lock-open-outline-icon v-else/>
                                </div>
                                <div class="flex-1 pl-2" :class="{'opacity-60': lockHeight}">
                                    <input :value="gangSheetSize.height" class="inp-no-style p-0 w-full" :disabled="true" @change="handleGangSheetHeightChange"/>
                                </div>
                            </div>
                            <div class="ml-2">
                                {{ artBoardUnit }}
                            </div>
                        </div>
                    </div>

                    <template v-if="!loading">
                        <div class="flex flex-1 w-1">
                            <div class="min-h-[40px] flex items-center">
                                <div class="border-l-2 px-1 h-full flex items-center ml-2">
                                    <button
                                        title="Undo (Ctrl+Z)"
                                        :disabled="!hasUndoHistory"
                                        class="w-6 h-6 border rounded flex items-center justify-center cursor-pointer disabled:border-none disabled:text-gray-400"
                                        @click="handleUndo"
                                        :class="{'gs-bg-primary': hasUndoHistory}"
                                    >
                                        <undo-icon size="18"/>
                                    </button>

                                    <button
                                        title="Redo (Ctrl+Y)"
                                        :disabled="!hasRedoHistory"
                                        class="w-6 h-6 border rounded flex items-center justify-center cursor-pointer ml-2 active:bg-white active:text-info active:border active:border-info disabled:border-none disabled:text-gray-400"
                                        @click="handleRedo"
                                        :class="{'gs-bg-primary': hasRedoHistory}"
                                    >
                                        <redo-icon size="18" :class="{'text-white': hasRedoHistory}"/>
                                    </button>
                                </div>

                                <div class="border-l-2 px-1 h-full flex items-center">
                                    <button
                                        title="Move mode"
                                        class="w-6 h-6 border rounded flex items-center justify-center cursor-pointer"
                                        @click="moveMode = !moveMode"
                                        :class="{'gs-bg-primary': moveMode}"
                                    >
                                        <hand-back-left-outline-icon size="16" :class="{'text-white': moveMode}"/>
                                    </button>
                                </div>

                                <div class="max-lg:hidden border-l-2 px-1 h-full flex items-center">
                                    <setting-margin/>
                                </div>
                            </div>
                            <div v-if="objectSelected" class="max-md:hidden overflow-x-auto">
                                <control-bar v-if="objectSelected" :is-group-selection="isGroupSelection"/>
                            </div>
                        </div>
                        <div class="ml-auto h-[40px] flex items-center justify-center">
                            <auto-nest-rolling-gang-sheet-button/>
                        </div>
                    </template>
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

                <ruler :translate-y="translateY + translateYOffset" :translate-x="translateX" :zoom="zoom" :width="gangSheetSize.width"
                       :height="gangSheetSize.height" :unit="artBoardUnit"/>

                <div class="absolute left-0 bottom-0 h-4 right-0 z-20 bg-builder border-t flex justify-center touch-none">
                    <div
                        class="btn-builder justify-between rounded-full px-0 w-48 cursor-pointer hover:bg-primary text-white user-select-none"
                        :style="{transform: `translate(${scrollX}px, 0px)`}"
                        @mousedown="scrollHEnabled = true"
                        @touchstart.prevent="handleTouchStart($event, 'scrollHEnabled')"
                    >
                        <chevron-left-icon size="18"/>
                        <chevron-right-icon size="18"/>
                    </div>
                </div>
                <div
                    class="absolute right-0 h-full w-4 bottom-0 z-40 cursor-pointer bg-builder border-l flex flex-col justify-center touch-none">
                    <div
                        class="btn-builder px-0 py-0 flex-col justify-between rounded-full h-48 hover:bg-primary text-white user-select-none"
                        :style="{transform: `translate(0px, ${scrollY - translateYOffset}px)`}"
                        @mousedown="scrollVEnabled = true"
                        @touchstart.prevent="handleTouchStart($event, 'scrollVEnabled')"
                    >
                        <chevron-up-icon size="18"/>
                        <chevron-down-icon size="18"/>
                    </div>
                </div>

                <selected-object-info/>

                <context-menu :object-selected="objectSelected"/>

                <slot v-if="$slots['right-panel']" name="right-panel"/>
                <rolling-canvas-designs v-else-if="!editMode"/>

                <div class="absolute w-max top-[24px] flex items-center right-[20px] z-30 bg-builder rounded max-sm:text-xs">
                    <div title="Fit to screen" class="btn-builder max-sm:rounded-sm rounded mr-2 py-0.5 px-1 cursor-pointer"
                         @click="handleRest(false)">
                        <fit-to-screen-outline-icon :size="16"/>
                    </div>
                    <div class="max-md:hidden btn-builder rounded mr-4 py-px px-2 cursor-pointer text-xs" @click="handleRest(true)">
                        <span title="reset editor view">100%</span>
                    </div>
                    <div class="max-sm:w-5 max-sm:h-5 w-6 h-[22px] bg-builder border rounded flex items-center justify-center cursor-pointer"
                         @click="handleZoomOut">
                        <magnify-minus-outline-icon size="16"/>
                    </div>
                    <span class="px-1 w-14 text-center text-xs">{{ (zoom * 100).toFixed(2) }}%</span>
                    <div class="max-sm:w-5 max-sm:h-5 w-6 h-[22px] bg-builder border rounded flex items-center justify-center cursor-pointer"
                         @click="handleZoomIn">
                        <magnify-plus-outline-icon size="16"/>
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
                        <gs-canvas ref="canvas" :variant="gangSheetSize" :json-data="currentDesign" :options="builderSettings" @initialized="handleCanvasInitialized"/>
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
        </template>
    </div>
</template>
