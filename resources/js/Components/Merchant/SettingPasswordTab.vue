<template>
    <form @submit.prevent="onSubmit" class="rounded-lg bg-white shadow max-w-xl mx-auto p-5 my-8 space-y-5">
        <div v-if="!$page.props.admin_id && $page.props.auth.user.type !== 'woo'" class="space-y-1">
            <InputLabel for="current_password" value="Current Password"/>
            <TextInput
                id="current_password"
                type="text"
                class="w-full"
                v-model="form.current_password"
                required
                placeholder="Current Password"
            />
            <InputError :message="form.errors.current_password"/>
        </div>
        <div class="space-y-1">
            <InputLabel for="new_password" value="New Password"/>
            <TextInput
                id="new_password"
                type="text"
                class="w-full"
                v-model="form.password"
                required
                placeholder="New Password"
            />
            <InputError :message="form.errors.password"/>
        </div>
        <div class="space-y-1">
            <InputLabel for="confirm_password" value="Confirm Password"/>
            <TextInput
                id="confirm_password"
                type="text"
                class="w-full"
                v-model="form.password_confirmation"
                required
                placeholder="Confirm Password"
            />
            <InputError :message="form.errors.password_confirmation"/>
        </div>
        <div class="text-center">
            <PrimaryButton type="submit">Save</PrimaryButton>
        </div>
    </form>
</template>

<script>
import TextInput from '@/Components/TextInput.vue'
import {useForm} from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Checkbox from '@/Components/Checkbox.vue'

export default {
    name: 'SettingPasswordTab',
    components: {Checkbox, PrimaryButton, InputError, InputLabel, TextInput},
    setup() {
        return {
            form: useForm({
                current_password: '',
                password: '',
                password_confirmation: '',
            })
        }
    },
    methods: {
        onSubmit() {
            this.form.post(route('merchant.setting.update-password'), {
                onSuccess: () => {
                    this.form.reset()
                    window.Toast.success({
                        message: 'Password updated successfully'
                    })
                }
            })
        }
    }
}

</script>

<style scoped>

</style>
