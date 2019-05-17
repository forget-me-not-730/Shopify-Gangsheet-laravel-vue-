<template>
    <AdminLayout title="Merchants">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Merchants
                </h2>

                <button class="btn btn-primary" @click="openCreateStoreModal = true">
                    Create Store
                </button>
            </div>
        </template>

        <div class="border rounded-lg divide-y divide-gray-200">
            <div class="py-3 px-4 flex items-center">
                <div class="inline-block w-[150px] relative mr-1">
                    <select v-model="type"
                            class="block w-full cursor-pointer rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        <option value="all">All</option>
                        <option value="normal">Normal</option>
                        <option value="woo">Woo</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <div class="relative max-w-md w-full">
                    <label class="sr-only">Search</label>
                    <input v-model="search" type="text" name="hs-table-with-pagination-search" id="hs-table-with-pagination-search"
                           class="py-2 px-3 ps-9 block w-full border-gray-200 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none"
                           placeholder="Type name or email">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                        <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 0">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Logo</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Company Name</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Products</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Orders/Designs</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Credits</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Created At</th>
                        <th class="p-3 text-start text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">

                    <tr v-for="merchant in merchants.data" class="text-sm">
                        <td class="p-3">{{ merchant.id }}</td>
                        <td class="py-3">
                            <div class="h-12 border border-gray-300 flex justify-center">
                                <img v-if="merchant.logo_url" :src="merchant.logo_url" class="h-full max-w-[160px] object-contain" alt="">
                            </div>
                        </td>
                        <td class="p-3">
                            {{ merchant.name }}
                        </td>
                        <td class="p-3">
                            <div class="flex flex-col relative">
                                <a :href="merchant.website" target="_blank" class="text-blue-500">
                                    {{ merchant.company_name ?? merchant.name }}
                                </a>
                                <span v-if="merchant.type === 'woo'" class="absolute top-full bg-green-300 text-xs font-thin w-max px-2 rounded-full">Woo store</span>
                                <span v-else-if="merchant.type === 'custom'" class="absolute top-full bg-blue-300 text-xs font-thin w-max px-2 rounded-full">Custom store</span>
                                <span v-else class="absolute top-full bg-gray-300 text-xs font-thin w-max px-2 rounded-full">Normal</span>
                            </div>
                        </td>
                        <td class="p-3">
                            {{ merchant.email }}
                        </td>
                        <td class="p-3">
                            <Link :href="route('admin.merchant.products', merchant.id)" class="text-blue-500 p-5">
                                {{ merchant.products_count }}
                            </Link>
                        </td>
                        <td class="p-3">
                            {{ merchant.orders_count }} / {{ merchant.designs_count }}
                        </td>
                        <td class="p-3">
                            {{ merchant.credits }}
                        </td>
                        <td class="p-3">
                            <span v-if="merchant.status === 'active'" class="text-red">
                                Active
                            </span>
                            <span v-else class="text-red-500">
                                Inactive
                            </span>
                        </td>
                        <td class="p-3">
                            {{ $filters.formatDateTime(merchant.created_at) }}
                        </td>
                        <td class="p-3">
                            <div class="flex gap-2">
                                <button @click="openModal(merchant)" class="h-8 w-8 rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <a :href="$route('admin.merchant.impersonate', merchant.id)"
                                   class="h-8 border rounded px-2 border-primary text-primary hover:bg-primary hover:text-white flex items-center">
                                    Login
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="merchants.data.length === 0">
                        <td colspan="11" class="px-6 py-4 whitespace-nowrap text-gray-400 h-40 text-center">
                            No Merchant Found
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :data="merchants"/>
        </div>

        <create-store-modal v-if="openCreateStoreModal" @success="handleStoreCreateSuccess" @close="openCreateStoreModal = false"/>
        <merchant-modal v-if="modalOpen" :merchant="merchant" :close="closeModal"/>
    </AdminLayout>
</template>

<script>
import {Table} from '@protonemedia/inertiajs-tables-laravel-query-builder'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {Link, router} from '@inertiajs/vue3'
import MerchantModal from '@/Pages/Admin/MerchantModal.vue'
import CreateStoreModal from '@/Components/Modals/CreateStoreModal.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import Pagination from '@/Components/Pagination.vue'
import {debounce} from 'lodash'

export default {
    components: {Pagination, SvgIcon, CreateStoreModal, MerchantModal, Table, AdminLayout, Link},
    props: {
        merchants: Object,
        filters: Object
    },
    data() {
        return {
            modalOpen: false,
            openCreateStoreModal: false,

            merchant: null,
            type: this.filters.type,
            search: this.filters.search
        }
    },
    watch: {
        type() {
            this.handleFilterMerchants()
        },
        search() {
            debounce(this.handleFilterMerchants, 1500)();
        }
    },
    methods: {
        closeModal() {
            this.modalOpen = false
        },
        openModal(merchant) {
            this.merchant = merchant
            this.modalOpen = true
        },
        handleStoreCreateSuccess() {
            this.openCreateStoreModal = false
            router.reload({
                only: ['merchants']
            })
        },
        handleFilterMerchants(e) {
            const url = new URL(window.location.href)

            if (this.search) {
                url.searchParams.set('search', this.search)
            } else {
                url.searchParams.delete('search')
            }

            if (this.type) {
                url.searchParams.set('type', this.type)
            } else {
                url.searchParams.delete('type')
            }

            url.searchParams.set('page', 1)

            router.get(url.toString())
        }
    }
}
</script>
