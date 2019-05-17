<script>
import { defineComponent } from 'vue'
import { Table } from '@protonemedia/inertiajs-tables-laravel-query-builder'
import GbsSelect from '@/Components/Select.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import moment from 'moment-timezone'
import Pagination from '@/Components/Pagination.vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

export default defineComponent({
    name: 'Transactions',
    components: { Pagination, SvgIcon, AdminLayout, GbsSelect, Table },
    props: {
        users: {
            type: Array,
            required: true
        },
        transactions: {
            type: Object,
            required: true
        },
        filters: {
            type: Object,
            required: true
        }
    },
    data () {
        let user = null

        if (this.$props.filters.user) {
            user = this.$props.users.find(u => u.id.toString() === this.$props.filters.user.toString())
        }

        return {
            filter: {
                user,
                search: this.$props.filters.search ?? ''
            }
        }
    },
    watch: {
        filter: {
            deep: true,
            handler () {
                debounce(() => {
                    router.get(this.$route('admin.transaction.index', {
                            user: this.filter.user.id,
                            search: this.filter.search,
                            page: 1
                        }
                    ))
                }, 750)()
            }
        }
    },
    methods: {
        moment
    }
})
</script>

<template>
    <AdminLayout title="Transactions">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Transactions
                </h2>
            </div>
        </template>

        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="border rounded-lg divide-y divide-gray-200">
                        <div class="py-3 px-4 flex justify-between items-center">
                            <div class="w-96">
                                <GbsSelect :options="users" :search="true" v-model="filter.user">
                                    <template #selected="{selected}">
                                        <div class="flex flex-col w-full">
                                            <div class="inline-block whitespace-nowrap">{{ selected?.company_name ?? 'All' }}</div>
                                        </div>
                                    </template>

                                    <template #option="{option}">
                                        <div class="flex flex-col w-full">
                                            <div class="inline-block whitespace-nowrap">{{ option?.company_name }}</div>
                                            <small class="opacity-50">{{ option?.website }}</small>
                                        </div>
                                    </template>
                                </GbsSelect>
                            </div>
                            <div class="relative w-full max-w-xs">
                                <label class="sr-only">Search</label>
                                <input type="text" v-model="filter.search"
                                       class="py-2 px-3 ps-9 block w-full border-gray-200 shadow-sm rounded-lg text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none"
                                       placeholder="Search for items">
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
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Order</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Shop</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Size</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Created At</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">

                                <tr v-for="design in transactions.data">
                                    <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-800">
                                        #{{ design.order_id }}
                                    </td>

                                    <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-800">
                                        <div class="flex flex-col">
                                            <span>{{ design.merchant.company_name }}</span>
                                            <small>{{ design.merchant.website }}</small>
                                        </div>
                                    </td>

                                    <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-800">
                                        <template v-if="design.data.meta?.variant">
                                            <div v-if="design.data.meta.variant.title">{{ design.data.meta.variant.title }}</div>
                                            <div v-else>
                                                {{ design.data.meta.variant.width }}{{ design.data.meta.variant.unit }} x {{ design.data.meta.variant.height }}{{ design.data.meta.variant.unit }}
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-800">
                                        <div v-if="design.order" class="flex flex-col">
                                            <span>{{ design.order?.name }}</span>
                                            <small>{{ design.order?.email }}</small>
                                        </div>
                                        <div v-else class="flex flex-col">
                                            <span>{{ design.customer?.name }}</span>
                                            <small>{{ design.customer?.email }}</small>
                                        </div>
                                    </td>
                                    <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-800">
                                        ${{ design.commission }}
                                    </td>
                                    <td class="px-6 py-1 whitespace-nowrap text-sm text-gray-800">
                                        {{ moment(design.updated_at).format('MM/DD - LT') }}
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-gray-400 h-40 text-center">
                                        No Transactions Found
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="py-4 px-6">
                            <Pagination :data="transactions"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>

</style>
