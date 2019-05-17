<script>
import {defineComponent} from 'vue'
import builderMixin from '@/Builder/Mixins/builderMixin'
import eventBus from '@/Builder/Utils/eventBus'

export default defineComponent({
    name: 'SelectedObjectInfo',
    mixins: [builderMixin],
    data() {
        return {
            resolutionLevels: undefined,
            objectSelected: false,

            dimensions: {
                left: 0,
                top: 0,
                width: 0,
                height: 0
            },
            resolutionX: undefined,
            resolutionY: undefined,

            showResolutionLines: true,
            showMarginOutlines: true,
        }
    },
    mounted() {
        eventBus.$on(eventBus.CANVAS_INITIALIZED, (canvas) => {
            this.resolutionLevels = canvas.resolutionLevels
            this.showResolutionLines = canvas.showResolutionLines
            this.showMarginOutlines = canvas.showMarginOutlines

            const updateDimensions = () => {
                const selected = canvas?.getActiveObject()
                if (selected) {
                    this.dimensions = selected.getDimensions()
                    const resolution = selected.getResolution()

                    if (selected.type === 'image') {
                        this.resolutionX = resolution.resolutionX
                        this.resolutionY = resolution.resolutionY
                    } else {
                        this.resolutionX = undefined
                        this.resolutionY = undefined
                    }
                }
            }

            const handleSelectionCreate = () => {
                updateDimensions()
                this.objectSelected = true
            }

            const handleSelectionCleared = () => {
                this.objectSelected = false
            }

            const handleScaling = () => {
                updateDimensions()
            }

            const handleMoving = () => {
                updateDimensions()
            }

            const handleObjectUpdated = () => {
                updateDimensions()
            }

            canvas.on({
                'selection:created': handleSelectionCreate,
                'selection:cleared': handleSelectionCleared,
                'object:scaling': handleScaling,
                'object:moving': handleMoving,
                'object:updated': handleObjectUpdated,
                'after:render': updateDimensions,
            })
        })
        eventBus.$on(eventBus.UPDATE_SHOW_RESOLUTION_LINES, (newValue) => {
            this.showResolutionLines = newValue;
        })
        eventBus.$on(eventBus.UPDATE_SHOW_OVERLAPPING_LINES, (newValue) => {
            this.showMarginOutlines = newValue;
        })
    },
    unmounted() {
        eventBus.$off(eventBus.UPDATE_SHOW_RESOLUTION_LINES)
        eventBus.$off(eventBus.UPDATE_SHOW_OVERLAPPING_LINES)
    },
    methods: {
        handleShowResolutionLinesChange(e) {
            this.showResolutionLines = e.target.checked
            window._gangSheetCanvasEditor.showResolutionLines = e.target.checked
            window._gangSheetCanvasEditor.renderAll()
            eventBus.$emit(eventBus.UPDATE_SHOW_RESOLUTION_LINES, e.target.checked)
        },
        handleShowMarginOutlineChange(e) {
            this.showMarginOutlines = e.target.checked
            window._gangSheetCanvasEditor.showMarginOutlines = e.target.checked
            window._gangSheetCanvasEditor.renderAll()
            eventBus.$emit(eventBus.UPDATE_SHOW_OVERLAPPING_LINES, e.target.checked)
        }
    }
})
</script>

<template>
    <div class="absolute w-max top-[20px] left-[20px] z-30 flex flex-col min-w-[150px] font-thin text-xs pointer-events-none">

        <template v-if="!patternMode">
            <div v-if="objectSelected" class="sm:hidden font-thin flex max-w-max items-center">
                <span>{{ dimensions.width.toFixed(2) }}</span>{{ artBoardUnit }} x {{ dimensions.height.toFixed(2) }}{{ artBoardUnit }}, &nbsp;
                <span class="max-w-max">{{ Math.min(resolutionX, resolutionY) }} dpi</span>
            </div>

            <div class="max-sm:hidden text-xs space-y-0.5 py-1 px-2 w-full bg-builder rounded border">
                <label class="flex items-center pointer-events-auto">
                    <input type="checkbox" @change="handleShowMarginOutlineChange" :checked="showMarginOutlines" class="mr-2">
                    <span>{{ $t('Show Overlapping Lines') }}</span>
                </label>
            </div>

            <div v-if="resolutionLevels" class="max-sm:hidden text-xs space-y-0.5 py-1 px-2 w-full bg-builder rounded border mt-2">
                <label class="flex items-center pointer-events-auto">
                    <input type="checkbox" @change="handleShowResolutionLinesChange" :checked="showResolutionLines" class="mr-2">
                    <span>{{ $t('Show Resolution Lines') }}</span>
                </label>
                <div v-if="showResolutionLines" class="pt-2 pointer-events-none">
                    <div class="flex items-center">
                        <span class="w-3 h-3 border border-gray-100 bg-green-500 mr-1"></span>
                        <span>{{ $t('Optimal Resolution') }} >= {{ resolutionLevels.optimal }} dpi</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 border border-gray-100 bg-yellow-400 mr-1"></span>
                        <span>{{ $t('Good Resolution') }} >= {{ resolutionLevels.good }} dpi</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 border border-gray-100 bg-red-500 mr-1"></span>
                        <span>{{ $t('Bad Resolution') }} >= {{ shop.settings?.hideTerribleResolution ? 1 : resolutionLevels.bad }} dpi</span>
                    </div>
                    <div v-if="!shop.settings?.hideTerribleResolution" class="flex items-center">
                        <span class="w-3 h-3 border border-gray-100 bg-black mr-1"></span>
                        <span>{{ $t('Terrible Resolution') }} &lt; {{ resolutionLevels.bad }} dpi</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 border border-gray-100 bg-blue-600 mr-1"></span>
                        <span>{{ $t('Overlapping images') }}</span>
                    </div>
                </div>
            </div>
        </template>
        <div v-if="objectSelected" class="py-1 max-sm:hidden px-2 w-full bg-builder rounded border mt-1 pointer-events-none">
            <div class="flex items-center">
                <span class="w-20 ">{{ $t('Left') }}</span>
                <span class="max-w-max"> <span>{{ dimensions.left.toFixed(2) }}</span> {{ artBoardUnit }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-20 ">{{ $t('Top') }}</span>
                <span class="max-w-max"> <span>{{ dimensions.top.toFixed(2) }}</span> {{ artBoardUnit }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-20 ">{{ $t('Width') }}</span>
                <span class="max-w-max"> <span>{{ dimensions.width.toFixed(2) }}</span> {{ artBoardUnit }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-20 ">{{ $t('Height') }}</span>
                <span class="max-w-max"> {{ dimensions.height.toFixed(2) }} {{ artBoardUnit }}</span>
            </div>
            <div v-if="resolutionX && resolutionY" class="flex items-center">
                <span class="w-20 ">{{ $t('Resolution') }}</span>
                <span class="max-w-max">{{ Math.min(resolutionX, resolutionY) }} dpi</span>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
