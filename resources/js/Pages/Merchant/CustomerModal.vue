<template>
    <div class="pointer-events-none fixed top-[57px] bottom-0 right-0 z-20 flex max-w-full pl-10">
        <div class="pointer-events-auto w-screen max-w-3xl">
            <div class="flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl pb-28">
                <div class="px-4 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title">
                            {{ form.id ? 'Update' : 'Create' }} a Customer
                        </h2>
                        <div class="ml-3 flex h-7 items-center">
                            <CloseButton v-on:click.prevent="close"/>
                        </div>
                    </div>
                </div>
                <div class="relative mt-6 flex-1 border-t border-gray-200">
                    <form @submit.prevent="onSubmit">
                        <div class="bg-white p-4">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left space-y-5">
                                <div class="space-y-1">
                                    <InputLabel for="name" value="Name"/>
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.name"/>
                                </div>
                                <div class="space-y-1">
                                    <InputLabel for="email" value="Email"/>
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        autocomplete="new-email"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.email"/>
                                </div>
                                <div class="space-y-1">
                                    <InputLabel for="phone" value="Phone"/>
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        class="w-full"
                                    />
                                    <InputError :message="form.errors.phone"/>
                                </div>
                                <label
                                    v-if="form.id"
                                    class="flex items-center cursor-pointer"
                                >
                                    <input
                                        id="reset-password-checkbox"
                                        type="checkbox"
                                        v-model="showPasswordFields"
                                        @change="handlePasswordFieldsToggle"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2"
                                    >
                                    <span class="ml-2 text-sm whitespace-nowrap">
                                        Reset Password
                                    </span>
                                </label>
                                <div v-if="showPasswordFields" class="space-y-4">
                                    <div class="space-y-1">
                                        <InputLabel for="password" value="New Password"/>
                                        <TextInput
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            class="w-full"
                                            autocomplete="new-password"
                                            :required="showPasswordFields"
                                        />
                                        <InputError :message="form.errors.password"/>
                                    </div>
                                    <div class="space-y-1">
                                        <InputLabel for="confirm_password" value="Confirm New Password"/>
                                        <TextInput
                                            id="confirm_password"
                                            v-model="form.confirm_password"
                                            type="password"
                                            class="w-full"
                                            :required="showPasswordFields"
                                        />
                                        <InputError :message="form.errors.confirm_password"/>
                                    </div>
                                    <label v-if="form.id" class="flex items-center cursor-pointer">
                                        <input
                                            id="send-password-email"
                                            type="checkbox"
                                            v-model="form.is_password_email_send"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2"
                                        >
                                        <span class="ml-2 text-sm whitespace-nowrap">
                                            Send email to the customer about the password update
                                        </span>
                                    </label>
                                </div>
                                <label
                                    v-if="!form.id"
                                    class="flex items-center cursor-pointer"
                                >
                                    <input
                                        id="send-creation-email"
                                        type="checkbox"
                                        v-model="form.is_email_send"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2"
                                    >
                                    <span class="ml-2 text-sm whitespace-nowrap">
                                        Send email to the customer about the customer creation
                                    </span>
                                </label>
                                <div class="!mt-10 flex w-full justify-between">
                                    <DangerButton
                                        type="button"
                                        v-on:click="close"
                                    >
                                        Cancel
                                    </DangerButton>
                                    <PrimaryButton
                                        type="submit"
                                        :class="{ 'opacity-25': form.processing }"
                                        :disabled="form.processing"
                                    >
                                        {{ form.id ? 'Save' : 'Create' }}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {useForm, usePage} from '@inertiajs/vue3'
import CloseButton from '@/Components/CloseButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import DangerButton from '@/Components/DangerButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

export default {
    name: 'CustomerModal',

    components: {
        CloseButton,
        InputLabel,
        TextInput,
        InputError,
        DangerButton,
        PrimaryButton,
    },

    props: {
        customer: {
            type: Object,
            default: () => ({})
        },
        close: {
            type: Function,
            required: true
        },
    },

    setup(props) {
        const customer = props.customer
        const page = usePage()
        const merchant = page.props.auth.user

        return {
            form: useForm({
                id: customer?.id || null,
                name: customer?.name || '',
                email: customer?.email || '',
                phone: customer?.phone || '',
                password: '',
                confirm_password: '',
                user_id: merchant.id,
                type: merchant.type,
                is_email_send: false,
                is_password_email_send: false,
            })
        }
    },

    data() {
        return {
            showPasswordFields: !this.form.id
        }
    },

    watch: {
        showPasswordFields(newVal) {
            if (!newVal) {
                this.resetPasswordFields()
            }
        }
    },

    methods: {
        handlePasswordFieldsToggle(e) {
            if (!e.target.checked) {
                this.resetPasswordFields()
            }
        },

        resetPasswordFields() {
            this.form.password = ''
            this.form.confirm_password = ''
            this.form.is_password_email_send = false
        },

        onSubmit() {
            if (!this.showPasswordFields) {
                this.resetPasswordFields()
            }

            this.form.post(route('merchant.customer.save'), {
                onSuccess: (page) => {
                    window.Toast.success({
                        message: 'Customer saved successfully.'
                    })
                    this.close()
                },
                onError: (errors) => {
                    window.Toast.error({
                        message: 'Error saving customer.'
                    })
                }
            })
        }
    }
}
</script>

<style scoped>
</style>
