<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col bg-builder border max-w-lg mx-auto sm:rounded-lg max-h-full">
            <div class="flex justify-between relative px-4 py-2">
                <h1 class="text-xl font-bold">
                    <span v-if="formType === 'login'">{{ $t('Login') }}</span>
                    <span v-else-if="formType === 'register'">{{ $t('Register') }}</span>
                    <span v-else>{{ $t('Forgot Password') }}</span>
                </h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon />
                </div>
            </div>
            <hr />
            <form v-if="formType === 'login'" @submit.prevent="handleLogin"
                class="space-y-2 w-full text-start py-3 max-w-md mx-auto my-10 p-5">
                <div class="bg-cyan-300 bg-opacity-50 flex items-center p-1 text-sm rounded">
                    <information-outline-icon size="18" class="mr-1" />
                    <p>
                        {{ $t('Login to view images/designs of your past orders.') }}
                    </p>
                </div>
                <div class="space-y-1">
                    <label>Email</label>
                    <input id="email" v-model="loginForm.email" type="email" class="inp-builder w-full" />
                    <p v-if="loginForm.errors.email" class="text-xs text-red-500">
                        {{ loginForm.errors.email }}
                    </p>
                </div>
                <div class="space-y-1">
                    <label>Password</label>
                    <input id="password" v-model="loginForm.password" type="password" class="inp-builder w-full" />
                    <p v-if="loginForm.errors.password" class="text-xs text-red-500">
                        {{ loginForm.errors.password }}
                    </p>
                </div>
                <div class="text-right">
                    <button type="button" @click="formType = 'forgot'" class="text-sm text-primary hover:underline">
                        {{ $t('Forgot Password?') }}
                    </button>
                </div>
                <div class="flex justify-center space-x-5 !mt-7">
                    <button type="button" @click="formType = 'register'"
                        class="underline text-inherit uppercase text-sm hover:text-primary">Register</button>
                    <button class="btn-builder w-32" :disabled="loginForm.processing">
                        <spinner v-if="loginForm.processing" class="w-5 h-5" />
                        <span v-else>Login</span>
                    </button>
                </div>
            </form>
            <form v-else-if="formType === 'register'" @submit.prevent="handleRegister"
                class="space-y-2 w-full text-start py-3 max-w-md mx-auto my-5 p-5">
                <h1 class="text-center text-inherit text-xl font-bold">Create your account.</h1>
                <div class="space-y-1">
                    <label>{{ $t('Name') }}</label>
                    <input v-model="registerForm.name" type="text" class="inp-builder w-full" />
                    <p v-if="registerForm.errors.name" class="text-xs text-red-500">
                        {{ registerForm.errors.name }}
                    </p>
                </div>
                <div class="space-y-1">
                    <label>{{ $t('Email') }}</label>
                    <input v-model="registerForm.email" type="email" class="inp-builder w-full" />
                    <p v-if="registerForm.errors.email" class="text-xs text-red-500">
                        {{ registerForm.errors.email }}
                    </p>
                </div>
                <div class="space-y-1">
                    <label>{{ $t('Password') }}</label>
                    <input v-model="registerForm.password" type="password" class="inp-builder w-full" />
                    <p v-if="registerForm.errors.password" class="text-xs text-red-500">
                        {{ registerForm.errors.password }}
                    </p>
                </div>
                <div class="space-y-1">
                    <label>{{ $t('Confirm Password') }}</label>
                    <input v-model="registerForm.password_confirmation" type="password" class="inp-builder w-full" />
                    <p v-if="registerForm.errors.password_confirmation" class="text-xs text-red-500">
                        {{ registerForm.errors.password_confirmation }}
                    </p>
                </div>
                <div class="flex justify-center space-x-5 !mt-10">
                    <button type="button" @click="formType = 'login'" 
                        class="underline text-inherit uppercase text-sm hover:text-primary">
                        {{ $t('Login') }}
                    </button>
                    <button class="btn-builder w-32" :disabled="registerForm.processing">
                        <spinner v-if="registerForm.processing" class="w-5 h-5" />
                        <span v-else>{{ $t('Register') }}</span>
                    </button>
                </div>
            </form>
            <form v-else @submit.prevent="handleForgotPassword"
                class="space-y-2 w-full text-start py-3 max-w-md mx-auto my-10 p-5">
                <div class="bg-cyan-300 bg-opacity-50 flex items-center p-1 text-sm rounded mb-4">
                    <information-outline-icon size="18" class="mr-1" />
                    <p>
                        {{ $t('Enter the email address associated with your account and we will send you a link to reset your password.') }}
                    </p>
                </div>
                <p v-if="successMessage" class="text-green-500">{{ successMessage }}</p>
                <div class="space-y-1">
                    <label>{{ $t('Email') }}</label>
                    <input v-model="forgotForm.email" type="email" class="inp-builder w-full" />
                    <p v-if="forgotForm.errors.email" class="text-xs text-red-500">
                        {{ forgotForm.errors.email }}
                    </p>
                </div>
                <div class="flex justify-center space-x-5 !mt-10">
                    <button type="button" @click="formType = 'login'" 
                        class="underline text-inherit uppercase text-sm hover:text-primary">
                        {{ $t('Return to Login') }}
                    </button>
                    <button class="btn-builder w-32" :disabled="forgotForm.processing">
                        <spinner v-if="forgotForm.processing" class="w-5 h-5" />
                        <span v-else>{{ $t('Reset') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </modal>
</template>

<script>
import { useForm } from '@inertiajs/vue3'
import Spinner from '@/Components/Spinner.vue'
import builderMixin from '@/Builder/Mixins/builderMixin'
import Modal from '@/Builder/Modals/Modal.vue'
import eventBus from '@/Builder/Utils/eventBus.js'
import CloseIcon from "@/Builder/Icons/CloseIcon.vue"
import InformationOutlineIcon from "@/Builder/Icons/InformationOutlineIcon.vue"

export default {
    name: 'CustomerLoginModal',
    components: { InformationOutlineIcon, CloseIcon, Modal, Spinner },
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    setup() {
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
            }),
            forgotForm: useForm({
                email: ''
            })
        }
    },
    data() {
        return {
            formType: 'login',
            loading: false,
            successMessage: ''
        }
    },
    methods: {
        handleLogin() {
            this.loginForm.post(route('builder.login'), {
                onSuccess: () => {
                    eventBus.$emit(eventBus.CUSTOMER_LOGIN_SUCCESS)
                }
            })
        },
        handleRegister() {
            this.registerForm.user_id = this.shop.id
            this.registerForm.post(route('builder.register'), {
                onSuccess: () => {
                    eventBus.$emit(eventBus.CUSTOMER_LOGIN_SUCCESS)
                }
            })
        },
        handleForgotPassword() {
            this.forgotForm.post(route('builder.forgot-password'), {
                onSuccess: () => {
                    this.successMessage = this.$t('We have emailed your password reset link.')
                }
            })
        },
        resetMessages() {
            this.successMessage = ''
        }
    },
    watch: {
        formType() {
            this.resetMessages()
        }
    }
}
</script>

<style scoped>
</style>