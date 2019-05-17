<template>
    <MerchantLayout title="Products">
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="border rounded-lg divide-y divide-gray-200">
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 0">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Image</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Title</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Sizes</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Orders</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Created At</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                <tr v-for="product in productData">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                        <div class="w-16 h-16">
                                            <div class="border flex items-center justify-center w-full h-full">
                                                <img v-if="product.image" :src="product.image[0]" alt="placeholder"/>
                                                <span v-else>No Image</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.title }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.variants.length }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.orders_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $filters.formatDateTime(product.created_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                        <div class="flex gap-2">
                                            <button
                                                class="h-7 w-7 flex items-center justify-center rounded-full border border-primary text-primary hover:bg-primary hover:text-white"
                                                @click="handleView(product)"
                                            >
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button @click="openModal(product)" class="h-7 w-7 rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="productData.length === 0">
                                    <td colspan="9" class="px-6 py-4 whitespace-nowrap text-gray-400 h-40 text-center">
                                        No Products Found
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MerchantLayout>
</template>

<script>
import MerchantLayout from "@/Layouts/MerchantLayout.vue";
import SvgIcon from "@jamescoyle/vue-icon";

export default {
    components: {SvgIcon, MerchantLayout},
    props: {
        products: {
            type: [Array, null],
            required: true
        },
    },
    data() {
        return {
            modalOpen: false,
            product: null
        }
    },
    computed: {
        productData() {
            return this.products || []
        },
        user() {
            return this.$page.props.auth.user
        }
    },
    mounted() {
        if(!this.products) {
            window.Toast.error({
                title: 'Error',
                message: 'Failed to fetch products'
            })
        }
    },
    methods: {
        closeModal() {
            this.modalOpen = false
        },
        openModal(product) {
            this.product = product
        },
        handleView(product) {

        }
    }
}
</script>
