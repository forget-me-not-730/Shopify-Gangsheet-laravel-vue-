<script setup>
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout>
        <Head title="Forgot Password" />

        <div class="uppercase py-1 leading-6 text-primary font-erica-one text-3xl mb-8">
            Reset your password
        </div>

        <div class="mb-4 text-sm text-gray-600 max-w-md">
            Enter the email address associated with your account and we’ll send you a link to reset your  password.
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="max-w-md">
            <div>
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Email address"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="w-full mt-6">
                <button class="rounded-full w-full bg-white py-2 font-erica-one text-2xl uppercase text-primary shadow-xl" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Reset
                </button>
            </div>
            <div class="flex mt-6 justify-center items-center">
                <Link
                    :href="route('login')"
                    class="text-sm text-primary hover:text-primary ml-2"
                >
                    Return to Login
                </Link>
            </div>

        </form>
    </AuthLayout>
</template>
