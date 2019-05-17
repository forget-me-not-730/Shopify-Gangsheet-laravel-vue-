<template>
    <div class="pointer-events-none fixed top-[57px] bottom-0 right-0 z-20 flex max-w-full pl-10">
        <div class="pointer-events-auto w-screen max-w-4xl">
            <div class="flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl">
                <div class="px-4 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 class="text-base font-semibold leading-6 text-gray-900" id="slide-over-title">Order
                            Detail</h2>
                        <div class="ml-3 flex h-7 items-center">
                            <CloseButton v-on:click.prevent="close"/>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="form.put(route('merchant.order.update'))"
                      class="relative mt-6 flex-1 border-t border-gray-200 space-y-5 py-8 px-5">
                    <h1 class="text-gray-700 font-bold text-center text-2xl">{{ order.product_title }}</h1>
                    <table class="w-full divide-y divide-gray-200 text-sm">
                        <thead>
                        <tr>
                            <td class="py-2">Image</td>
                            <td>Size</td>
                            <td class="w-10">Qty</td>
                            <td>Price</td>
                            <td class="text-center">Status</td>
                            <td>Downloaded At</td>
                            <td class="text-right">Actions</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(design, index) in order.designs">
                            <td>
                                <div class="w-20 h-20 bg-gray-200">
                                    <img :src="design.thumbnail_url" class="w-full h-full object-contain" alt=""/>
                                </div>
                            </td>
                            <td>
                                <span>
                                    {{ getSizeLabel(design) }}
                                </span>
                                <div class="flex mt-2 w-52 text-sm py-0.5 px-1 bg-gray-100 rounded items-center text-blue-500" @click="copyCustomerEditLink(design.id)">
                                    <div class="px-0.5 w-5">
                                        <template v-if="editApprovingId === design.id">
                                            <spinner v-if="editApproving"/>
                                            <i v-else class="mdi mdi-check"></i>
                                        </template>
                                        <i v-else class="mdi mdi-content-copy cursor-pointer hover:text-indigo-500"></i>
                                    </div>
                                    <span v-if="editApprovingId === design.id && !editApproving">
                                        Copied
                                    </span>
                                    <span v-else>
                                        Copy customer edit link.
                                    </span>
                                </div>
                            </td>
                            <td>
                                {{ design.quantity }}
                            </td>
                            <td>
                                <span>
                                    $ {{ getSizePrice(order, index) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex w-full justify-center">
                                    <design-status :design="design" class="text-xs"/>
                                </div>
                            </td>
                            <td>
                               <span v-if="design.downloaded_at ">
                                   {{ moment(design.downloaded_at).format('M/D/YY H:mm:ss') }}
                               </span>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1 flex-wrap">
                                    <template v-if="design.status === 'completed'">
                                        <button @click="handleDownloadClick(design)" type="button"
                                                class="w-8 h-8 flex items-center justify-center border border-primary rounded-full text-primary hover:text-white hover:bg-primary ">
                                            <i class="mdi mdi-download"/>
                                        </button>
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
                    <div class="grid grid-cols-2 gap-4">
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
                        <div class="space-y-1">
                            <InputLabel for="state" value="State"/>
                            <TextInput type="text" v-model="form.state" id="state" class="w-full"/>
                            <InputError :message="form.errors.state"/>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="city" value="City"/>
                            <TextInput type="text" v-model="form.city" id="city" class="w-full"/>
                            <InputError :message="form.errors.city"/>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="street" value="Street"/>
                            <TextInput type="text" v-model="form.street" id="street" class="w-full"/>
                            <InputError :message="form.errors.street"/>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="zipcode" value="Zipcode"/>
                            <TextInput type="text" v-model="form.zipcode" id="zipcode" class="w-full"/>
                            <InputError :message="form.errors.zipcode"/>
                        </div>
                    </div>
                    <div class="!mt-10 flex w-full justify-between">
                        <DangerButton v-on:click="close()">
                            Cancel
                        </DangerButton>
                        <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }"
                                       :disabled="form.processing">
                            Submit
                        </PrimaryButton>
                    </div>
                </form>
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
import Spinner from "@/Components/Spinner.vue";
import DesignStatus from "@/Components/DesignStatus.vue";
import {getSizeLabel} from "@/Builder/Utils/helpers";
import moment from 'moment'

export default {
    name: "OrderModal",
    computed: {
        moment() {
            return moment
        }
    },
    components: {DesignStatus, Spinner, TextField, SelectBox, PrimaryButton, DangerButton, InputError, TextInput, InputLabel, CloseButton},
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
                state: order.state,
                city: order.city,
                street: order.street,
                zipcode: order.zipcode,
            })
        }
    },
    data() {
        return {
            editApprovingId: null,
            editApproving: null,
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
                {
                    label: 'Archived',
                    value: 'archived'
                },
            ]
        }
    },
    methods: {
        copyCustomerEditLink(design_id) {
            this.editApprovingId = design_id
            this.editApproving = true
            axios.post(route('builder.approve-edit-request', design_id)).then((res) => {
                navigator.clipboard.writeText(res.data.edit_url).then(() => {
                    this.editApproving = false
                    console.log('Copied')
                });
                setTimeout(() => {
                    this.editApprovingId = null
                }, 1500)
            })
        },
        onSubmit() {
            this.form.post(route('merchant.product.save'), {
                onSuccess: () => {
                    this.close()
                }
            })
        },
        handleDownloadClick(design) {
            design.downloaded_at = new Date()
            window.open(route('merchant.order.download', design.id), '_blank')
        },
        getSizeLabel(design) {
            return getSizeLabel(design)
        },
        getSizePrice(order, index) {
            const design = order.designs[index];
            const order_item = order.data?.items[index];
            const variant = design.data?.meta?.variant;

            if (order_item?.item_data?.total) {
                return parseFloat(order_item.item_data.total).toFixed(2)
            }

            if (variant.price) {
                return parseFloat(design.data?.meta?.variant.price).toFixed(2)
            }

            if (design.size?.price) {
                return parseFloat(design.size.price).toFixed(2)
            }

            return 0
        }
    }
}
</script>

<style scoped>

</style>
