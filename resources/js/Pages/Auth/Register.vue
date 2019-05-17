<script setup>
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";
import Checkbox from "@/Components/Checkbox.vue";
import { useReCaptcha } from "vue-recaptcha-v3";
const { executeRecaptcha, recaptchaLoaded } = useReCaptcha()

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    accept: false,
    captcha_token: null
});

const submit = async() => {
    await recaptchaLoaded()
    form.captcha_token = await executeRecaptcha('login')
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Register" />

        <div class="uppercase py-1 leading-6 text-primary font-erica-one text-3xl mb-8">
            Signup
        </div>

        <form @submit.prevent="submit" class="max-w-[430px] space-y-4">
            <div>
                <TextInput
                    id="first-name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.first_name"
                    required
                    placeholder="First Name"
                />

                <InputError class="mt-2" :message="form.errors.first_name" />
            </div>

            <div>
                <TextInput
                    id="last-name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.last_name"
                    required
                    placeholder="Last Name"
                />

                <InputError class="mt-2" :message="form.errors.last_name" />
            </div>

            <div>
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="email"
                    placeholder="Email address"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm Password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="w-full mt-6">
                <button class="rounded-full w-full bg-white py-2 font-erica-one text-2xl uppercase text-primary shadow" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Sign Up
                </button>
                <InputError class="mt-2" :message="form.errors.captcha_token" />
            </div>

            <div class="flex mt-6 justify-center items-center">
                Already have an account?
                <Link
                    :href="route('login')"
                    class="text-sm text-primary hover:text-primary ml-2"
                >
                    Login
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
