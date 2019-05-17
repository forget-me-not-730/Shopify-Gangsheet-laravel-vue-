<script>
import {defineComponent} from 'vue'
import AnimationIcon from "@/Builder/Icons/AnimationIcon.vue";
import ChevronLeftIcon from "@/Builder/Icons/ChevronLeftIcon.vue";
import InvoiceTextPlusOutlineIcon from "@/Builder/Icons/InvoiceTextPlusOutlineIcon.vue";
import SquareEditOutlineIcon from "@/Builder/Icons/SquareEditOutlineIcon.vue";
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";

export default defineComponent({
    name: "SizeUploadDesigns",
    components: {SquareEditOutlineIcon, InvoiceTextPlusOutlineIcon, ChevronLeftIcon, AnimationIcon},
    mixins: [gangSheetMixin],
    methods: {
        handleDesignClick(index) {
            if (this.workingDesignIndex !== index) {
                this.$gsb.updateCanvasData()
                this.workingDesignIndex = index
                this.$nextTick(() => {
                    this.images = this.currentDesign.meta.images ?? []
                })
            }
        },
    }
})
</script>

<template>
    <div class="absolute bg-builder border py-2 w-[290px] top-14 z-30 flex flex-col min-w-[150px] p-2 transition-all font-thin bottom-5"
         :class="[openWorkingDesigns ? 'right-5' : 'right-[-275px]']">
        <div class="absolute w-5 h-12 bg-builder border border-r-0 rounded rounded-r-none right-full top-[-1px] cursor-pointer flex items-center justify-center"
             @click="openWorkingDesigns = !openWorkingDesigns">
            <div class="transition-all" :class="{'rotate-180':openWorkingDesigns}">
                <chevron-left-icon size="20" class="shrink-0"/>
            </div>
        </div>
        <div class="w-full h-full overflow-y-auto tiny-scroll-bar text-xs space-y-2 pb-20">

            <div class="font-bold">({{ workingDesigns.length }}) &nbsp; {{ $t('Gang Sheets') }}</div>

            <div v-for="(design, index) in workingDesigns" class="rounded cursor-pointer border relative p-2"
                 :key="design.id"
                 :class="[index === workingDesignIndex ? 'gs-border-primary' :'border-gray-300']"
                 @click="handleDesignClick(index)">

                <div class="flex items-center px-1" :class="{'font-bold': index === 0, 'text-yellow-600': design.meta.variant.visible === 'Hidden'}">
                    <span class="text-2xs pt-0.5">
                        {{ design.meta.variant.width }} x {{ design.meta.variant.height }} {{ design.meta.variant.unit }}
                    </span>
                </div>
                <div class="flex">
                    <div class="p-1 flex-1">
                        {{ design.name }}
                    </div>
                </div>
                <small class="my-1 px-1 font-bold opacity-80"> {{ design.objects.length }} {{ $t('Images') }}</small>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 w-[286px] p-2 text-xs bg-builder">
            Powered by <a href="http://www.thedripapps.com" target="_blank" class="text-blue-500">Drip Apps</a>
        </div>
    </div>
</template>

<style scoped>

</style>
