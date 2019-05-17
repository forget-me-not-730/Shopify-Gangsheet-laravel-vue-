<script>
import {defineComponent} from 'vue'
import {getProducts} from "@/Woo/Apis/gsbApi";
import ProductDetail from "@/Woo/Components/ProductDetail.vue";
import Spinner from "@/Components/Spinner.vue";
import { getProductTypeLabel } from "@/Utils/helpers";

export default defineComponent({
    name: "ProductsPage",
    components: {ProductDetail, Spinner},
    data() {
        return {
            loading: true,
            products: [],
            selectedProduct: null
        }
    },
    mounted() {
        this.loading = true
        getProducts().then(res => {
            if (res.data.success) {
                this.products = res.data.products
                this.loading = false
            } else {
                this.loading = false
                window.Toast.error({
                    message: res.data.error
                })
            }
        })
    },
    methods: {
        getProductTypeLabel(product) {
            return getProductTypeLabel(product)
        }
    }
})
</script>

<template>
    <div class="p-4">
        <div class="hidden justify-end px-2">
            <button class="btn-primary">
                <i class="mdi mdi-plus-circle-outline text-white"></i>
                Add Product
            </button>
        </div>
        <ProductDetail v-if="selectedProduct" :product="selectedProduct" @back="selectedProduct = null"/>
        <table v-else class=" w-full border-collapse border text-center mt-2">
            <thead class="bg-primary-tbl-header">
            <tr class="border-b border-t">
                <td class="p-2 text-left">Image</td>
				<td>Title</td>
                <td>Tags</td>
				<td>Variants</td>
                <td>Art Board Type</td>
				<td>Created At</td>
				<td>Actions</td>
            </tr>
            </thead>
            <tbody>
            <tr v-if="loading">
                <td colspan="7" class="text-center py-12">
                    <div class="flex items-center justify-center">
                        <Spinner class="w-6 h-6"/>
                    </div>
                </td>
            </tr>
            <template v-else>
                <tr v-if="!products.length">
                    <td colspan="8" class="text-center py-12">
                        No Products
                    </td>
                </tr>
                <tr v-else v-for="product in products" class="border-b border-t hover:bg-gray-100 cursor-pointer">
                    <td class="p-2">
						<div class="w-20 h-20">
							<div class="border flex items-center justify-center w-full h-full">
								<img v-if="product.image" :src="product.image[0]" alt="placeholder"/>
								<span v-else>No Image</span>
							</div>
						</div>
					</td>
                    <td>
                        {{ product.title }}
                    </td>
                    <td>
                        {{ product.tags.join(', ') }}
                    </td>
                    <td>
                        {{ product.variants.length }}
                    </td>
                    <td>
                        {{ getProductTypeLabel(product) }}
                    </td>
                    <td>
                        {{ new Date(product.created_at).toLocaleString() }}
                    </td>
                    <td class="p-1">
                        <div class="flex gap-2 items-center justify-center">
                            <button @click="selectedProduct = product" class="rounded-full w-8 h-8 border border-primary text-primary hover:bg-blue-500 hover:text-white">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</template>

<style scoped>

</style>
