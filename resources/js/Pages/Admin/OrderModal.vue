<template>
    <div class="pointer-events-none fixed top-[57px] bottom-0 right-0 z-20 flex max-w-full pl-10">
        <div class="pointer-events-auto w-screen max-w-3xl">
            <div class="flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl">
                <div class="px-4 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title">
                            Order Detail (#{{ order.id }})
                        </h2>
                        <div class="ml-3 flex h-7 items-center">
                            <CloseButton v-on:click.prevent="close"/>
                        </div>
                    </div>
                </div>
                <div class="relative mt-6 flex-1 border-t border-gray-200 space-y-5 py-8 px-5">
                    <h1 class="text-gray-700 font-bold text-center text-2xl">{{ order.product_title }}</h1>
                    <table class="w-full divide-y divide-gray-200">
                        <thead>
                        <tr>
                            <td class="py-2">Image</td>
                            <td>Size</td>
                            <td>Quantity</td>
                            <td>Price</td>
                            <td>Status</td>
                            <td>Actions</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="design in order.designs">
                            <td>
                                <div class="w-20 h-20 bg-gray-200">
                                    <img :src="design.thumbnail_url" class="w-full h-full object-contain" alt=""/>
                                </div>
                            </td>
                            <td>
                                <span v-if="design.data?.meta?.variant.title || design.data?.meta?.variant.label">
                                    {{ design.data?.meta?.variant.title || design.data?.meta?.variant.label }}
                                </span>
                                <span v-else>
                                    {{ design.size.label }}
                                </span>
                            </td>
                            <td>
                                {{ design.quantity }}
                            </td>
                            <td>
                                <span v-if="design.data?.meta?.variant.price">
                                $ {{ parseFloat(design.data?.meta?.variant.price).toFixed(2) }}
                                </span>
                                <span v-else>
                                $ {{ parseFloat(design.size.price).toFixed(2) }}
                                </span>
                            </td>
                            <td>
                                <design-status :design="design" class="text-xs"/>
                            </td>
                            <td>
                                <div class="flex gap-2 flex-wrap">
                                    <template v-if="design.status === 'completed'">
                                        <a :href="route('merchant.order.download', design.id)" type="button"
                                           class="w-8 h-8 flex items-center justify-center border border-primary rounded-full text-primary hover:text-white hover:bg-primary ">
                                            <i class="mdi mdi-download"/>
                                        </a>
                                    </template>
                                    <a :href="design.watermark_url" target="_blank"
                                       class="w-8 h-8 flex items-center justify-center border border-primary rounded-full text-primary hover:text-white hover:bg-primary">
                                        <i class="mdi mdi-eye"/>
                                    </a>
                                    <a :href="$route('merchant.order.design.edit', design.id)" target="_blank"
                                       class="w-8 h-8 flex items-center justify-center border border-primary rounded-full text-primary hover:text-white hover:bg-primary">
                                        <i class="mdi mdi-pencil"/>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="space-y-2">
                        <div class="space-y-1">
                            <InputLabel for="name" value="Name"/>
                            <TextInput type="text" v-model="form.name" id="name" class="w-full"/>
                            <InputError :message="form.errors.name"/>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="email" value="Email"/>
                            <TextInput type="email" v-model="form.email" id="email" class="w-full"/>
                            <InputError :message="form.errors.email"/>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="phone" value="Phone"/>
                            <TextInput type="text" v-model="form.phone" id="phone" class="w-full"/>
                            <InputError :message="form.errors.phone"/>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="status" value="Status"/>
                            <SelectBox :options="statuses" v-model="form.status"/>
                            <InputError :message="form.errors.status"/>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <a :href="$route('admin.merchant.impersonate', order.user_id)" target="_blank"
                           class="px-2 h-8 flex items-center justify-center border border-primary rounded text-primary hover:text-white hover:bg-primary">
                            Login to Merchant
                        </a>
                    </div>
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
import DesignStatus from "@/Components/DesignStatus.vue";

export default {
    name: "OrderModal",
    components: {
        DesignStatus,
        TextField,
        SelectBox,
        PrimaryButton,
        DangerButton,
        InputError,
        TextInput,
        InputLabel,
        CloseButton
    },
    props: {
        order: Object,
        close: Function
    },
    setup(props) {
        const order = props.order;
        return {
            form: useForm({
                id: order.id,
                name: order.name,
                email: order.email,
                phone: order.phone,
                status: order.status,
            })
        }
    },
    data() {
        return {
            statuses: [
                {
                    label: 'Created',
                    value: 'created'
                },
                {
                    label: 'Paid',
                    value: 'paid'
                },
                {
                    label: 'In Progress',
                    value: 'in-progress'
                },
                {
                    label: 'Completed',
                    value: 'completed'
                },
            ]
        }
    },
    methods: {
        addSize() {
            this.form.sizes.push({})
        },
        deleteSize(index) {
            if (this.form.sizes[index].id) {
                this.form.sizes[index].delete = true
            } else {
                this.form.sizes.splice(index, 1)
            }
        },
        onSubmit() {
            this.form.post(route('merchant.product.save'), {
                onSuccess: () => {
                    this.close()
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
