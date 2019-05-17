<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <AuthLayout>
        <Head title="Email Verification" />

        <div class="mb-4 text-sm text-gray-600 max-w-md">
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the link
            we just emailed to you? If you didn't receive the email, we will gladly send you another.
        </div>

        <div class="mb-4 font-medium text-sm text-green-600 max-w-md" v-if="verificationLinkSent">
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <form @submit.prevent="submit" class="max-w-[430px]">
            <div class="w-full mt-6">
                <button class="rounded-full w-full bg-white py-2 font-erica-one text-2xl uppercase text-primary shadow-xl" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Resend Verification Email
                </button>
            </div>
            <div class="flex mt-6 justify-center items-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-primary hover:text-primary"
                >Log Out</Link
                >
            </div>
        </form>
    </AuthLayout>
</template>
