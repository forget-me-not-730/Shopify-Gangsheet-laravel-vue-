<template>
    <AdminLayout title="Products">
        <template #header>

            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Orders
                </h2>
            </div>

        </template>

        <Table :resource="products"
               :striped="true"
               :preserve-scroll="true"
        >
            <template #cell(id)="{ item: product }">
                {{ product.id }}
            </template>
            <template #cell(created_at)="{ item: product }">
                {{ $filters.formatDateTime(product.created_at) }}
            </template>
            <template #cell(deleted_at)="{ item: product }">
                <span v-if="product.deleted_at" class="text-red-500">
                    Inactive
                </span>
                <div v-else class="text-blue-500">
                    Active
                </div>
            </template>
            <template #cell(actions)="{ item: product }">
                <div class="flex flex-wrap gap-2">
                    <a :href="`https://${product.user.slug}.${$page.props.appDomain}/builder/create/${product.slug}`" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                        <i class="mdi mdi-eye" />
                    </a>
                </div>
            </template>
        </Table>
    </AdminLayout>
</template>

<script>
import { Table } from "@protonemedia/inertiajs-tables-laravel-query-builder";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import OrderModal from "@/Pages/Admin/OrderModal.vue";

export default {
    name: 'Products',
    components: {Table, AdminLayout, OrderModal},
    props: {
        products: {
            type: Object,
            required: true
        }
    }
}
</script>
