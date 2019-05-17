<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import confirmationMixin from "@/Builder/Mixins/confirmationMixin";

export default defineComponent({
    name: "Confirmation",
    components: {Modal},
    mixins: [confirmationMixin],
})
</script>

<template>
    <modal :open="Boolean(this.confirmation)" class="pointer-events-none">
        <div v-if="Boolean(this.confirmation)" class="bg-gray-50  w-[95%] p-4 shadow-lg my-auto border rounded max-w-sm mx-auto text-left mt-10">
            <h2 class="text-lg font-semibold">
                {{ confirmation?.title || $t('Are you sure?') }}
            </h2>
            <p v-if="confirmation?.description">{{ confirmation.description }}</p>
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
    </modal>
</template>

<style scoped>

</style>
