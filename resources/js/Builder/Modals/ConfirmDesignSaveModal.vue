<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";

export default defineComponent({
    name: "ConfirmDesignSaveModal",
    components: {Modal},
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
        saveAndContinue() {
            window._gangSheetCanvasEditor.name = this.designName
            this.$gsb.updateCanvasData()
            this.$emit('confirm', true)
            this.$emit('close')
        },
        dontSaveAndContinue() {
            this.$emit('confirm', false)
            this.$emit('close')
        }
    }
})
</script>

<template>
    <modal :open="open">
        <div class="flex flex-col bg-builder border sm:rounded max-h-full max-w-md mx-auto">
            <div class="flex-1 p-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                <div class="w-full text-left mb-5">
                    <p>{{ $t('You have unsaved design modification.') }}</p>
                    <p>{{ $t('Do you want to save your work?') }}</p>
                </div>
                <div class="text-left w-full text-sm">Design Name <small class="text-red-500 text-xl">*</small></div>
                <input type="text" v-model="designName" required class="inp-builder w-full mt-2"/>
            </div>
            <hr class="mt-4"/>
            <div class="flex justify-end relative px-4 py-2">
                <button class="btn-builder-outline mr-2" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </button>
                <button class="btn-builder mr-2" :disabled="!designName" @click="saveAndContinue">
                    {{ $t('Save') }}
                </button>
                <button class="btn-builder" @click="dontSaveAndContinue">
                    {{ $t('No, Thanks') }}
                </button>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
