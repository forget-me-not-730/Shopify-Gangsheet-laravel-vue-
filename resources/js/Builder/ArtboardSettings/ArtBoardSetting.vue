<script>
import {defineComponent} from 'vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import SettingBackground from "@/Builder/ArtboardSettings/SettingBackground.vue";
import SettingSnapOnOff from "@/Builder/ArtboardSettings/SettingSnapOnOff.vue";
import SettingArtboardMargin from "@/Builder/ArtboardSettings/SettingArtboardMargin.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiCogOutline} from '@mdi/js'
import SettingVisualQuality from '@/Builder/ArtboardSettings/SettingVisualQuality.vue'
import SettingBackgroundWarning from "@/Builder/ArtboardSettings/SettingBackgroundWarning.vue";

export default defineComponent({
    name: "ArtBoardSetting",
    components: {SettingBackgroundWarning, SettingVisualQuality, SettingArtboardMargin, SettingSnapOnOff, SettingBackground, MenuItem, Menu, MenuItems, MenuButton, SvgIcon},
    data() {
        return {
            showResolutionLines: true,
            showMarginOutlines: true,
            mdiCogOutline
        }
    },
    created() {
        this.showResolutionLines = _gangSheetCanvasEditor.showResolutionLines
        this.showMarginOutlines = _gangSheetCanvasEditor.showMarginOutlines
    },
    methods: {
        handleShowResolutionLinesChange(e) {
            _gangSheetCanvasEditor.showResolutionLines = e.target.checked
            _gangSheetCanvasEditor.renderAll()
        },
        handleShowMarginOverlappingLines(e) {
            _gangSheetCanvasEditor.showMarginOutlines = e.target.checked
            _gangSheetCanvasEditor.renderAll()
        }
    }
})
</script>

<template>
    <Menu as="div" class="relative inline-block text-left w-full">
        <menu-button
            class="flex w-full items-center justify-center px-3 py-1">
            <svg-icon type="mdi" :path="mdiCogOutline" size="16" />
            <span class="text-sm">{{ $t('Settings') }}</span>
        </menu-button>

        <transition enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95">
            <menu-items class="absolute right-2 z-50 mt-1 p-2 max-sm:w-56 w-56 origin-top-right rounded-md bg-builder shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="py-1 space-y-2">
                    <div class="pl-1">
                        <setting-snap-on-off/>
                    </div>
                    <div>
                        <label class="flex items-center px-1">
                            <input type="checkbox"
                                   @change="handleShowResolutionLinesChange"
                                   v-model="showResolutionLines"
                                   class="w-4 h-4 mr-2 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2">
                            <span class="text-sm">{{ $t('Show Resolution Lines') }}</span>
                        </label>
                    </div>
                    <div>
                        <label class="flex items-center px-1">
                            <input type="checkbox"
                                   @change="handleShowMarginOverlappingLines"
                                   v-model="showMarginOutlines"
                                   class="w-4 h-4 mr-2 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2">
                            <span class="text-sm">{{ $t('Overlapping Lines') }}</span>
                        </label>
                    </div>
                    <div class="px-1">
                        <setting-artboard-margin/>
                    </div>
                    <div class="px-1">
                        <setting-background/>
                    </div>
                    <div class="px-1">
                        <setting-visual-quality />
                    </div>
                    <div class="px-1">
                        <setting-background-warning />
                    </div>
                </div>
            </menu-items>
        </transition>
    </Menu>
</template>

<style scoped>

</style>
