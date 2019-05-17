<script>
import {defineComponent} from "vue";
import Modal from "@/Builder/Modals/Modal.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import UploadImages from "@/Builder/Sticker/StickerUploadImages.vue";

export default defineComponent({
    name: "ImageModal",
    components: {CloseIcon, Modal, UploadImages},
    mixins: [],
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        data: {
            type: [Object, null],
            default: null,
        },
    },
    data() {
        return {}
    },
    computed: {
        classes() {
            return {
                body: '!w-full h-full'
            }
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')" :classes="classes">
        <div class="rounded bg-white flex flex-col h-full w-full">
            <div class="flex justify-between items-center relative px-4 py-2">
                <h6 class="text-lg font-bold">Images</h6>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr>
            <div class="grow p-2 overflow-y-auto">
                <upload-images/>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
