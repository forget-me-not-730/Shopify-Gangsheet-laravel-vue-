<script>
import {defineComponent} from 'vue'
import Modal from "./Modal.vue";
import {getStripeCheckoutUrl} from "@/Woo/Apis/gsbApi";
import Spinner from "@/Components/Spinner.vue";

export default defineComponent({
    name: "AddCreditModal",
    components: {Spinner, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            amount: 0,
            loading: false
        }
    },
    watch: {
        open() {
            this.amount = 0
        }
    },
    methods: {
        handleContinue() {
            this.loading = true
            getStripeCheckoutUrl({amount: this.amount}).then(res => {
                if (res.data.success) {
                    window.location.href = res.data.url
                } else {
                    window.Toast.error({
                        message: res.data.error
                    })
                    this.loading = false
                }
            })
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col max-w-sm bg-white rounded mx-auto max-sm:h-full">
            <h4 class="text-lg py-3 px-6 text-left font-semibold">Credit Amount</h4>
            <hr/>
            <div class="p-6 pt-4 text-left">

                <label>Amount($)</label>
                <input v-model="amount" class="inp-default h-8"/>

                <div class="mt-5 flex justify-end  max-sm:space-y-4 max-sm:flex-col-reverse">
                    <button @click="$emit('close')"
                            class="items-center bg-white border border-black text-black hover:bg-gray-700 hover:text-white max-sm:mt-4 rounded py-2 px-4 max-sm:text-sm max-sm:ml-1 max-sm:px-2">
                        Close
                    </button>
                    <button @click="handleContinue"
                            :disabled="loading || !amount"
                            class="btn-primary ml-2">
                        <spinner v-if="loading" class="mr-2"/>
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
