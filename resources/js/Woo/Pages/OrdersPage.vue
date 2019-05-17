<script>
import {defineComponent} from 'vue'
import GbsSelect from '@/Components/Select.vue'
import Spinner from '@/Components/Spinner.vue'
import Pagination from '@/Woo/Components/Pagination.vue'
import DesignStatus from '@/Woo/Components/DesignStatus.vue'
import {getDesignDownloadUrl, getShopOrders} from '@/Woo/Apis/gsbApi'
import WooDesignStatus from '@/Woo/Components/WooDesignStatus.vue'
import {getGsEditUrl} from '@/Woo/woo-gs-helpers'

export default defineComponent({
    name: 'OrdersPage',
    components: {WooDesignStatus, DesignStatus, Pagination, GbsSelect, Spinner},
    data() {
        return {
            order_items: [],
            loading: true,
            currentPage: 1,
            perPage: 10,
            total: 0,
            links: [],
            search: '',
            searchBy: 'design_id',
            searchDelayTimer: 0,
            copiedDesignId: null,
            imagesToView: [],
            openImagesModal: false,
            orderIdSortMethod: 0,   //0: normal, 1: asc, -1: desc
        }
    },
    mounted() {
        this.loadOrders()
    },
    watch: {
        orderIdSortMethod() {
            this.loadOrders()
        }
    },
    methods: {
        handlePageChange(page) {
            this.currentPage = page
            this.loadOrders()
        },
        loadOrders() {
            this.loading = true
            getShopOrders({page: this.currentPage, perPage: this.perPage, sort: this.orderIdSortMethod, search: this.search, searchBy: this.searchBy}).then(res => {
                if (res.data.success) {
                    this.order_items = res.data.orders
                    this.links = res.data.links
                    this.total = res.data.total
                }
                this.loading = false
            })
        },
        onChangePerPage() {
            this.currentPage = 1
            this.loadOrders()
        },
        onChangeSearch() {
            clearTimeout(this.searchDelayTimer)
            this.searchDelayTimer = setTimeout(() => {
                this.currentPage = 1
                this.loadOrders()
                this.searchDelayTimer = 0
            }, 1000)
        },
        onChangeSearchBy() {
            this.loadOrders()
        },
        handleCopyDesignId(design_id) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(design_id).then(() => {
                    this.copiedDesignId = design_id
                    setTimeout(() => {
                        this.copiedDesignId = null
                    }, 1000)
                })
            } else {
                window.Toast.error({
                    message: 'Your connection is not secure.'
                })
            }
        },
        getOrderDetailUrl(order_item) {
            const orderUrl = new URL(`${window.gs_website}/wp-admin/admin.php`)
            orderUrl.searchParams.append('page', 'wc-orders')
            orderUrl.searchParams.append('action', 'edit')
            orderUrl.searchParams.append('id', order_item.wc_order_id)

            return orderUrl.toString()
        },
        getDesignEditUrl(order_item) {
            return getGsEditUrl(order_item.design_id)
        },
        downloadDesign(order_item) {
            getDesignDownloadUrl(order_item.design_id).then(res => {
                if (res.data.success) {
                    window.open(res.data.url, '_blank')
                } else {
                    window.Toast.error({
                        message: res.data.error
                    })
                }
            })
        },
        changeOrderIdSortMethod() {
            this.orderIdSortMethod = (this.orderIdSortMethod + 2) % 3 - 1
        }
    },

})
</script>

