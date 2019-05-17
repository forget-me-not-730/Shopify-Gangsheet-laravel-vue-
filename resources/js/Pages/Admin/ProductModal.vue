<template>
    <div class="pointer-events-none fixed top-[57px] bottom-0 right-0 z-20 flex max-w-full pl-10">
        <div class="pointer-events-auto w-screen max-w-2xl">
            <div class="flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl">
                <div class="px-4 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title"><span v-text="product?.id?'Update':'Create'"></span> Product</h2>
                        <div class="ml-3 flex h-7 items-center">
                            <CloseButton v-on:click.prevent="close"/>
                        </div>
                    </div>
                </div>
                <div class="relative mt-6 flex-1 border-t border-gray-200">
                    <form @submit.prevent="onSubmit">
                        <div class="bg-white p-4">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left space-y-3">
                                <div class="space-y-1">
                                    <InputLabel for="title" value="Title" />
                                    <TextInput
                                        id="title"
                                        v-model="form.title"
                                        type="text"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.title" />
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="code" value="Code" />
                                    <TextInput
                                        id="code"
                                        v-model="form.slug"
                                        type="text"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.slug" />
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="redirect_url" value="Redirect URL" />
                                    <TextInput
                                        id="redirect_url"
                                        v-model="form.redirect_url"
                                        type="text"
                                        class="w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.redirect_url" />
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="description" value="Description" />
                                    <TextField
                                        id="description"
                                        v-model="form.description"
                                        type="text"
                                        class="w-full"
                                    />
                                    <InputError :message="form.errors.description" />
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="sizes" value="Sizes" />
                                    <div >
                                        <table class="w-full">
                                            <thead>
                                            <tr class="font-semibold">
                                                <td>Width</td>
                                                <td>Height</td>
                                                <td>Unit</td>
                                                <td>Price</td>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <template v-for="(size, index) in form.sizes">
                                                <tr v-if="!size.delete" >
                                                    <td>
                                                        <input type="number" step="0.01" v-model="size.width" class="w-full" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" v-model="size.height" class="w-full" required>
                                                    </td>
                                                    <td>
                                                        <select v-model="size.unit" class="w-24" required>
                                                            <option value="inch">inch</option>
                                                            <option value="mm">mm</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" v-model="size.price" class="w-full" required>
                                                    </td>
                                                    <td class="pl-2">
                                                        <button type="button" @click="deleteSize(index)" class="w-8 h-8 rounded-full border border-red-500 text-red-500 hover:text-white hover:bg-red-500">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                            </tbody>
                                        </table>
                                    </div>
                                    <InputError :message="form.errors.sizes" />
                                    <div class="text-right">
                                        <PrimaryButton type="button" @click="addSize">Add Size</PrimaryButton>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel for="status" value="Status" />
                                    <SelectBox v-model="form.status" :options="statuses" />
                                    <InputError :message="form.errors.status" />
                                </div>

                                <div class="!mt-10 flex w-full justify-between">

                                    <DangerButton v-on:click="close()">
                                        Cancel
                                    </DangerButton>
                                    <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        Submit
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
import CloseButton from "@/Components/CloseButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import DangerButton from "@/Components/DangerButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useForm} from "@inertiajs/vue3";
import SelectBox from "@/Components/SelectBox.vue";
import TextField from "@/Components/TextField.vue";
export default {
    name: "ProductModal",
    components: {TextField, SelectBox, PrimaryButton, DangerButton, InputError, TextInput, InputLabel, CloseButton},
    props: {
        user_id: [String, Number],
        product: Object,
        close: Function
    },
    setup(props){
        const product = props.product;
        const user_id = props.user_id;
        return {
            form: useForm({
                id: product?.id,
                user_id: user_id,
                title: product?.title,
                description: product?.description,
                slug: product?.slug,
                redirect_url: product?.redirect_url,
                sizes: product?.sizes ?? [],
                status: product?.deleted_at ? 'inactive' : 'active',
            })
        }
    },
    data(){
        return {
            statuses: [
                {
                    label: 'Active',
                    value: 'active'
                },
                {
                    label: 'Inactive',
                    value: 'inactive'
                },
            ]
        }
    },
    methods: {
        addSize(){
            this.form.sizes.push({})
        },
        deleteSize(index){
            if (this.form.sizes[index].id){
                this.form.sizes[index].delete = true
            }else{
                this.form.sizes.splice(index, 1)
            }
        },
        onSubmit(){
            this.form.post(route('admin.product.save'), {
                onSuccess: ()=>{
                    this.close()
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
