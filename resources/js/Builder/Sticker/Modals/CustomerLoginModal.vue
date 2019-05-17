<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col bg-builder border max-w-lg mx-auto sm:rounded max-h-full">
            <div class="flex justify-between relative px-4 pt-2">
                <h1 class="text-xl font-bold mb-3">
                    <span v-if="formType === 'login'">{{ $t('Login') }}</span>
                    <span v-else>{{ $t('Register') }}</span>
                </h1>
                <div class="absolute right-[14px] top-[3px] text-3xl cursor-pointer" @click="$emit('close')">
                    &times;
                </div>
            </div>
            <hr/>
            <form v-if="formType === 'login'" @submit.prevent="handleLogin" class="space-y-2 w-full text-start py-3 max-w-md mx-auto rounded border my-10 p-5">
                <h1 class="text-center text-inherit text-lg font-bold mb-5">{{ $t('Login to view images/designs of your past orders.') }}</h1>
                <div class="space-y-1">
                    <InputLabel for="email" value="Email"/>
                    <TextInput id="email" v-model="loginForm.email" type="email" class="w-full"/>
                    <InputError :message="loginForm.errors.email"/>
                </div>
                <div class="space-y-1">
                    <InputLabel for="password" value="Password"/>
                    <TextInput id="password" v-model="loginForm.password" type="password" class="w-full"/>
                    <InputError :message="loginForm.errors.password"/>
                </div>
                <div class="flex justify-center space-x-5 !mt-10">
                    <button type="button" @click="formType='register'" class="underline text-inherit uppercase text-sm">Register</button>
                    <PrimaryButton class="!px-12">Login</PrimaryButton>
                </div>
            </form>
            <form v-else @submit.prevent="handleRegister" class="space-y-2 w-full text-start py-3 max-w-md mx-auto rounded border my-5 p-5">
                <h1 class="text-center text-inherit text-lg font-bold">Create your account.</h1>
                <div class="space-y-1">
                    <InputLabel for="name" :value="$t('Name')"/>
                    <TextInput id="name" v-model="registerForm.name" type="text" class="w-full"/>
                    <InputError :message="registerForm.errors.name"/>
                </div>
                <div class="space-y-1">
                    <InputLabel for="email" :value="$t('Email')"/>
                    <TextInput id="email" v-model="registerForm.email" type="email" class="w-full"/>
                    <InputError :message="registerForm.errors.email"/>
                </div>
                <div class="space-y-1">
                    <InputLabel for="password" :value="$t('Password')"/>
                    <TextInput id="password" v-model="registerForm.password" type="password" class="w-full"/>
                    <InputError :message="registerForm.errors.password"/>
                </div>
                <div class="space-y-1">
                    <InputLabel for="password" :value="$t('Confirm Password')"/>
                    <TextInput id="password" v-model="registerForm.password_confirmation" type="password" class="w-full"/>
                    <InputError :message="registerForm.errors.password_confirmation"/>
                </div>
                <div class="flex justify-center space-x-5 !mt-10">
                    <button type="button" @click="formType='login'" class="underline text-inherit uppercase text-sm">{{ $t('Login') }}</button>
                    <PrimaryButton class="!px-12">{{ $t('Register') }}</PrimaryButton>
                </div>
            </form>
        </div>
    </modal>
</template>

<script>
import { useForm } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Spinner from '@/Components/Spinner.vue'
import builderMixin from '@/Builder/Mixins/builderMixin'
import Modal from '@/Builder/Modals/Modal.vue'
import eventBus from '@/Builder/Utils/eventBus.js'

export default {
    name: 'CustomerLoginModal',
    components: { Modal, Spinner, SecondaryButton, PrimaryButton, InputError, TextInput, InputLabel },
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    setup () {
        return {
            loginForm: useForm({
                email: '',
                password: ''
            }),
            registerForm: useForm({
                user_id: '',
                name: '',
                email: '',
                phone: '',
                password: '',
                password_confirmation: '',
            })
        }
    },
    data () {
        return {
            formType: 'login',
            loading: false
        }
    },
    methods: {
        handleLogin () {
            this.loginForm.post(route('builder.login'), {
                onSuccess: () => {
                    eventBus.$emit(eventBus.CUSTOMER_LOGIN_SUCCESS)
                }
            })
        },
        handleRegister () {
            this.registerForm.user_id = this.shop.id
            this.registerForm.post(route('builder.register'), {
                onSuccess: () => {
                    eventBus.$emit(eventBus.CUSTOMER_LOGIN_SUCCESS)
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
