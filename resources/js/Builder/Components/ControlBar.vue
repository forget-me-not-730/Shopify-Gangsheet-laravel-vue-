<script>
import {defineComponent} from 'vue'
import Spinner from "@/Components/Spinner.vue";
import builderMixin from "@/Builder/Mixins/builderMixin";
import SvgIcon from "@jamescoyle/vue-icon";
import {
    mdiContentDuplicate,
    mdiFileRotateRightOutline,
    mdiFileRotateLeftOutline,
    mdiArrowExpandVertical,
    mdiArrowExpandHorizontal,
    mdiFormatHorizontalAlignCenter,
    mdiFormatVerticalAlignCenter,
    mdiAlignHorizontalLeft,
    mdiAlignHorizontalRight,
    mdiAlignVerticalTop,
    mdiAlignVerticalBottom,
    mdiTrashCanOutline,
    mdiRefresh,
    mdiTransitDetour,
    mdiCrop,
    mdiDownloadOutline,
    mdiImageFilterCenterFocusStrongOutline,
} from '@mdi/js'

export default defineComponent({
    name: "ControlBar",
    components: {Spinner, SvgIcon},
    mixins: [builderMixin],
    props: {
        isGroupSelection: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            removingBackground: false,
            backgroundRemoved: false,
            activeObjectType: '',

            mdiContentDuplicate,
            mdiFileRotateLeftOutline,
            mdiFileRotateRightOutline,
            mdiArrowExpandVertical,
            mdiArrowExpandHorizontal,
            mdiFormatVerticalAlignCenter,
            mdiImageFilterCenterFocusStrongOutline,
            mdiAlignHorizontalLeft,
            mdiAlignHorizontalRight,
            mdiAlignVerticalTop,
            mdiAlignVerticalBottom,
            mdiTrashCanOutline,
            mdiRefresh,
            mdiTransitDetour,
            mdiCrop,
            mdiDownloadOutline,
            mdiFormatHorizontalAlignCenter
        }
    },
    computed: {
        showRemoveBackgroundButton() {
            if (this.shopSettings.disableBackgroundRemoveTool) {
                return false;
            }
            const selected = window._gangSheetCanvasEditor.getActiveObject()
            return selected?.type === 'image';
        }
    },
    mounted() {
        if (window._gangSheetCanvasEditor) {
            const updateStatus = () => {
                const selected = window._gangSheetCanvasEditor.getActiveObject()
                if (selected) {
                    this.removingBackground = selected.removingBackground
                    this.activeObjectType = selected.type
                }
            }
            window._gangSheetCanvasEditor.on({
                'selection:updated': updateStatus,
            })
            updateStatus()
        }
    },
    methods: {
        handleDuplicate() {
            if (window._gangSheetCanvasEditor) {
                window._gangSheetCanvasEditor.duplicate()
            }
        },

        handleRotate(direction) {
            const selected = window._gangSheetCanvasEditor.getActiveObject()

            if (selected) {
                let angle = selected.get('angle')
                if (direction === 'left') {
                    angle -= 90
                } else {
                    angle += 90
                }

                if (angle > 360) {
                    angle -= 360
                } else if (angle < -360) {
                    angle += 360
                }

                selected.set('angle', angle)

                window._gangSheetCanvasEditor.fire('object:rotating', {target: selected})
                window._gangSheetCanvasEditor.renderAll()
            }
        },
        handleExpand(direction) {
            if (window._gangSheetCanvasEditor) {
                switch (direction) {
                    case 'horizontal': {
                        window._gangSheetCanvasEditor.fitToViewPortWidth()
                        break;
                    }
                    case 'vertical': {
                        window._gangSheetCanvasEditor.fitToViewPortHeight()
                        break;
                    }
                }
                window._gangSheetCanvasEditor.fire('selection:updated')
            }
        },
        handleRestObject() {
            if (window._gangSheetCanvasEditor) {
                const selected = window._gangSheetCanvasEditor.getActiveObject();
                if (selected) {
                    selected.set('angle', 0)
                    selected.set('scaleX', 1)
                    selected.set('scaleY', 1)
                    window._gangSheetCanvasEditor.viewportCenterObject(selected);
                    window._gangSheetCanvasEditor.fire('object:updated')
                }
            }
        },
        handleAlignment(align) {
            if (window._gangSheetCanvasEditor) {
                window._gangSheetCanvasEditor.alignSelectedObject(align);
            }
        },
        async handleRemoveBackground() {
            const selected = window._gangSheetCanvasEditor.getActiveObject()
            if (selected && !this.removingBackground) {
                this.removingBackground = true
                const res = await selected.removeBackground()
                window._gangSheetCanvasEditor.renderAll()
                this.removingBackground = false
                if (res) {
                    // while working on background transparency, selected object can be changed.
                    const selected = window._gangSheetCanvasEditor.getActiveObject()
                    if (selected) {
                        this.backgroundRemoved = selected.backgroundRemoved
                    }
                } else {
                    window.Toast.error({
                        message: "We are not able to remove the background of this image."
                    })
                }
            }
        },
        handleDownloadImage() {
            const selected = window._gangSheetCanvasEditor.getActiveObject()
            if (selected) {
                const imageSrc = selected.getSrc()
                const a = document.createElement("a")
                a.href = imageSrc
                a.download = "image.png"
                a.target = '_blank'
                a.click()
            }
        },
        handleDelete() {
            if (window._gangSheetCanvasEditor) {
                window._gangSheetCanvasEditor.deleteActiveObjects()
            }
        },
    }
})
</script>

