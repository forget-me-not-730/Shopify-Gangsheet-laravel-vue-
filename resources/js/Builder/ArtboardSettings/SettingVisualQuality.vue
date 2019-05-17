<script>
import {defineComponent} from 'vue'
import {Sketch} from '@lk77/vue3-color'
import builderMixin from '@/Builder/Mixins/builderMixin'
import eventBus from '@/Builder/Utils/eventBus'
import ToggleInput from "@/Builder/Components/ToggleInput.vue";

export default defineComponent({
    name: 'SettingVisualQuality',
    components: {ToggleInput, Sketch},
    mixins: [builderMixin],
    data() {
        return {
            visualQuality: true
        }
    },
    mounted() {
        this.visualQuality = this.artBoardSettings.visualQuality
    },
    methods: {
        async handleChange(e) {
            this.$gsb.setArtBoardSettings('visualQuality', e.target.checked)
            this.loadingDesign = true
            eventBus.$emit(eventBus.REFRESH_BUILDER)
        }
    }
})
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="text-sm whitespace-nowrap max-sm:text-xs">{{ $t('Visual Quality') }}:</div>
        <toggle-input v-model="visualQuality" @change="handleChange"/>
    </div>
</template>
