<template>
    <form @submit.prevent="form.post(route('merchant.setting.profile'))" class="rounded-lg bg-white shadow max-w-xl mx-auto p-5 my-8 space-y-5">
        <div class="space-y-1">
            <InputLabel for="firstname" value="Firstname"/>
            <TextInput
                id="firstname"
                type="text"
                class="w-full"
                v-model="form.first_name"
                required
                placeholder="Fist Name"
            />
            <InputError :message="form.errors.first_name" />
        </div>
        <div class="space-y-1">
            <InputLabel for="lastname" value="Lastname"/>
            <TextInput
                id="lastname"
                type="text"
                class="w-full"
                v-model="form.last_name"
                required
                placeholder="Last Name"
            />
            <InputError :message="form.errors.last_name" />
        </div>
        <div class="space-y-1">
            <InputLabel for="email" value="Email"/>
            <TextInput
                id="email"
                type="text"
                class="w-full"
                v-model="form.email"
                required
                placeholder="Email"
            />
            <InputError class="mt-2" :message="form.errors.email" />
        </div>
        <div class="space-y-1">
            <InputLabel for="phone" value="Phone"/>
            <TextInput
                id="phone"
                type="text"
                class="w-full"
                v-model="form.phone"
                placeholder="Phone"
            />
            <InputError class="mt-2" :message="form.errors.phone" />
        </div>
        <div class="text-center">
            <PrimaryButton type="submit">Save</PrimaryButton>
        </div>
    </form>
</template>

<script>
import TextInput from "@/Components/TextInput.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
export default {
    name: "SettingProfileTab",
    components: {PrimaryButton, InputError, InputLabel, TextInput},
    setup(){
        const user = usePage().props.auth.user;
        return {
            form: useForm({
                first_name: user.first_name,
                last_name: user.last_name,
                email: user.email,
                phone: user.phone,
            })
        }
    }
}
</script>

<style scoped>

</style>
