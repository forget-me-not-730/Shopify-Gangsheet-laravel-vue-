<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import stickerMixin from "@/Builder/Mixins/stickerMixin";
import FontSettingPanel from './FontSettingPanel.vue';
import ImageSettingPanel from './ImageSettingPanel.vue';
import {mdiFormatText} from '@mdi/js'
import UploadImages from "./StickerUploadImages.vue";
import eventBus from "@/Builder/Utils/eventBus";
import {TOOLS} from "@/Builder/Utils/constants";

export default defineComponent({
    name: "StickerSideBar",
    components: {ImageSettingPanel, FontSettingPanel, UploadImages, SvgIcon},
    mixins: [stickerMixin],
    data() {
        return {
            tools: TOOLS,
            hasBackground: false,
            mdiFormatText
        }
    },
    mounted: function () {
        this.tool = this.tools.image
        this.checkCanvasBackground()
        eventBus.$on(eventBus.STICKER_EDIT, () => {
            this.hasBackground = true
            this.handleChangeTab(this.tools.edit)
        })
        eventBus.$on(eventBus.STICKER_CLEARED, () => {
            this.hasBackground = false
            this.handleChangeTab(this.tools.image)
        })
    },
    unmounted: function () {
        eventBus.$off(eventBus.STICKER_EDIT)
        eventBus.$off(eventBus.STICKER_CLEARED)
    },
    methods: {
        handleAddText() {
            if (_stickerCanvasEditor) {
                _stickerCanvasEditor.addText('Sticker', this.defaultFont)
                this.$emit('close')
            }
        },
        handleChangeTab(tab) {
            this.tool = tab
        },
        checkCanvasBackground() {
            this.hasBackground = Boolean(this.currentSticker);
        }
    }
})
</script>

<template>
    <div class="p-4 w-[380px] shrink-0 hidden sm:flex flex-col bg-white rounded border border-gray-200">
        <div class="flex space-x-2">
            <div class="flex-1 py-2 flex-center-col cursor-pointer rounded-t border border-transparent hover:gs-border-primary"
                 :class="{'gs-bg-primary text-white': tool === this.tools.image}"
                 @click="handleChangeTab(this.tools.image)">
                Images
            </div>
            <div class="flex-1 py-2 flex-center-col rounded-t border border-transparent hover:gs-border-primary"
                 :class="[tool === this.tools.text ? 'gs-bg-primary text-white': '', hasBackground ? 'cursor-pointer' : 'cursor-not-allowed opacity-50']"
                 @click="hasBackground ? handleChangeTab(this.tools.text) : null">
                Text
            </div>
            <div class="flex-1 py-2 flex-center-col rounded-t border border-transparent hover:gs-border-primary"
                 :class="[tool === this.tools.edit ? 'gs-bg-primary text-white': '', hasBackground ? 'cursor-pointer' : 'cursor-not-allowed opacity-50']"
                 @click="hasBackground ? handleChangeTab(this.tools.edit) : null">
                Edit
            </div>
        </div>
        <hr/>
        <div v-if="tool === this.tools.image" class="flex-1 h-px pt-2">
            <upload-images/>
        </div>
        <div v-else-if="tool === this.tools.text" class="flex-1 h-px">
            <div class="w-full">
                <div @click="handleAddText" class="btn-builder btn-sm w-1/2 mx-auto mt-4">
                    <svg-icon type="mdi" :path="mdiFormatText" size="18"></svg-icon>
                    <small>{{ $t('Add New Text') }}</small>
                </div>
                <font-setting-panel/>
            </div>
        </div>
        <div v-else-if="tool === this.tools.edit" class="flex-1 h-px">
            <image-setting-panel/>
        </div>
        <div class="mt-2 w-full text-xs max-md:hidden">
            Powered by <a href="https://www.thedripapps.com" target="_blank" class="gs-text-primary">Drip Apps</a>
        </div>
    </div>
</template>

<style scoped>

</style>
