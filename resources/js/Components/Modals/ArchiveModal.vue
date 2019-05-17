<script>
import {defineComponent} from 'vue'
import Modal from "@/Components/Modals/Modal.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiArchive, mdiClose} from '@mdi/js'

export default defineComponent({
    name: "ArchiveModal",
    components: {SvgIcon, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        },
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
            mdiArchive,
            mdiClose
        }
    }
})
</script>

<template>
    <Modal :open="open" @close="$emit('close')">
        <div class="w-full bg-white rounded-lg max-w-lg m-auto text-left">
            <div class="p-4 flex items-center justify-between">
                <span class="font-bold">Archive Order(s)</span>
                <div @click="$emit('close')">
                    <svg-icon type="mdi" :path="mdiClose" size="16"/>
                </div>
            </div>
            <hr/>
            <div class="p-4">
                <label class="cursor-pointer">
                    <input v-model="confirm" type="checkbox" class="mr-1"/>
                    If you archive the order(s), you cannot revert it.
                </label>

                <div class="flex py-2 items-center space-x-4 justify-end">
                    <button @click="$emit('archive')" class="flex items-center rounded-full bg-primary text-gray-100 py-1 px-4 cursor-pointer"
                       :class="[{'!bg-gray-400':!confirm}]"
                       target="_blank">
                        <svg-icon type="mdi" :path="mdiArchive" size="16"/>
                        Archive
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
