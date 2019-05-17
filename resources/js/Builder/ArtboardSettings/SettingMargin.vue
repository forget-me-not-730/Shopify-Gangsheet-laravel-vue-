<script>
import {defineComponent} from 'vue'
import builderMixin from "@/Builder/Mixins/builderMixin";
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";

export default defineComponent({
    name: "SettingMargin",
    mixins: [builderMixin, gangSheetSettingMixin],
    data() {
        if (this.patternMode) {
            return {
                margin: 0,
                marginEnabled: false
            }
        }
        return {
            margin: 0.125,
            marginEnabled: true
        }
    },
    methods: {
        initializeSettings(canvas) {
            this.margin = canvas.margin || 0;
            this.marginEnabled = canvas.marginEnabled
        },
        handleMarginEnabledChange(e) {
            this.margin = window._gangSheetCanvasEditor.margin || 0;
            window._gangSheetCanvasEditor.toggleMarginEnabled(e.target.checked)
        },
        handleMarginChange(e) {
            const margin = Number(e.target.value)
            if (!isNaN(margin)) {
                window._gangSheetCanvasEditor.setMargin(margin)
            }
        },
    }
})
</script>

<template>
    <div class="flex items-center cursor-pointer">
        <label v-if="!builderSettings.lockMargin" class="text-sm flex items-center">
            <input v-model="marginEnabled" type="checkbox" @change="handleMarginEnabledChange" class="mr-1">
            {{ $t('Image Margin') }}
        </label>
        <template v-if="marginEnabled">
            <input v-model="margin" type="number" @input="handleMarginChange"
                   :step="artBoardUnit === 'mm' ? 1 : 0.1"
                   min="0"
                   class="inp-builder h-7 border w-16 pr-0 ml-2 pl-1"
                   :disabled="builderSettings.lockMargin"/>
            <span class="ml-1">{{ artBoardUnit }}</span>
        </template>
    </div>
</template>

<style scoped>

</style>
