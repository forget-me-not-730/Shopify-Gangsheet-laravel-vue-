<script>
import {defineComponent} from 'vue'
import SettingArtboardMargin from "@/Builder/ArtboardSettings/SettingArtboardMargin.vue";
import SettingSnapOnOff from "@/Builder/ArtboardSettings/SettingSnapOnOff.vue";
import SettingBackground from "@/Builder/ArtboardSettings/SettingBackground.vue";
import SettingVisualQuality from "@/Builder/ArtboardSettings/SettingVisualQuality.vue";
import {mdiCogOutline} from "@mdi/js";
import ToggleInput from "@/Builder/Components/ToggleInput.vue";
import eventBus from '@/Builder/Utils/eventBus'
import SettingBackgroundWarning from "@/Builder/ArtboardSettings/SettingBackgroundWarning.vue";
import GbsSelect from '@/Components/Select.vue'
import builderMixin from "@/Builder/Mixins/builderMixin";
import { LANGUAGES } from "@/Builder/Utils/constants";

export default defineComponent({
    name: "GangSheetArtBoardSettings",
    components: {SettingBackgroundWarning, ToggleInput, SettingVisualQuality, SettingBackground, SettingSnapOnOff, SettingArtboardMargin, GbsSelect},
    mixins: [builderMixin],
    data() {
        return {
            showResolutionLines: true,
            showMarginOutlines: true,
            languages: LANGUAGES,
            selectedLanguage: LANGUAGES.find(lang => lang.value === 'en'),
            mdiCogOutline
        }
    },
    mounted() {
        if (_gangSheetCanvasEditor) {
            this.showResolutionLines = _gangSheetCanvasEditor.showResolutionLines
            this.showMarginOutlines = _gangSheetCanvasEditor.showMarginOutlines
            this.selectedLanguage = this.languages.find(lang => lang.value === this.shop.settings.language) || this.selectedLanguage;
        }
        eventBus.$on(eventBus.UPDATE_SHOW_RESOLUTION_LINES, (newValue) => {
            this.showResolutionLines = newValue;
        })
        eventBus.$on(eventBus.UPDATE_SHOW_OVERLAPPING_LINES, (newValue) => {
            this.showMarginOutlines = newValue;
        })
    },
    computed: {
        language: {
            get() {
                return this.selectedLanguage;
            },
            set(v) {
                this.selectedLanguage = this.languages.find(lang => lang.value === v.value) || this.languages.find(lang => lang.value === 'en');
                this.setLocale(this.selectedLanguage.value)
            }
        },
    },
    methods: {
        handleShowResolutionLinesChange(e) {
            if (_gangSheetCanvasEditor) {
                _gangSheetCanvasEditor.showResolutionLines = e.target.checked
                _gangSheetCanvasEditor.renderAll()
            }
            eventBus.$emit(eventBus.UPDATE_SHOW_RESOLUTION_LINES, e.target.checked);
        },
        handleShowMarginOverlappingLines(e) {
            if (_gangSheetCanvasEditor) {
                _gangSheetCanvasEditor.showMarginOutlines = e.target.checked
                _gangSheetCanvasEditor.renderAll()
            }
            eventBus.$emit(eventBus.UPDATE_SHOW_OVERLAPPING_LINES, e.target.checked);
        }
    }
})
</script>

<template>
    <div class="py-4 px-2 space-y-4">
        <div v-if="!patternMode" class="flex items-center justify-between">
            <span class="text-sm">{{ $t('Show Resolution Lines') }}</span>
            <toggle-input v-model="showResolutionLines" @change="handleShowResolutionLinesChange"/>
        </div>
        <div v-if="!patternMode" class="flex items-center justify-between">
            <span class="text-sm">{{ $t('Overlapping Lines') }}</span>
            <toggle-input v-model="showMarginOutlines" @change="handleShowMarginOverlappingLines"/>
        </div>
        <setting-snap-on-off/>
        <setting-visual-quality/>
        <setting-background-warning />
        <setting-background/>
        <setting-artboard-margin/>
        <div class="flex items-center justify-between md:hidden">
            <span class="text-sm whitespace-nowrap max-sm:text-xs">{{ $t('Language') }}:</span>
            <gbs-select class="w-36 z-50" :options="languages" v-model="language">
                <template #selected="{selected}">
                    <span class="font-medium text-gray-900">{{ selected.label }}</span>
                </template>
                <template #option="{option}">
                    <span class="font-medium text-gray-900">{{ option.label }}</span>
                </template>
            </gbs-select>
        </div>
    </div>
</template>

<style scoped>

</style>
