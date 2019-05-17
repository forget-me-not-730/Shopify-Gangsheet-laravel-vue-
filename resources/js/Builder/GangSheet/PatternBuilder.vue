<template>
    <file-uploader>
        <div class="flex flex-col h-full w-full">
            <div v-if="renderComponent" class="flex flex-1 h-1 w-full md:flex-row max-md:flex-col-reverse">
                <tool-bar/>
                <gang-sheet-art-board>
                    <template v-slot:right-panel>
                        <product-patterns/>
                    </template>
                </gang-sheet-art-board>
            </div>
        </div>

        <gang-sheet-builder-modals/>
        <confirmation/>
    </file-uploader>
</template>

<script>

import GangSheetArtBoard from './GangSheetArtBoard.vue'
import FileUploader from '@/Builder/Components/FileUploader.vue'
import GangSheetBuilderModals from '../Modals/GangSheetBuilderModals.vue'
import ToolBar from '../Components/ToolBar.vue'

import builderMixin from '@/Builder/Mixins/builderMixin'
import rerenderMixin from '@/Builder/Mixins/rerenderMixin'
import initBuilderMixin from "@/Builder/Mixins/initBuilderMixin";

import {defineAsyncComponent} from 'vue'
import ProductPatterns from "@/Builder/Components/ProductPatterns.vue";

const Confirmation = defineAsyncComponent(() => import('../Components/Confirmation.vue'))

export default {
    name: 'PatternBuilder',
    components: {ProductPatterns, Confirmation, FileUploader, GangSheetArtBoard, ToolBar, GangSheetBuilderModals},
    mixins: [builderMixin, rerenderMixin, initBuilderMixin],
}
</script>

<style scoped>

</style>
