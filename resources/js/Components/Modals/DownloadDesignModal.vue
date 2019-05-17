<script>
import {defineComponent} from 'vue'
import Modal from "@/Components/Modals/Modal.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiDownload, mdiClose} from '@mdi/js'

export default defineComponent({
    name: "DownloadDesignModal",
    components: {SvgIcon, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        },
        design: {
            type: Object,
            required: true
        }
    },
    watch: {
        open(value) {
            if (value) {
                this.confirm = false
            }
        }
    },
    data() {
        return {
            confirm: false,
            mdiDownload,
            mdiClose
        }
    }
})
</script>

<template>
    <Modal :open="open" @close="$emit('close')">
        <div class="w-full bg-white rounded-lg max-w-lg m-auto text-left">
            <div class="p-4 flex items-center justify-between">
                <span class="font-bold">Download Design</span>
                <div @click="$emit('close')">
                    <svg-icon type="mdi" :path="mdiClose" size="16"/>
                </div>
            </div>
            <hr/>
            <div class="p-4">
                <label class="cursor-pointer">
                    <input v-model="confirm" type="checkbox" class="mr-1"/>
                    This design hasn't been paid for yet. Please confirm that we will deduct your credit when you download it.
                </label>

                <div class="flex py-2 items-center space-x-4 justify-end">
                    <a class="btn-primary cursor-pointer" :href="route('merchant.design.download', {design_id: design.id})" target="_blank">
                        View Watermark
                    </a>
                    <a class="flex items-center rounded-full bg-primary text-gray-100 py-1 px-4 cursor-pointer"
                       :class="[{'!bg-gray-400':!confirm}]"
                       :href="confirm ? route('merchant.design.pay-and-download', {design_id: design.id}) : 'Javascript:void(0)'"
                       target="_blank">
                        <svg-icon type="mdi" :path="mdiDownload" size="16"/>
                        Download
                    </a>
                </div>
            </div>
        </div>
    </Modal>
</template>
