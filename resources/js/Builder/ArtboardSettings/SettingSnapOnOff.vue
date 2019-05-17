<script>
import {defineComponent} from 'vue'
import ToggleInput from "@/Builder/Components/ToggleInput.vue";
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";

export default defineComponent({
    name: "SettingSnapOnOff",
    components: {ToggleInput},
    mixins: [gangSheetSettingMixin],
    data() {
        return {
            snapEnabled: true
        }
    },
    methods: {
        initializeSettings(canvas) {
            this.snapEnabled = canvas.snapEnabled
        },
        handleSnapEnabledChange(e) {
            _gangSheetCanvasEditor.toggleSnapEnabled(e.target.checked)
        }
    }
})
</script>

<template>
    <div class="flex items-center justify-between">
        <span class="text-sm whitespace-nowrap">{{ $t('Snap when moving') }}</span>
        <toggle-input v-model="snapEnabled" @change="handleSnapEnabledChange"/>
    </div>
</template>

<style scoped>

</style>
