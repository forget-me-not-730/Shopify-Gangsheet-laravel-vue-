<script>
import {defineComponent} from 'vue'
import builderMixin from "@/Builder/Mixins/builderMixin";
import ToggleInput from "@/Builder/Components/ToggleInput.vue";
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";

export default defineComponent({
    name: "SettingArtboardMargin",
    components: {ToggleInput},
    mixins: [builderMixin, gangSheetSettingMixin],
    data() {
        return {
            artboardMargin: 0.125,
            artboardMarginEnabled: true,
        }
    },
    methods: {
        initializeSettings(canvas) {
            this.artboardMargin = canvas.artboardMargin || 0;
            this.artboardMarginEnabled = canvas.artboardMarginEnabled
        },
        handleMarginImageEnabledChange(e) {
            this.artboardMargin = window._gangSheetCanvasEditor.artboardMargin || 0;
            window._gangSheetCanvasEditor.toggleArtboardMarginEnabled(e.target.checked)
        },
        handleMarginImageChange(e) {
            const marginImage = Number(e.target.value)
            if (!isNaN(marginImage)) {
                window._gangSheetCanvasEditor.setArtboardMargin(marginImage)
            }
        },
    }
})
</script>

<template>
    <div class="flex flex-col">
        <div class="text-sm flex items-center justify-between">
            <span>{{ $t('Artboard Margin') }}</span>

            <toggle-input v-model="artboardMarginEnabled" :disabled="builderSettings.lockArtboardMargin" @change="handleMarginImageEnabledChange"/>
        </div>
        <div v-if="artboardMarginEnabled" class="flex items-center ml-auto mt-2">
            <input v-model="artboardMargin" type="number" @input="handleMarginImageChange"
                   :step="artBoardUnit === 'mm' ? 1 : 0.1"
                   min="0"
                   class="inp-builder w-20 pr-0 ml-2 pl-1"
                   :disabled="builderSettings.lockArtboardMargin"/>
            <span class="ml-1">{{ artBoardUnit }}</span>
        </div>
    </div>
</template>

<style scoped>

</style>
