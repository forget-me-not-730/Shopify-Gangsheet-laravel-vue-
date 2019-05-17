<template>
    <MerchantLayout title="Orders">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Orders
                </h2>
            </div>
        </template>

        <div class="flex flex-col text-xs">
            <div class="p-1 min-w-full inline-block align-middle">
                <div class="border rounded-lg bg-white divide-y divide-gray-200">
                    <div class="py-2 px-4 flex justify-between items-center space-x-4">
                        <div class="flex space-x-2">
                            <button class="flex inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-xs 
                                tracking-widest text-black bg-gray-200}"
                                :class="{
                                'text-white bg-primary': filter.status !== 'archived' && filter.status !== 'all',
                                'text-black bg-gray-200': filter.status === 'archived' || filter.status === 'all'
                                }"
                                @click="handleStatusChange('orders')" >
                                Orders
                            </button>
                            <button class="flex inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-xs
                                tracking-widest"
                                :class="{
                                'text-white bg-primary': filter.status === 'archived',
                                'text-black bg-gray-200': filter.status !== 'archived'
                                }"
                                @click="handleStatusChange('archived')" >
                                Archived
                            </button>
                            <button class="flex inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-xs
                                tracking-widest"
                                :class="{
                                'text-white bg-primary': filter.status === 'all',
                                'text-black bg-gray-200': filter.status !== 'all'
                                }"
                                @click="handleStatusChange('all')" >
                                All
                            </button>
                        </div>

                        <div class="flex space-x-2">
                            <div class="relative max-w-sm w-72">
                                <input v-model="filter.search" class="py-1 px-2 w-full border-gray-200 rounded text-xs focus:ring-blue-500" placeholder="Search">
                                <div v-if="filter.search" class="absolute top-1.5 right-1 text-gray-500 cursor-pointer" @click="filter.search = ''">
                                    <close-icon/>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <select
                                    :value="filter.searchBy"
                                    @change="handleSearchByChange"
                                    class="block cursor-pointer rounded-md border-gray-300 py-1 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                    <option value="product">Product</option>
                                    <option value="name">Name</option>
                                    <option value="email">Email</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <table class="w-full">
                        <thead>
                        <tr v-if="selectedOrders.length > 0">
                            <th colspan="12" class="p-2 border-t border-b">
                                <div class="w-full flex items-center justify-between">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="selectedOrders.length === orders.data.length" class="checkbox-primary all mr-2" @change="handleSelectAll"/>
                                        <span class="pt-0.5">{{ selectedOrders.length }} Selected</span>
                                    </label>
                                    <div>
                                        <button class="btn-danger" @click="openArchiveModal">Archive</button>
                                    </div>
                                </div>
                            </th>
                        </tr>
                        <tr v-else class="bg-gray-100 border-t border-b text-xs text-gray-500">
                            <th class="w-48 text-left px-2 py-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" class="checkbox-primary mr-2" @change="handleSelectAll"/>
                                    <span>Id</span>
                                </label>
                            </th>
                            <th class="text-left px-2 py-3">
                                Product
                            </th>
                            <th class="text-left px-2 py-3">
                                Name
                            </th>
                            <th class="text-left px-2 py-3">
                                Email
                            </th>
                            <th class="text-left px-2 py-3">
                                Phone
                            </th>
                            <th class="text-left px-2 py-3">
                                Total Price
                            </th>
                            <th class="text-left px-2 py-3">
                                Items
                            </th>
                            <th class="text-left px-2 py-3">
                                Status
                            </th>
                            <th class="text-left px-2 py-3">
                                Created At
                            </th>
                            <th class="text-left px-2 py-3">
                                Actions
                            </th>
                        </tr>
                        </thead>
                        <tr v-for="order in orders.data" :key="order.id" class="border-b hover:bg-gray-100" :class="{'bg-gray-100': selectedOrders.includes(order.id)}">
                            <td class="text-left px-2 py-3">
                                <label class="flex cursor-pointer items-center whitespace-nowrap">
                                    <input :value="order.id" v-model="selectedOrders" type="checkbox" class="checkbox-primary mr-2"/>
                                    <span>{{ order.id }}</span>
                                </label>
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                {{ order.product_title }}
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                {{ order.name }}
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                {{ order.email }}
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                {{ order.phone }}
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                {{ order.price }}
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                {{ order.designs_count }}
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                <div
                                    class="capitalize rounded-full px-4 py-px bg-gray-200 max-w-min"
                                    :class="{
                                        'bg-yellow-200': order.status === 'paid',
                                        'bg-green-200': order.status === 'in-progress',
                                        'bg-green-500 text-white': order.status === 'completed',
                                    }"
                                >
                                    <span class="font-bold">&bull;</span>
                                    <span class="ml-1">{{ order.status }}</span>
                                </div>
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                {{ $filters.formatDateTime(order.created_at) }}
                            </td>
                            <td class="text-left text-sm px-2 py-3">
                                <button @click="openOrderModal(order)" class="h-8 w-8 rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                                    <i class="mdi mdi-eye"></i>
                                </button>&nbsp;
                                <button v-if="order.status !== 'archived'" @click="openArchiveModal(order)"
                                        class="h-8 w-8 rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                                    <i class="mdi mdi-archive"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="12" class="h-40 text-center text-lg text-gray-400">
                                No orders listed
                            </td>
                        </tr>
                    </table>

                    <Pagination :data="orders"/>
                </div>
            </div>
        </div>

        <OrderModal v-if="orderModalOpen" :order="order" :close="closeOrderModal"/>
        <ArchiveModal :open="archiveModalOpen" @close="closeArchiveModal" @archive="archiveOrders"/>
    </MerchantLayout>
