<template>
    <form @submit.prevent="handleSubmit" class="max-w-xl mx-auto my-5">
        <div class="rounded-lg bg-white shadow p-5 space-y-4">
            <div class="space-y-1">
                <InputLabel for="company-name" value="Company Name"/>
                <TextInput
                    id="company-name"
                    type="text"
                    class="w-full"
                    v-model="form.company_name"
                    required
                    @input="onChangeCompanyName"
                    placeholder="Company Name"
                />
                <InputError :message="form.errors.company_name"/>
            </div>
            <div class="space-y-1">
                <InputLabel for="slug" value="Company Id"/>
                <TextInput
                    id="slug"
                    type="text"
                    class="w-full"
                    v-model="form.slug"
                    :disabled="$page.props.auth.user.type === 'woo'"
                    required
                    placeholder="Company ID"
                    pattern="[a-z1-9\-]*"
                />
                <InputError :message="form.errors.slug"/>
            </div>
            <div class="space-y-1">
                <InputLabel for="logo" value="Logo"/>
                <input ref="file" type="file" @change="onChangeFile" class="hidden">
                <div>
                    <img
                        v-if="form.logo || form.logo_url"
                        :src="form.logo ?? form.logo_url"
                        @click="$refs.file.click()"
                        class="h-28 object-contain border-2 border-dashed border-primary cursor-pointer"
                        alt="logo"
                    >
                    <div
                        v-else
                        class="h-28  border-2 border-dashed border-primary flex flex-col cursor-pointer"
                        @click="$refs.file.click()"
                    >
                        <div class="m-auto">Click to upload image</div>
                    </div>
                </div>
                <InputError :message="form.errors.logo"/>
            </div>
            <div class="space-y-1">
                <InputLabel for="website" value="Website"/>
                <TextInput
                    id="website"
                    type="text"
                    class="w-full"
                    v-model="form.website"
                    placeholder="Company Website"
                />
                <InputError :message="form.errors.website"/>
            </div>
            <div class="space-y-1">
                <InputLabel for="max_order" value="Max Fee per Order"/>
                <TextInput
                    id="max_order"
                    type="text"
                    class="w-full"
                    v-model="form.max_order"
                    readonly
                    placeholder="Max fee per order"
                />
                <InputError :message="form.errors.website"/>
            </div>
            <div class="space-y-1">
                <InputLabel for="commission_rate" value="Commission Rate : per 1 in x 1 in"/>
                <TextInput
                    id="commission_rate"
                    type="text"
                    class="w-full"
                    v-model="form.commission_rate"
                    readonly
                    placeholder="Max fee per order"
                />
                <InputError :message="form.errors.commission_rate"/>
            </div>
            <div class="space-y-1 flex justify-between items-center">
                <InputLabel for="auto_charge_credits" value="Auto Charge Credits"/>
                <toggle v-model="auto_charge_enabled"/>
            </div>
            <div v-if="auto_charge_enabled" class="space-y-1">
                <InputLabel for="auto_min_credits" value="Minimum Credit"/>
                <TextInput
                    id="auto_min_credits"
                    type="text"
                    class="w-full"
                    v-model="auto_min_credits"
                    required
                    placeholder="Credit Amount Limit"
                />
                <InputError :message="form.errors.auto_min_credits"/>
            </div>
            <div v-if="auto_charge_enabled" class="space-y-1">
                <InputLabel for="auto_charge_amount" value="Default Charge Credits"/>
                <TextInput
                    id="auto_charge_amount"
                    type="text"
                    class="w-full"
                    v-model="auto_charge_amount"
                    required
                    placeholder="Auto Charge Amount"
                />
                <InputError :message="form.errors.auto_charge_amount"/>
            </div>

            <div class="text-center">
                <PrimaryButton type="submit" :disabled="form.processing">
                    Save
                </PrimaryButton>
            </div>
        </div>
    </form>
</template>

<script>
import TextInput from '@/Components/TextInput.vue'
import {useForm, usePage} from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Spinner from '@/Components/Spinner.vue'
import Toggle from "@/Components/Toggle.vue";

export default {
    name: 'SettingCompanyTab',
    components: {Spinner, PrimaryButton, InputError, InputLabel, TextInput, Toggle},
    data() {
        const user = this.$page.props.auth.user

        return {
            user,
            auto_min_credits: 2,
            auto_charge_amount: 20,
            auto_charge_enabled: false,
            form: useForm({
                company_name: user.company_name,
                slug: user.slug,
                website: user.website,
                logo: null,
                logo_url: user.logo_url,
                max_order: user.max_order,
                commission_rate: user.commission_rate,
            })
        }
    },
    mounted() {
        this.getCreditsInfo()
    },
    methods: {
        getCreditsInfo(){
            window.axios.get(route('merchant.setting.credit')).then(res => {
                if (res.data.success){
                    this.auto_charge_amount = res.data.auto_charge_amount
                    this.auto_min_credits = res.data.auto_min_credits
                    this.auto_charge_enabled = res.data.auto_charge_enabled
                }
            })
        },
        onChangeFile(e) {
            const files = e.target.files
            if (files.length) {
                const file = files[0]
                const reader = new FileReader()
                reader.onload = () => {
                    this.form.logo = reader.result
                }
                reader.readAsDataURL(file)
            }
        },
        onChangeCompanyName(e) {
            if (this.user.type !== 'woo') {
                this.form.slug = e.target.value.toLowerCase().replaceAll(' ', '-')
            }
        },
        handleSubmit() {
            const payload = {
                auto_charge_amount: this.auto_charge_amount,
                auto_min_credits: this.auto_min_credits,
                auto_charge_enabled: this.auto_charge_enabled
            };
            window.axios.post(route('merchant.setting.credit'), payload).then(res => {
            })
            this.form.post(route('merchant.setting.company'), {
                onSuccess: () => {
                    window.Toast.success({
                        message: 'Successfully updated.'
                    })
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
