<template>
    <MerchantLayout title="Products">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Products
                </h2>
                <PrimaryButton @click="handleProductDetail(null)">
                    Add Product
                </PrimaryButton>
            </div>
        </template>
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="border rounded-lg divide-y divide-gray-200 bg-white overflow-hidden">
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 0">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Title</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Redirect URL</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">ArtBoard Type</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Sizes</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Orders</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Created At</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                <tr v-for="product in products">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.title }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.slug }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.redirect_url }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ getProductTypeLabel(product) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.sizes_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ product.orders_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                        <span v-if="product.deleted_at" class="text-red-500">
                                            Inactive
                                        </span>
                                        <span v-else class="text-blue-500">
                                            Active
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $filters.formatDateTime(product.created_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                        <div class="flex gap-2">
                                            <button
                                                class="h-7 w-7 flex items-center justify-center rounded-full border border-primary text-primary hover:bg-primary hover:text-white"
                                                @click="handleView(product)"
                                            >
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <button @click="handleProductDetail(product)" class="h-7 w-7 rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="products.length === 0">
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
import {Link, router} from "@inertiajs/vue3";
import MerchantLayout from "@/Layouts/MerchantLayout.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { getProductTypeLabel } from "@/Utils/helpers";

export default {
    components: {PrimaryButton, SvgIcon, MerchantLayout, Link},
    props: {
        products: {
            type: Array,
            required: true
        },
    },
    data() {
        return {
            product: null,
        }
    },
    methods: {
        handleProductDetail(product) {
            this.product = product
            const routeName = 'merchant.product.detail';
            if (product) {
                router.get(route(routeName, {product_id: product.id}));
            } else {
                router.get(route(routeName));
            }
        },
        handleView(product) {
            const userSlug = this.$page.props.auth.user.slug
            if (userSlug) {
                window.open(
                    `https://${userSlug}.${this.$page.props.appDomain}/builder/create/${product.slug}`,
                    '_blank'
                );
            } else {
                window.Toast.warning({
                    message: 'You should fill out company information.'
                })
                router.get(route('merchant.setting.index') + '#company')
            }
        },
        getProductTypeLabel(product) {
            return getProductTypeLabel(product)
        }
    }
}
</script>