</template>

<script>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import OrderModal from '@/Pages/Merchant/OrderModal.vue'
import ArchiveModal from '@/Components/Modals/ArchiveModal.vue'
import Pagination from '@/Components/Pagination.vue'
import {router} from '@inertiajs/vue3'
import axios from 'axios';
import {debounce} from 'lodash';
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default {
    name: 'Orders',
    components: {CloseIcon, OrderModal, MerchantLayout, ArchiveModal, Pagination},
    props: {
        orders: {
            type: Object,
            required: true
        },
        filters: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            orderModalOpen: false,
            archiveModalOpen: false,
            archiveSingle: null,
            order: null,

            selectedOrders: [],
            filter: {
                search: this.filters.search,
                status: this.filters.status,
                searchBy: this.filters.searchBy,
            },
        }
    },
    watch: {
        filter: {
            deep: true,
            handler() {
                debounce(() => {
                    this.reloadOrders()
                }, 750)()
            }
        }
    },
    methods: {
        reloadOrders() {
            this.archiveModalOpen = false

            const url = new URL(window.location.href)
            url.searchParams.set('search', this.filter.search || '')
            url.searchParams.set('searchBy', this.filter.searchBy || 'design_id')
            url.searchParams.set('page', 1)
            url.searchParams.set('status', this.filter.status || 'orders')

            router.get(url.toString())
        },
        openOrderModal(order) {
            this.order = order
            this.orderModalOpen = true
        },
        closeOrderModal() {
            this.orderModalOpen = false
        },
        openArchiveModal(order) {
            this.archiveSingle = order.id
            this.archiveModalOpen = true
        },
        closeArchiveModal() {
            this.archiveSingle = null;
            this.archiveModalOpen = false
        },
        handleSelectAll(e) {
            if (e.target.checked) {
                this.selectedOrders = this.orders.data.map(design => design.id)
            } else {
                this.selectedOrders = []
            }
        },
        handleStatusChange(status) {
            this.handleFilter('status', status)
        },
        handleSearchByChange(e) {
            this.handleFilter('searchBy', e.target.value)
        },
        handleFilter(key, value) {
            const url = new URL(window.location.href)

            if (typeof key === 'object') {
                for (let k in key) {
                    url.searchParams.set(k, key[k] || '')
                }
            } else {
                url.searchParams.set(key, value || '')
            }

            url.searchParams.set('page', '1')

            router.get(url.toString())
        },
        archiveOrders() {
            let orders = []
            if (this.archiveSingle) {
                orders = [this.archiveSingle]
                this.archiveSingle = null;
            } else
                orders = this.selectedOrders
            console.log(orders)
            axios.put(route('merchant.order.archive'), {
                orders,
            }).then(response => {
                if (response.data.success === true) {
                    this.archiveModalOpen = false
                    this.reloadOrders();
                }
            }).catch(error => {
                console.error('Error archiving order:', error);
            });
        }
    }
}
</script>

<style scoped>

</style>
