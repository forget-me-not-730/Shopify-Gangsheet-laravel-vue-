<script>
import {defineComponent} from 'vue'
import Spinner from "@/Builder/Components/Spinner.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import designSubmitMixin from "@/Builder/Mixins/designSubmitMixin";
import ChevronLeftIcon from "@/Builder/Icons/ChevronLeftIcon.vue";
import {
    mdiCheckCircle
} from '@mdi/js'

export default defineComponent({
    name: "ProductPatterns",
    mixins: [gangSheetMixin, designSubmitMixin],
    components: {ChevronLeftIcon, SvgIcon, Spinner},
    methods: {
        handleVariantClick(index) {
            this.$gsb.updateCanvasData()
            this.$store.commit('setStore', {path: 'variant', value: this.variants[index]})
        },
        handleOpacityChange() {
            window._gangSheetCanvasEditor.setOpacity(this.opacity)
        }
    },
    data() {
        return {
            opacity: 1,
            mdiCheckCircle
        }
    }
})
</script>

<template>
    <div class="absolute bg-builder border py-2 w-[290px] top-14 z-30 flex flex-col min-w-[150px] p-2 transition-all font-thin bottom-5"
         :class="[openWorkingDesigns ? 'right-5' : 'right-[-275px]']">
        <div class="absolute w-5 h-12 bg-builder border border-r-0 rounded rounded-r-none right-full top-[-1px] cursor-pointer flex items-center justify-center"
             @click="openWorkingDesigns = !openWorkingDesigns">
            <div class="transition-all" :class="{'rotate-180':openWorkingDesigns}">
                <chevron-left-icon/>
            </div>
        </div>
        <div class="w-full h-full overflow-y-auto tiny-scroll-bar text-xs space-y-2 pb-20">
            <div class="font-bold mt-1"> {{ $t('Product Patterns') }}</div>

            <div v-for="(v, index) in variants" class="rounded cursor-pointer border relative p-2"
                 :key="v.id"
                 :class="[variant.id === v.id ? 'gs-border-primary' :'border-gray-300']"
                 @click="handleVariantClick(index)">
                 <div class="flex justify-between w-full">
                    <span class="pt-0.5">{{ v.title || v.label }}</span>
                    <svg-icon v-if="v.pattern?.objects?.length??false" type="mdi" :path="mdiCheckCircle" />
                </div>
            </div>

            <!-- <div class="flex items-center justify-between mt-1">
                <label class="w-20 pl-4">{{ $t('Opacity') }}: </label>
                <div class="inp-builder flex-1 flex">
                    <input v-model="opacity" type="number" @change="handleOpacityChange" min="0" max="1" step="0.1" class="w-16 py-1 pl-1 inp-no-style">
                    <input v-model="opacity" type="range" @change="handleOpacityChange" :min="0" :max="1" :step="0.1" class="flex-1"/>
                </div>
            </div> -->
        </div>

        <div class="absolute bottom-0 left-0 w-[286px] p-2 text-xs bg-builder">
            Powered by <a href="http://www.thedripapps.com" target="_blank" class="gs-text-primary">Drip Apps</a>
        </div>
    </div>
</template>

<style scoped>

</style>
