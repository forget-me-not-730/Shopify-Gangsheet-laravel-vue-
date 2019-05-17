<template>
    <div class="max-sm:hidden absolute top-[65px] w-[360px] z-50 left-[calc(50%-180px)] text-xs space-y-1">
        <div v-if="marginOverlapping.show" class="bg-orange-300 bg-opacity-50 flex items-center py-1 px-2 rounded">
            <alert-circle-outline-icon class="w-4 h-4 inline-block mr-1"/>
            <p>{{ $t('Images are overlapping') }}</p>
        </div>

        <div v-if="designOverlapping.show" class="bg-orange-300 bg-opacity-50 flex items-center py-1 px-2 rounded">
            <alert-circle-outline-icon class="w-4 h-4 inline-block mr-1"/>
            <p>{{ $t('Design is overlapping art board. Please select a larger size.') }}</p>
        </div>

        <div v-if="alertLoading" class="bg-gray-500 bg-opacity-75 flex items-center py-2 px-2 rounded">
            <spinner class="mr-1" size="3"/>
            <p>{{ $t(alertLoading) }}</p>
        </div>
    </div>
</template>

<script>
import eventBus from "@/Builder/Utils/eventBus";
import AlertCircleOutlineIcon from "@/Builder/Icons/AlertCircleOutlineIcon.vue";
import Spinner from "@/Builder/Components/Spinner.vue";
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";
import builderMixin from "../Mixins/builderMixin";

export default {
    name: "BuilderAlerts",
    components: {Spinner, AlertCircleOutlineIcon},
    mixins: [builderMixin, gangSheetSettingMixin],
    data() {
        return {
            marginOverlapping: {
                allowed: true,
                show: false
            },
            designOverlapping: {
                allowed: true,
                show: false
            },
            alertLoading: null
        }
    },
    mounted() {
        eventBus.$on(eventBus.ALERT_LOADING, message => {
            this.alertLoading = message
        })
    },
    unmounted() {
        eventBus.$off(eventBus.ALERT_LOADING, this.addEventListeners)
    },
    methods: {
        initializeSettings(canvas) {
            if (this.patternMode)
                return
            canvas.on({
                'object:overlapping': ({status}) => {
                    if (this.marginOverlapping.allowed) {
                        this.marginOverlapping.show = status
                    }
                },
                'object:off-board': ({status}) => {
                    if (this.designOverlapping.allowed) {
                        this.designOverlapping.show = status
                    }
                }
            })
            this.updateOverlappingStatus(canvas)
        },
        updateOverlappingStatus(canvas) {
            this.marginOverlapping.show = canvas.imagesError;
            this.designOverlapping.show = canvas.artBoardError;
        }
    }
}
</script>

<style scoped>

</style>
