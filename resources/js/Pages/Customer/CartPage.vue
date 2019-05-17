<template>
    <BuilderLayout title="My Cart">
        <div class="w-full bg-builder">
            <div class="max-w-5xl mx-auto p-5">
                <div class="flex space-x-5 items-center">
                    <img :src="merchant.logo_url" alt="logo" class="md:h-12 h-8">
                    <h1 class="font-bold md:text-4xl text-3xl">{{ merchant.company_name }}</h1>
                </div>
                <hr class="my-3">
                <h1 class="text-center font-bold text-5xl my-10">Your Gang Sheets</h1>
                <div class="flex md:flex-row flex-col gap-4">
                    <div class="md:w-1/2 w-full">
                        <div class="rounded-lg border border-gray-300 w-full">
                            <div class="flex p-3 justify-between">
                                <div class="font-semibold">Shipping Address</div>
                            </div>
                            <hr>
                            <div class="space-y-2 px-3 py-6">
                                <div class="space-y-1">
                                    <label>Name<sup class="text-red-500">*</sup></label>
                                    <input type="text" v-model="form.name" class="w-full inp-builder" required/>
                                    <p v-if="form.errors.name" class="text-xs text-red-500">
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <label>Email<sup class="text-red-500">*</sup></label>
                                    <input type="email" v-model="form.email" class="w-full inp-builder" required/>
                                    <p v-if="form.errors.email" class="text-xs text-red-500">
                                        {{ form.errors.email }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <label>Phone</label>
                                    <input type="text" v-model="form.phone" class="w-full inp-builder"/>
                                    <p v-if="form.errors.phone" class="text-xs text-red-500">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>

                                <template v-if="collectShippingAddress">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="col-span-2">
                                            <label>Street</label>
                                            <input type="text" v-model="form.street" class="w-full inp-builder"/>
                                            <p v-if="form.errors.street" class="text-xs text-red-500">
                                                {{ form.errors.street }}
                                            </p>
                                        </div>

                                        <div>
                                            <label>Zipcode</label>
                                            <input type="text" v-model="form.zipcode" class="w-full inp-builder"/>
                                            <p v-if="form.errors.zipcode" class="text-xs text-red-500">
                                                {{ form.errors.zipcode }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <label>State</label>
                                            <input type="text" v-model="form.state" class="w-full inp-builder"/>
                                            <p v-if="form.errors.state" class="text-xs text-red-500">
                                                {{ form.errors.state }}
                                            </p>
                                        </div>
                                        <div class="space-y-1">
                                            <label>City</label>
                                            <input type="text" v-model="form.city" class="w-full inp-builder"/>
                                            <p v-if="form.errors.city" class="text-xs text-red-500">
                                                {{ form.errors.city }}
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                    <div class="md:w-1/2 w-full flex flex-col space-y-4">
                        <template v-if="items.length">
                            <div v-for="design in items" class="rounded-lg border border-gray-300 overflow-hidden flex items-center">
                                <div class="h-36 w-36 bg-gray-300 bg-opacity-50">
                                    <img :src="design.thumbnail_url + '?v=' + (new Date()).getTime()" class="object-contain h-full mx-auto" alt="">
                                </div>
                                <div class="flex flex-col justify-between h-full p-3 flex-1 space-y-2">
                                    <div class="flex items-start space-x-4">
                                        <div class="text-2xl font-bold flex-1 max-xs:text-sm">{{ design.product.title }}</div>
                                        <button @click="deleteDesign(design)" class="w-7 h-7 hover:bg-gray-300 rounded-full text-red-600 flex items-center justify-center cursor-pointer">
                                            <svg-icon type="mdi" :path="mdiDelete" size="18"/>
                                        </button>
                                    </div>
                                    <div class="flex justify-between flex-wrap">
                                        <div class="max-xs:text-xs text-base">{{ getSizeLabel(design) }}</div>
                                        <button class="btn-builder-outline" @click="$inertia.get(route('builder.design', design.id))">
                                            Edit Design
                                        </button>
                                    </div>
                                    <div v-if="design.product.art_board_type === 'rolling-gang-sheet'" class="flex justify-start">
                                        <div class="font-medium bg-gray-200 w-max px-2 mt-0.5 rounded">
                                            Actual Height: {{ getActualHeight(design, design.data.meta?.variant?.unit) }} {{ design.data.meta?.variant?.unit }}
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex space-x-4 items-center">
                                            <button @click="decrease(design)" class="w-7 h-7 btn-builder p-0">
                                                <svg-icon type="mdi" :path="mdiMinus" size="18"/>
                                            </button>
                                            <div class="text-lg">{{ design.quantity }}</div>
                                            <button @click="increase(design)" class="w-7 h-7 btn-builder p-0">
                                                <svg-icon type="mdi" :path="mdiPlus" size="18"/>
                                            </button>
                                        </div>
                                        <div>$ {{ getItemPrice(design) }}</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <h1 class="text-bold text-center text-2xl mb-10 mt-5">Your Cart is Empty</h1>
                        </template>
                        <div v-if="items.length" class="flex p-3 justify-between">
                            <div class="font-semibold">Subtotal</div>
                            <div class="font-semibold">$ {{ subtotal.toFixed(2) }}</div>
                        </div>
                        <hr>
                        <div class="p-3">
                            This is not an actual shopping cart.
                            This page displays all of the gang sheets you have built for this order.
                            You are being redirected to <b>{{ merchant.company_name }}</b>'s website.
                            Please add all items you have built to your cart on their website.
                            An email will be sent with a summary what you built.
                        </div>
                        <hr>
                        <div class="w-full text-center py-5 flex justify-between px-3 space-x-2">
                            <button @click.prevent="$inertia.get(route('builder.create', product.slug))" class="btn-builder-outline flex-1 justify-center">
                                Add Design
                            </button>
                            <button @click="checkout" class="btn-builder flex-1" :disabled="!items.length || form.processing || submitting">
                                <spinner v-if="form.processing || submitting" class="mr-2"/>
                                Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BuilderLayout>
</template>

<script>
import {Head, usePage} from '@inertiajs/vue3'
import SelectBox from '@/Components/SelectBox.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import BuilderLayout from '@/Layouts/BuilderLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Spinner from '@/Components/Spinner.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiMinus, mdiPlus, mdiDelete} from '@mdi/js'
import {convertDimension, getSizeLabel} from "@/Builder/Utils/helpers";

export default {
    name: 'CreateDesign',
    components: {Spinner, SecondaryButton, BuilderLayout, PrimaryButton, TextInput, InputError, InputLabel, SelectBox, Head, SvgIcon},
    props: {
        merchant: {
            type: Object,
            required: true
        },
        product: {
            type: Object,
            required: true
        },
        designs: {
            type: Array,
            default: []
        },
    },
    data() {
        const customer = this.$page.props.auth.customer

        return {
            submitting: false,
            form: {
                product_id: this.$props.product.id,
                customer_id: customer?.id,
                name: customer?.name,
                email: customer?.email,
                phone: customer?.phone,
                state: customer?.state,
                city: customer?.city,
                street: customer?.street,
                zipcode: customer?.zipcode,
                errors: {}
            },

            mdiMinus,
            mdiPlus,
            mdiDelete
        }
    },
    computed: {
        items() {
            return this.designs.filter((d) => !d.deleted)
        },
        subtotal() {
            let total = 0
            for (const design of this.items) {
                if (design.product.art_board_type === 'rolling-gang-sheet') {
                    const startHeight = this.$props.product.settings.startHeight
                    total += design.quantity * design.size.price / design.size.height * Math.max(this.getActualHeight(design, design.data.meta?.variant?.unit), startHeight)
                } else {
                    total += design.quantity * design.size.price
                }
            }
            return total
        },
        collectShippingAddress() {
            return this.merchant.settings?.collectShippingAddress ?? true
        }
    },
    methods: {
        getSizeLabel(design) {
            return getSizeLabel(design)
        },
        getItemPrice(design) {
            if (design.product.art_board_type === 'rolling-gang-sheet') {
                const startHeight = this.$props.product.settings.startHeight
                return (design.size.price / design.size.height * Math.max(this.getActualHeight(design, design.data.meta?.variant?.unit), startHeight) * design.quantity).toFixed(2)
            } else {
                return (design.size.price * design.quantity).toFixed(2)
            }
        },
        getActualHeight(design, unit = 'inch') {
            let actualHeight = design.data.actualHeight

            actualHeight = convertDimension(actualHeight, 'px', unit)

            return actualHeight;
        },
        async increase(design) {
            design.quantity++
            NProgress.start()
            await this.updateQuantity(design.id, design.quantity)
            NProgress.done()
        },
        async decrease(design) {
            if (design.quantity > 1) {
                design.quantity--
                NProgress.start()
                await this.updateQuantity(design.id, design.quantity)
                NProgress.done()
            }
        },
        deleteDesign(design) {
            axios.delete(route('builder.cart.delete', design.id)).then(() => {
                design.deleted = true
            }).catch(() => {
                Toast.error({
                    message: 'Failed to delete item'
                })
            })
        },
        updateQuantity(id, quantity) {
            return new Promise((resolve) => {
                axios.post(route('builder.cart.update-quantity'), {
                    id: id,
                    quantity: quantity,
                }).then(res => {
                    resolve(res.data)
                }).catch(() => {
                    Toast.error({
                        message: 'Failed to update quantity'
                    })
                    resolve(false)
                })
            })
        },
        checkout() {
            this.submitting = true
            this.form.errors = {}

            axios.post(route('builder.cart.checkout'), this.form).then(res => {
                if (res.data.success) {
                    window.location.href = res.data.redirect_url
                } else {
                    if (res.data.errors) {
                        for (const key in res.data.errors) {
                            this.form.errors[key] = res.data.errors[key][0]
                        }
                    }
                }
            }).catch(() => {
                Toast.error({
                    message: 'Something went to wrong. Please try again.'
                })
            }).finally(() => {
                this.submitting = false
            })
        }
    }
}
</script>

<style scoped>

</style>
