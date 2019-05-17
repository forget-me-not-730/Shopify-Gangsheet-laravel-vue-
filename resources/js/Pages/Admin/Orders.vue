<template>
    <AdminLayout title="Brands">
        <template #header>

            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Orders
                </h2>
            </div>

        </template>

        <Table :resource="orders"
               :striped="true"
               :preserve-scroll="true"
        >
            <template #cell(id)="{ item: order }">
                {{ order.id }}
            </template>
            <template #cell(shop_name)="{ item: order }">
                <a :href="'https://' + order.shop_name" target="_blank" class="text-indigo-600">{{ order.shop_name }}</a>
            </template>
            <template #cell(data)="{ item: order }">
                <div class="whitespace-normal">{{order.data}}</div>
            </template>
            <template #cell(created_at)="{ item: order }">
                {{ $filters.formatDateTime(order.created_at) }}
            </template>
            <template #cell(actions)="{ item: order }">
                <div class="flex flex-wrap gap-2">
                    <button @click="openModal(order)"  class="w-8 h-8 text-center rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                        <i class="mdi mdi-eye" />
                    </button>
                </div>
            </template>
        </Table>
        <OrderModal v-if="modalOpen" :order="order" :close="closeModal" />
    </AdminLayout>
</template>

<script>
import { Table } from "@protonemedia/inertiajs-tables-laravel-query-builder";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import OrderModal from "@/Pages/Admin/OrderModal.vue";

export default {
    name: 'Orders',
    components: {Table, AdminLayout, OrderModal},
    props: {
        orders: Object
    },
    data(){
        return {
            modalOpen: false,
            order: null,
        }
    },
    methods: {
        closeModal(){
            this.modalOpen = false
        },
        openModal(order){
            this.order = order
            this.modalOpen = true
        }
    }
}
</script>
