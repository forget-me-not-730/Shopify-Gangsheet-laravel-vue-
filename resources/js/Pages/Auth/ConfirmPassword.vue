<script setup>
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from "@/Layouts/AuthLayout.vue";

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Confirm Password" />

        <div class="uppercase py-1 leading-6 text-primary font-erica-one text-3xl mb-8">
            Confirm Password
        </div>
        <div class="mb-4 text-sm text-gray-600  max-w-md">
            This is a secure area of the application. Please confirm your password before continuing.
        </div>

        <form @submit.prevent="submit" class="max-w-[430px]">
            <div>
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="Password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="w-full mt-6">
                <button class="rounded-full w-full bg-white py-2 font-erica-one text-2xl uppercase text-primary shadow-xl" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Confirm
                </button>
            </div>
        </form>
    </AuthLayout>
</template>
