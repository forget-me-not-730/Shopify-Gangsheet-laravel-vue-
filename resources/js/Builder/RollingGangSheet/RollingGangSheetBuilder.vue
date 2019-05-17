<script>
import {defineComponent} from 'vue'
import FileUploader from "@/Builder/Components/FileUploader.vue";
import GangSheetBuilderModals from "@/Builder/Modals/GangSheetBuilderModals.vue";
import RollingGangSheetArtBoard from "@/Builder/RollingGangSheet/RollingGangSheetArtBoard.vue";
import ToolBar from "@/Builder/Components/ToolBar.vue";
import builderMixin from "@/Builder/Mixins/builderMixin";
import rerenderMixin from "@/Mixins/rerenderMixin";
import initBuilderMixin from "@/Builder/Mixins/initBuilderMixin";

import {defineAsyncComponent} from 'vue'

const GangSheetsPreview = defineAsyncComponent(() => import('@/Builder/Components/GangSheetsPreview.vue'))
const Confirmation = defineAsyncComponent(() => import('../Components/Confirmation.vue'))

export default defineComponent({
    name: "RollingGangSheetBuilder",
    components: {RollingGangSheetArtBoard, GangSheetsPreview, ToolBar, GangSheetBuilderModals, FileUploader, Confirmation},
    mixins: [builderMixin, rerenderMixin, initBuilderMixin]
})
</script>

<template>
    <file-uploader>
        <div class="flex flex-col h-full w-full">
            <gang-sheets-preview v-if="gangSheetsPreview"/>
            <div v-else-if="renderComponent" class="flex flex-1 h-1 w-full md:flex-row max-md:flex-col-reverse">
                <tool-bar/>
                <rolling-gang-sheet-art-board/>
            </div>
        </div>

        <gang-sheet-builder-modals/>
        <confirmation/>
    </file-uploader>
</template>

<style scoped>

</style>
