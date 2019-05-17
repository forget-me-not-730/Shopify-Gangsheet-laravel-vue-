<script>
import {defineComponent} from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiMenu, mdiClose} from '@mdi/js'
import GalleryMenu from "@/Components/Merchant/GalleryMenu.vue";

export default defineComponent({
    name: 'Splitter',
    components: {GalleryMenu, SvgIcon},
    data() {
        return {
            leftPanelWidth: 320,
            clientX: 0,
            resizeMode: false,
            openMenu: false,

            mdiMenu,
            mdiClose
        }
    },
    mounted() {
        document.addEventListener('mousemove', this.handleMouseMove)
        document.addEventListener('mouseup', this.handleMouseUp)
    },
    beforeUnmount() {
        document.removeEventListener('mousemove', this.handleMouseMove)
    },
    watch: {
        'pageData.activePath': {
            handler() {
                this.openMenu = false
            },
            immediate: true
        }
    },
    methods: {
        handleMouseDown(e) {
            e.preventDefault()
            this.clientX = e.clientX
            this.resizeMode = true
        },
        handleMouseMove(e) {
            e.preventDefault()

            if (this.resizeMode) {
                this.leftPanelWidth += e.clientX - this.clientX
                this.clientX = e.clientX

                if (this.leftPanelWidth < 200) {
                    this.leftPanelWidth = 200
                } else if (this.leftPanelWidth > 600) {
                    this.leftPanelWidth = 600
                }
            }
        },
        handleMouseUp(e) {
            e.preventDefault()

            this.resizeMode = false
        }
    }
})
</script>

<template>
    <div class="flex flex-col w-full h-full">
        <div class="absolute inset-0 bg-gray-600 bg-opacity-75 z-10 md:hidden" :class="{'hidden' : !openMenu}" v-on:click.prevent="openMenu = false"></div>

        <div class="flex px-4 py-2 md:hidden items-center space-x-4">
            <div @click="openMenu = true">
                <svg-icon type="mdi" :path="mdiMenu" size="24" class="cursor-pointer"/>
            </div>
            <slot name="header"></slot>
        </div>
        <div class="flex flex-1 w-full h-full overflow-hidden" :class="{'cursor-col-resize': resizeMode}">
            <div class="bg-gray-50 max-md:fixed max-md:top-0 z-20 h-full max-md:transition-all"
                 :style="{width: leftPanelWidth + 'px', left: openMenu ? 0 : `-${leftPanelWidth}px`}">
                <div class="flex justify-end">
                    <div class="p-2 md:hidden" @click="openMenu = false">
                        <svg-icon type="mdi" :path="mdiClose" size="20" class="cursor-pointer"/>
                    </div>
                </div>
                <slot name="left"></slot>
            </div>
            <div class="flex justify-between items-center">
                <div @mousedown="handleMouseDown" class="w-2 h-full cursor-col-resize border-l max-md:hidden"></div>
            </div>
            <div class="flex-1 w-px relative">
                <div class="w-full h-full">
                    <slot name="right"></slot>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
