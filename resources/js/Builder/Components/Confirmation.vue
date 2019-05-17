<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import confirmationMixin from "@/Builder/Mixins/confirmationMixin";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default defineComponent({
    name: "Confirmation",
    components: {CloseIcon, Modal},
    mixins: [confirmationMixin],
})
</script>

<template>
    <modal :open="Boolean(this.confirmation)" class="pointer-events-none">
        <div v-if="Boolean(this.confirmation)" class="bg-gray-50 w-[95%] shadow-lg my-auto border rounded-lg max-w-md mx-auto text-left mt-10">
            <div class="px-4 py-2 flex items-center justify-between">
                <h2 class="text-lg font-semibold">
                    {{ confirmation?.title || $t('Are you sure?') }}
                </h2>

                <div @click="handleCancel" class="cursor-pointer">
                    <close-icon/>
                </div>
            </div>
            <hr class="bg-gray-300"/>
            <div class="p-4">
                <p v-if="confirmation?.description" v-html="confirmation.description.replace(/\n/g, '<br>')"></p>
                <div class="flex items-center justify-end mt-5 space-x-2">
                    <button
                        @click.stop.prevent="handleCancel"
                        class="btn-builder-outline pointer-events-auto"
                    >
                        {{ $t('Cancel') }}
                    </button>
                    <button
                        @click.stop="handleConfirm"
                        class="btn-builder pointer-events-auto"
                    >
                        {{ $t('Confirm') }}
                    </button>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
