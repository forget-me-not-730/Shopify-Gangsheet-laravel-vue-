<template>
    <div class="pointer-events-none fixed top-[57px] bottom-0 right-0 z-20 flex max-w-full pl-10">
        <div class="pointer-events-auto w-[calc(100vw-257px)]">
            <div class="flex h-full flex-col bg-white py-6">
                <div class="px-4 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title"><span v-text="merchant?.id?'Update':'Create'"></span> Merchant</h2>
                        <div class="ml-3 flex h-7 items-center">
                            <CloseButton v-on:click.prevent="close"/>
                        </div>
                    </div>
                </div>
                <div class="relative mt-6 flex-1 border-t border-gray-200  overflow-y-auto">
                    <form @submit.prevent="form.put(route('admin.merchant.update'))">
                        <div class="bg-white p-4">
                            <div class="text-center sm:mt-0 sm:text-left grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <InputLabel for="first_name" value="First Name"/>
                                    <TextInput
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        class="w-full"
                                    />
                                    <InputError :message="form.errors.first_name"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="last_name" value="Last Name"/>
                                    <TextInput
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        class="w-full"
                                    />
                                    <InputError :message="form.errors.last_name"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="email" value="Email"/>
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="text"
                                        class="w-full"
                                    />
                                    <InputError :message="form.errors.email"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="phone" value="Phone"/>
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        class="w-full"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.phone"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="company_name" value="Company Name"/>
                                    <TextInput
                                        id="company_name"
                                        v-model="form.company_name"
                                        class="w-full"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.company_name"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="company_code" value="Company Code"/>
                                    <TextInput
                                        id="company_code"
                                        v-model="form.slug"
                                        class="w-full"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.slug"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="website" value="Website"/>
                                    <TextInput
                                        id="website"
                                        v-model="form.website"
                                        class="w-full"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.website"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="max_order" value="Max Order Price"/>
                                    <TextInput
                                        id="max_order"
                                        v-model="form.max_order"
                                        class="w-full"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.max_order"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="commission_rate" value="Commission Rate"/>
                                    <TextInput
                                        id="commission_rate"
                                        v-model="form.commission_rate"
                                        class="w-full"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.commission_rate"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="credits" value="Credits"/>
                                    <TextInput
                                        id="credits"
                                        v-model="form.credits"
                                        class="w-full"
                                        type="text"
                                    />
                                    <InputError :message="form.errors.credits"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="status" value="Status"/>
                                    <SelectBox v-model="form.status" :options="statuses"/>
                                    <InputError :message="form.errors.status"/>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="galleryShareWith" value="Gallery Share With"/>
                                    <TextInput
                                        id="galleryShareWith"
                                        v-model="form.galleryShareWith"
                                        class="w-full"
                                        type="text"
                                        placeholder="Enter Shop ID"
                                    />
                                    <InputError :message="form.errors.status"/>
                                </div>
                            </div>

                            <div class="flex w-full space-x-4 mt-5">
                                <DangerButton v-on:click="close()">
                                    Cancel
                                </DangerButton>
                                <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Submit
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import CloseButton from "@/Components/CloseButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import DangerButton from "@/Components/DangerButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useForm} from "@inertiajs/vue3";
import SelectBox from "@/Components/SelectBox.vue";

export default {
    name: "MerchantModal",
    components: {SelectBox, PrimaryButton, DangerButton, InputError, TextInput, InputLabel, CloseButton},
    props: {
        merchant: Object,
        close: Function
    },
    setup(props) {
        const merchant = props.merchant;
        return {
            form: useForm({
                id: merchant.id,
                first_name: merchant.first_name,
                last_name: merchant.last_name,
                email: merchant.email,
                phone: merchant.phone,
                company_name: merchant.company_name,
                slug: merchant.slug,
                website: merchant.website,
                max_order: merchant.max_order,
                commission_rate: merchant.commission_rate,
                credits: merchant.credits,
                status: merchant.status,
                galleryShareWith: merchant.settings?.galleryShareWith ?? '',
            })
        }
    },
    data() {
        return {
            outputs: [
                {
                    label: 'PNG',
                    value: 'png'
                },
                {
                    label: 'PDF',
                    value: 'pdf'
                }
            ],
            statuses: [
                {
                    label: 'Active',
                    value: 'active'
                },
                {
                    label: 'Inactive',
                    value: 'inactive'
                },
            ]
        }
    },
}
</script>

<style scoped>

</style>
