<script>
import {defineComponent} from 'vue'
import {Sketch} from '@lk77/vue3-color'
import builderMixin from '@/Builder/Mixins/builderMixin'
import ToggleInput from "@/Builder/Components/ToggleInput.vue";

export default defineComponent({
    name: 'SettingBackgroundWarning',
    components: {ToggleInput, Sketch},
    mixins: [builderMixin],
    data() {
        return {
            enableImageBackgroundWarning: true
        }
    },
    mounted() {
        this.enableImageBackgroundWarning = this.artBoardSettings.enableImageBackgroundWarning
    },
    methods: {
        async handleChange(e) {
            this.$gsb.setArtBoardSettings('enableImageBackgroundWarning', e.target.checked)
        }
    }
})
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="text-sm whitespace-nowrap max-sm:text-xs">{{ $t('Background Warning') }}:</div>
        <toggle-input v-model="enableImageBackgroundWarning" @change="handleChange"/>
    </div>
</template>