<template>
    <div class="p-5">
        <div class="flex justify-between px-2 my-2">
            <div class="flex items-center">
                <select v-model="perPage" @change="onChangePerPage">
                    <option :value="10">10 Per Page</option>
                    <option :value="20">20 Per Page</option>
                    <option :value="50">50 Per Page</option>
                    <option :value="100">100 Per Page</option>
                </select>
            </div>
            <div class="flex space-x-2">
                <div class="flex">
                    <input type="text" v-model="search" placeholder="Search..." @input="onChangeSearch"
                        class="rounded-md border-gray-300 py-1 text-xs focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 w-72 transition-all"/>
                </div>
                <div class="flex items-center">
                    <select v-model="searchBy" @change="onChangeSearchBy">
                        <option value="design_id">Design Id</option>
                        <option value="customer">Customer</option>
                        <option value="product">Product</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border text-center">
                <thead class="bg-primary-tbl-header">
                <tr class="border-b border-t">
                    <td class="py-2">
                        <span class="mr-2 relative">
                            Order ID
                            <a @click="changeOrderIdSortMethod" class="absolute cursor-pointer text-xl" :style="`top: ${-(5 + orderIdSortMethod * 2)}px;`">
                                <i class="mdi" :class="[orderIdSortMethod === 0 ? 'mdi-menu-swap' : orderIdSortMethod === 1 ? 'mdi-menu-up' : 'mdi-menu-down']"></i>
                            </a>
                        </span>
                    </td>
                    <td>Product</td>
                    <td class="text-left">Size</td>
                    <td class="text-left">Date</td>
                    <td class="text-left">Customer</td>
                    <td>Price</td>
                    <td>Quantity</td>
                    <td class="w-44">Status</td>
                    <td>Actions</td>
                </tr>
                </thead>
                <tbody>
                <tr v-if="loading">
                    <td colspan="10" class="py-12">
                        <div class="flex items-center justify-center">
                            <Spinner class="w-6 h-6"/>
                        </div>
                    </td>
                </tr>
                <tr v-else v-for="(order_item, index) of order_items" :key="index" class="border-b border-t hover:bg-gray-100 cursor-pointer">
                    <td class="text-black">
                        {{ order_item.wc_order_id }}
                    </td>
                    <td>
                        {{ order_item.product?.title }}
                    </td>
                    <td class="text-left">
                        {{ order_item.variant?.title }}
                    </td>
                    <td class="text-left">
                        {{ new Date(order_item.ordered_at).toLocaleDateString() }}
                    </td>
                    <td class="text-left">
                        <div>{{ order_item.customer?.name }}</div>
                        <div><small>{{ order_item.customer?.email }}</small></div>
                    </td>
                    <td>
                        ${{ order_item.subtotal }}
                    </td>
                    <td class="text-center">
                        {{ order_item.quantity }}
                    </td>
                    <td>
                        <woo-design-status :design="order_item"/>
                    </td>
                    <td class="p-1">
                        <div class="flex gap-1 items-center justify-center">
                            <button @click="downloadDesign(order_item)"
                                    v-tooltip:top="'Gang Sheet Image'"
                                    class="w-7 h-7 flex items-center justify-center border border-primary rounded-full text-primary hover:text-white hover:bg-blue-500">
                                <i class="mdi mdi-download"></i>
                            </button>
                            <a :href="getOrderDetailUrl(order_item)" target="_blank"
                               v-tooltip:top="'Order Detail'"
                               class="w-7 h-7 flex items-center justify-center border border-primary rounded-full text-primary hover:text-white hover:bg-blue-500">
                                <i class="mdi mdi-eye"></i>
                            </a>
                            <a :href="getDesignEditUrl(order_item)" target="_blank"
                               v-tooltip:top="'Edit Design'"
                               class="w-7 h-7 flex items-center justify-center border rounded-full relative hover:text-white"
                               :class="['border-primary hover:bg-blue-500 text-primary']"
                            >
                                <i class="mdi mdi-pencil"></i>
                            </a>
                            <button
                                v-if="order_item.design_id"
                                v-tooltip:top="'Copy Design ID'"
                                @click="handleCopyDesignId(order_item.design_id)"
                                class="w-5 h-7 flex items-center justify-center border relative hover:text-white disabled:text-gray-400 hover:bg-blue-500 text-primary"
                            >
                                <i class="mdi" :class="[copiedDesignId === order_item.design_id ? 'mdi-check' : 'mdi-content-copy']"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="!loading && order_items.length === 0">
                    <td colspan="10" class="py-12">
                        <div class="flex items-center justify-center text-gray-300">
                            No orders
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="w-full my-2">
                <Pagination :links="links" @change="handlePageChange"/>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
