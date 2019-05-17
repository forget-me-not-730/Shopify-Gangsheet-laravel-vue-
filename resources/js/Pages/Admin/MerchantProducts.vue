<template>
    <AdminLayout title="Merchant Products">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Merchant Products
                </h2>
                <PrimaryButton @click="openModal(null)">
                    Add Product
                </PrimaryButton>
            </div>
        </template>

        <Table :resource="products" :striped="true" :preserve-scroll="true">
            <template #cell(deleted_at)="{ item: product }">
                <span v-if="product.deleted_at" class="text-red-500">
                    Inactive
                </span>
                <span v-else class="text-blue-500">
                    Active
                </span>
            </template>
            <template #cell(created_at)="{ item: product }">
                {{ $filters.formatDateTime(product.created_at) }}
            </template>
            <template #cell(actions)="{ item: product }">
                <div class="flex gap-2">
                    <button @click="openModal(product)" class="h-8 w-8 rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                        <i class="mdi mdi-pencil"></i>
                    </button>
                </div>
            </template>
        </Table>

        <ProductModal v-if="modalOpen" :user_id="user_id" :product="product" :close="closeModal" />
    </AdminLayout>
</template>

<script>
import { Table } from "@protonemedia/inertiajs-tables-laravel-query-builder";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import ProductModal from "@/Pages/Admin/ProductModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

export default {
    components: {PrimaryButton, ProductModal, Table, AdminLayout, Link},
    props: {
        products: Object,
        user_id: [String, Number]
    },
    data(){
        return {
            modalOpen: false,
            product: null,
        }
    },
    methods: {
        closeModal(){
            this.modalOpen = false
        },
        openModal(product){
            this.product = product
            this.modalOpen = true
        }
    }
}
</script>