<template>
    <div class="border-l-2 h-[40px] px-1 flex items-center">
        <button
            :title="$t('Duplicate (Ctrl+D)')"
            class="w-6 h-6 hidden rounded items-center justify-center cursor-pointer gs-text-primary border hover:text-white hover:bg-info"
            @click="handleDuplicate">
            <svg-icon type="mdi" :path="mdiContentDuplicate" size="18"/>
        </button>

        <button
            :title="$t('Rotate 90 degrees CCW')"
            class="w-6 h-6 rounded shrink-0 flex items-center justify-center cursor-pointer gs-text-primary border hover:text-white hover:bg-info"
            @click="handleRotate('left')">
            <svg-icon type="mdi" :path="mdiFileRotateLeftOutline" size="16"/>
        </button>
        <button
            :title="$t('Rotate 90 degrees CW')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-px hover:text-white hover:bg-info"
            @click="handleRotate('right')">
            <svg-icon type="mdi" :path="mdiFileRotateRightOutline" size="16"/>
        </button>

        <button v-if="activeObjectType !== 'n-text'"
                :title="$t('Horizontal stretch')"
                class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-2 hover:text-white hover:bg-info"
                @click="handleExpand('horizontal')">
            <svg-icon type="mdi" :path="mdiArrowExpandHorizontal" size="16"/>
        </button>
        <button v-if="activeObjectType !== 'n-text' && !autoSize"
                :title="$t('Vertical stretch')"
                class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-px hover:text-white hover:bg-info"
                @click="handleExpand('vertical')">
            <svg-icon type="mdi" :path="mdiArrowExpandVertical" size="16"/>
        </button>

        <button
            :title="$t('Horizontal center')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-2 hover:text-white hover:bg-info"
            @click="handleAlignment('h-center')">
            <svg-icon type="mdi" :path="mdiFormatHorizontalAlignCenter" size="16"/>
        </button>
        <button
            v-if="!autoSize"
            :title="$t('Vertical center')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('v-center')">
            <svg-icon type="mdi" :path="mdiFormatVerticalAlignCenter" size="16"/>
        </button>
        <button
            v-if="!autoSize"
            :title="$t('Center in art board')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('center')">
            <svg-icon type="mdi" :path="mdiImageFilterCenterFocusStrongOutline" size="16"/>
        </button>

        <button
            :title="$t('Snap to left')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-2 hover:text-white hover:bg-info"
            @click="handleAlignment('left')">
            <svg-icon type="mdi" :path="mdiAlignHorizontalLeft" size="16"/>
        </button>
        <button
            :title="$t('Snap to right')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('right')">
            <svg-icon type="mdi" :path="mdiAlignHorizontalRight" size="16"/>
        </button>
        <button
            :title="$t('Snap to top')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('top')">
            <svg-icon type="mdi" :path="mdiAlignVerticalTop" size="16"/>
        </button>
        <button
            v-if="!autoSize"
            :title="$t('Snap to bottom')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('bottom')">
            <svg-icon type="mdi" :path="mdiAlignVerticalBottom" size="16"/>
        </button>

        <button
            v-if="!isGroupSelection && !backgroundRemoved && showRemoveBackgroundButton"
            :title="$t('Remove Background')"
            class="w-6 h-6 hidden rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-2 hover:text-white hover:bg-info disabled:text-gray-400 disabled:border-none disabled:border"
            :class="{'bg-info': removingBackground}"
            @click="handleRemoveBackground">
            <spinner v-if="removingBackground"/>
            <svg-icon v-else type="mdi" :path="mdiTransitDetour" size="16"/>
        </button>
        <button
            :title="$t('Download image')"
            class="w-6 h-6 hidden rounded flex shrink-0 items-center justify-center cursor-pointer gs-text-primary border ml-1"
            @click="handleDownloadImage">
            <svg-icon type="mdi" :path="mdiDownloadOutline" size="16"/>
        </button>

        <button
            v-if="!isGroupSelection"
            :title="$t('Reset selection')"
            class="w-6 h-6 hidden max-sm:hidden rounded flex shrink-0 items-center justify-center cursor-pointer text-danger border ml-4 hover:text-white hover:bg-danger"
            @click="handleRestObject">
            <svg-icon type="mdi" :path="mdiRefresh" size="16"/>
        </button>
        <div
            :title="$t('Delete (Ctrl+X)')"
            class="w-6 h-6 rounded flex shrink-0 items-center justify-center cursor-pointer text-danger border ml-px hover:text-white hover:bg-danger"
            @click="handleDelete">
            <svg-icon type="mdi" :path="mdiTrashCanOutline" size="16"/>
        </div>
    </div>
</template>
