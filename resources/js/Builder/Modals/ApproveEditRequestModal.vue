<script>
import {defineComponent} from 'vue'
import Modal from "./Modal.vue";
import Spinner from "@/Components/Spinner.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiClose, mdiInformationOutline} from "@mdi/js";
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";

export default defineComponent({
    name: "ApproveEditRequestModal",
    components: {SvgIcon, Spinner, Modal},
    mixins: [gangSheetMixin],
    data() {
        return {
            open: false,
            approve: true,
            decline_reason: '',
            error: '',
            loading: false,

            mdiClose,
            mdiInformationOutline
        }
    },
    mounted() {
        if (this.editRequest === 'pending') {
            this.openApproveEditRequestModal = true
        }
    },
    methods: {
        handleSubmit() {
            if (!this.approve && !this.decline_reason) {
                this.error = 'please provide decline reason.'
            }
            this.loading = true

            let ApproveUrl = route('builder.approve-edit-request', this.currentDesign.designId)
            let declineUrl = route('builder.decline-edit-request', this.currentDesign.designId)

            if (this.approve) {
                axios.post(ApproveUrl).then(() => {
                    this.openApproveEditRequestModal = false
                    this.editRequest = 'approved'
                    this.loading = false
                })
            } else {
                axios.post(declineUrl, {
                    decline_reason: this.decline_reason
                }).then(() => {
                    this.openApproveEditRequestModal = false
                    this.editRequest = 'declined'
                    this.loading = false
                })
            }
        }
    }
})
</script>

<template>
    <modal :open="openApproveEditRequestModal" @close="$emit('close')">
        <div class="flex flex-col max-w-xl bg-builder border sm:rounded mx-auto">
            <div class="flex items-center justify-between py-3 px-6">
                <h4 class="text-2xl text-left font-semibold">{{ $t('Approve Edit Request') }}</h4>
                <div class="cursor-pointer" @click="openApproveEditRequestModal = false">
                    <svg-icon type="mdi" :path="mdiClose"/>
                </div>
            </div>
            <hr/>
            <div class="p-6 text-left">
                <div class="text-warning flex items-center">
                    <svg-icon type="mdi" :path="mdiInformationOutline" size="16" class="mr-2"/>
                    {{ $t('This design is requested to be edited by the customer.') }}
                </div>
                <p class="mt-2">
                    {{ $t('Please approve or decline this request.') }}
                </p>

                <div class="flex items-center text-sm w-full space-x-5 my-1 mt-2">
                    <label class="inline-flex items-center cursor-pointer">
                        <input v-model="approve" name="quote_type" :value="true" type="radio"
                               class="form-radio h-4 w-4 text-gray-700 focus:ring-gray-700">
                        <span class="ml-2 text-gray-700">{{ $t('Approve') }}</span>
                    </label>

                    <label class="inline-flex items-center cursor-pointer">
                        <input v-model="approve" name="quote_type" :value="false" type="radio"
                               class="form-radio h-4 w-4 text-gray-700 focus:ring-gray-700">
                        <span class="ml-2 text-gray-700">{{ $t('Decline') }}</span>
                    </label>
                </div>

                <div v-if="!approve" class="mt-2 w-full">
                    <label>{{ $t('Reason for declining') }}</label>
                    <textarea v-model="decline_reason"
                              class="w-full border focus:border-gray-700 rounded focus:ring-gray-700"
                              rows="3"></textarea>
                </div>

                <div class="mt-5 flex justify-end">
                    <button @click="handleSubmit"
                            :disabled="loading"
                            class="items-center flex bg-black text-white disabled:bg-gray-500 hover:bg-gray-700 rounded py-2 px-4 max-sm:text-sm max-sm:ml-1 max-sm:px-2 ml-2">
                        <spinner v-if="loading" class="mr-2"/>
                        {{ $t('Submit') }}
                    </button>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
