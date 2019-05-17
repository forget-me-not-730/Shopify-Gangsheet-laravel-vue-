<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import stickerMixin from "@/Builder/Mixins/stickerMixin";

export default defineComponent({
    name: "SaveDesignModal",
    components: {Modal},
    mixins: [stickerMixin],
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
                this.designName = this.currentSticker?.name
            }
        }
    },
    methods: {
        handleDesignUpdate() {
            _stickerCanvasEditor.name = this.designName
            this.$emit('confirm')
            this.$emit('close')
        }
    }
})
</script>

<template>
    <modal :open="open">
        <div class="flex flex-col bg-builder border sm:rounded max-h-full max-w-md mx-auto">
            <div class="flex justify-between relative px-4 pt-2">
                <h1 class="text-lg font-bold mb-3">{{ $t('Save Sticker') }}</h1>
                <div class="absolute right-[14px] top-[3px] text-3xl cursor-pointer" @click="$emit('close')">
                    &times;
                </div>
            </div>
            <hr/>
            <div class="flex-1 p-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                <div class="text-left w-full">{{ $t('Sticker Name') }} <small class="text-red-500 text-xl">*</small></div>
                <input type="text" v-model="designName" required class="block w-full mt-2 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20"/>
            </div>
            <hr/>
            <div class="flex justify-end relative px-4 py-2">
                <button class="btn-secondary rounded px-5 mr-4" @click="$emit('close')" >{{ $t('Cancel') }}</button>
                <button class="btn-builder px-6 border border-black" :disabled="!designName" @click="handleDesignUpdate">{{ $t('Save') }}</button>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
