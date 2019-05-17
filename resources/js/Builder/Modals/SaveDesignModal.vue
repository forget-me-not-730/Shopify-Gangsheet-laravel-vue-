<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default defineComponent({
    name: "SaveDesignModal",
    components: {CloseIcon, Modal},
    mixins: [gangSheetMixin],
    props: {
        open: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            designName: null
        }
    },
    watch: {
        open: {
            immediate: true,
            handler() {
                this.designName = this.currentDesign?.name
            }
        }
    },
    methods: {
        handleDesignUpdate() {
            window._gangSheetCanvasEditor.name = this.designName
            this.$gsb.updateCanvasData()
            this.$emit('confirm')
            this.$emit('close')
        }
    }
})
</script>

<template>
    <modal :open="open">
        <div class="flex flex-col bg-builder border sm:rounded max-h-full max-w-md mx-auto">
            <div class="flex justify-between items-center relative px-4 py-2">
                <h1 class="text-lg font-bold">{{ $t('Save Design') }}</h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="flex-1 p-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                <div class="text-left w-full">{{ $t('Design Name') }} <small class="text-red-500 text-xl">*</small></div>
                <input type="text" v-model="designName" required class="inp-builder w-full mt-1"/>
            </div>
            <hr/>
            <div class="flex justify-end relative px-4 py-2">
                <button class="btn-builder-outline mr-4" @click="$emit('close')">{{ $t('Cancel') }}</button>
                <button class="btn-builder" :disabled="!designName" @click="handleDesignUpdate">{{ $t('Save') }}</button>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
