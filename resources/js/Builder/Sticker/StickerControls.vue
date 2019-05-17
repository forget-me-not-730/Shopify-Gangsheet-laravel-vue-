<script>
import {defineComponent} from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'

import {
    mdiFormatHorizontalAlignCenter,
    mdiFormatVerticalAlignCenter,
    mdiAlignHorizontalLeft,
    mdiAlignHorizontalRight,
    mdiAlignVerticalTop,
    mdiAlignVerticalBottom,
    mdiTrashCanOutline,
    mdiImageFilterCenterFocusStrongOutline,
} from '@mdi/js'

export default defineComponent({
    name: 'StickerControls',
    components: {SvgIcon},
    data() {
        return {
            mdiFormatVerticalAlignCenter,
            mdiImageFilterCenterFocusStrongOutline,
            mdiAlignHorizontalLeft,
            mdiAlignHorizontalRight,
            mdiAlignVerticalTop,
            mdiAlignVerticalBottom,
            mdiTrashCanOutline,
            mdiFormatHorizontalAlignCenter
        }
    },
    methods: {
        handleAlignment(align) {
            if (_stickerCanvasEditor) {
                _stickerCanvasEditor.alignSelectedObject(align);
            }
        },
        handleDelete() {
            if (_stickerCanvasEditor) {
                _stickerCanvasEditor.deleteActiveObjects()
            }
        },
    }
})
</script>

<template>
    <div class="flex items-center justify-between">
        <button
            title="Horizontal center"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-info border hover:text-white hover:bg-info"
            @click="handleAlignment('h-center')">
            <svg-icon type="mdi" :path="mdiFormatHorizontalAlignCenter" size="16"/>
        </button>
        <button
            title="Vertical center"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-info border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('v-center')">
            <svg-icon type="mdi" :path="mdiFormatVerticalAlignCenter" size="16"/>
        </button>
        <button
            title="Center in art board"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-info border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('center')">
            <svg-icon type="mdi" :path="mdiImageFilterCenterFocusStrongOutline" size="16"/>
        </button>

        <button
            title="Snap to left"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-info border ml-2 hover:text-white hover:bg-info"
            @click="handleAlignment('left')">
            <svg-icon type="mdi" :path="mdiAlignHorizontalLeft" size="16"/>
        </button>
        <button
            title="Snap to right"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-info border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('right')">
            <svg-icon type="mdi" :path="mdiAlignHorizontalRight" size="16"/>
        </button>
        <button
            title="Snap to top"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-info border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('top')">
            <svg-icon type="mdi" :path="mdiAlignVerticalTop" size="16"/>
        </button>
        <button
            title="Snap to bottom"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-info border ml-px hover:text-white hover:bg-info"
            @click="handleAlignment('bottom')">
            <svg-icon type="mdi" :path="mdiAlignVerticalBottom" size="16"/>
        </button>

        <button
            title="Delete (Ctrl+X)"
            class="w-7 h-7 rounded flex shrink-0 items-center justify-center cursor-pointer text-danger border ml-px hover:text-white hover:bg-danger"
            @click="handleDelete">
            <svg-icon type="mdi" :path="mdiTrashCanOutline" size="16"/>
        </button>
    </div>
</template>
