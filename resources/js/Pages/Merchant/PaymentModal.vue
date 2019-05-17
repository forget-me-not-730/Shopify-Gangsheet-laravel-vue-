<template>
    <Modal :show=true>
        <div class="rounded-2xl bg-white w-full max-w-lg mx-auto overflow-hidden border">
            <div class="pointer-events-auto  max-w-2xl">
                <div class="flex h-full flex-col overflow-y-auto bg-white shadow-xl">
                    <div class="p-4 sm:px-6 bg-gray-100">
                        <div class="flex items-start justify-between">
                            <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title">Add Credits</h2>
                            <div class="ml-3 flex h-7 items-center">
                                <CloseButton v-on:click.prevent="$emit('close')"/>
                            </div>
                        </div>
                    </div>
                    <div class="relative  flex-1 border-t border-gray-200">
                        <form @submit.prevent="onSubmit">
                            <div class="bg-white p-4">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left space-y-3">
                                    <div class="space-y-1">
                                        <InputLabel for="amount" value="Amount" />
                                        <TextInput
                                            id="amount"
                                            v-model="form.amount"
                                            type="number"
                                            class="w-full"
                                            :min="1"
                                            required
                                        />
                                        <InputError :message="form.errors.amount" />
                                    </div>

                                    <div class="flex">
                                        <input
                                            id="checkbox"
                                            v-model="form.auto_charge_enabled"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mr-2"
                                        />
                                        <div class="space-y-2">
                                            <div>
                                                <InputLabel for="checkbox" value="Auto Charge Credits" />
                                                <p class="text-sm text-gray-500">
                                                    Enable this option to automatically charge credits to your account when your balance falls below a certain threshold.
                                                </p>
                                            </div>
                                            <div v-if="form.auto_charge_enabled" class="space-y-1">
                                                <InputLabel for="auto_min_credits" value="Minimum Credit"/>
                                                <TextInput
                                                    id="auto_min_credits"
                                                    type="number"
                                                    class="w-full"
                                                    v-model="form.auto_min_credits"
                                                    required
                                                    placeholder="Credit Amount Limit"
                                                />
                                                <InputError :message="form.errors.auto_min_credits"/>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.auto_charge_enabled" />
                                    </div>

                                    <div class="!mt-10 flex w-full justify-between">
                                        <DangerButton v-on:click.prevent="$emit('close')">
                                            Cancel
                                        </DangerButton>
                                        <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                            Add Credits
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script>
import CloseButton from "@/Components/CloseButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import DangerButton from "@/Components/DangerButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useForm} from "@inertiajs/vue3";
import TextField from "@/Components/TextField.vue";
import Modal from '@/Builder/Modals/Modal.vue'

export default {
    name: "PaymentModal",
    components: {TextField, PrimaryButton, DangerButton, Modal, InputError, TextInput, InputLabel, CloseButton},
    setup(){
        return {
            form: useForm({
                amount: 5,
                auto_charge_enabled: false,
                auto_min_credits : 2,
            })
        }
    },
    mounted() {
        window.axios.get(route('merchant.setting.credit')).then(res => {
            if (res.data.success){
                const {auto_charge_enabled, auto_min_credits} = res.data
                if (auto_charge_enabled)
                    this.form.auto_charge_enabled = true
                this.form.auto_min_credits = auto_min_credits
            }
        })
    },
    methods: {
        onSubmit(){
            this.form.post(route('merchant.payment.add'), {
                onSuccess: ()=>{
                    this.close()
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
