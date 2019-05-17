<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";
import Spinner from "@/Components/Spinner.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import { mdiClose } from '@mdi/js'
import DangerButton from "@/Components/DangerButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

export default defineComponent({
    name: "Confirmation",
    components: {SvgIcon, Spinner, Modal, DangerButton, PrimaryButton},
    mixins: [ConfirmationMixin],
    data() {
        return {
            mdiClose
        }
    }
})
</script>

<template>
    <Modal :open="Boolean(this.confirmation)">
        <div v-if="Boolean(this.confirmation)" class="rounded-2xl bg-white w-full max-w-md mx-auto overflow-hidden border">
            <div class="bg-gray-100 py-3 px-4 flex items-center justify-between">
                <h2 class="text-base text-left font-semibold">
                    {{ confirmation?.title || 'Confirmation.' }}
                </h2>

                <div class="flex items-center justify-center cursor-pointer text-gray-700" @click="confirmation = null">
                    <svg-icon type="mdi" :path="mdiClose" size="20" />
                </div>
            </div>
            <div v-if="confirmation?.description" v-html="confirmation?.description" class="border-t text-sm text-left p-4"></div>
            <div class="flex items-center justify-end space-x-3 py-3 px-4 border-t">
                <button
                    @click="handleCancel"
                    :disabled="loading"
                    class="btn-secondary text-xs rounded-full py-2 px-4"
                >
                    Cancel
                </button>
                <button
                    @click="handleConfirm"
                    class="flex items-center justify-center text-xs rounded-full py-2 px-4"
                    :disabled="loading"
                    :class="[confirmation?.type === 'danger' ? 'btn-danger' : 'btn-primary']"
                >
                    <spinner v-if="loading" class="mr-1" />
                    <span>{{ confirmation.action || 'Confirm' }}</span>
                </button>
            </div>
        </div>
    </Modal>
</template>

<style scoped>

</style>
