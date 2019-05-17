<script>
import {defineComponent} from 'vue'
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import moment from 'moment'
import {router} from '@inertiajs/vue3'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiImage, mdiPencil, mdiDownload, mdiCreation} from '@mdi/js'
import GbsSelect from '@/Components/Select.vue'
import Pagination from '@/Components/Pagination.vue'
import DesignStatus from '@/Components/DesignStatus.vue'
import DesignType from '@/Components/DesignType.vue'
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import DownloadDesignModal from "@/Components/Modals/DownloadDesignModal.vue";

export default defineComponent({
    name: 'Designs',
    components: {DownloadDesignModal, CloseIcon, DesignStatus, Pagination, GbsSelect, MerchantLayout, SvgIcon, DesignType},
    props: {
        designs: {
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
            order_status: this.$props.filters.order_status,
            design_status: this.$props.filters.design_status,
            search: this.$props.filters.search,
            searchBy: this.$props.filters.searchBy,

            selectedDesign: null,
            openDownloadDesignModal: false,

            mdiImage,
            mdiPencil,
            mdiDownload,
            mdiCreation
        }
    },
    watch: {
        search() {
            this.handleFilter('search', this.search)
        }
    },
    methods: {
        moment,
        handleOrderStatusChange(e) {
            this.handleFilter('order_status', e.target.value)
        },
        handleDesignStatusChange(e) {
            this.handleFilter('design_status', e.target.value)
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
        getPreviewUrl(design) {
            return route('merchant.design.thumbnail', {design_id: design.id})
        },
        getDownloadUrl(design) {
            return route('merchant.design.download', {design_id: design.id})
        },
        handleDesignGenerate(design) {
            design.status = 'processing'
            axios.get(route('merchant.design.generate', {design_id: design.id})).then((res) => {
                if (!res.data.success) {
                    window.Toast.error({
                        message: 'You have exceeded the maximum number of processing designs. Please try again later.'
                    })
                }
            })
        },
        handleDownloadDesign(design) {
            this.selectedDesign = design
            this.openDownloadDesignModal = true
        },
        getDesignSize(design) {
            if (design.data) {
                const data = JSON.parse(design.data)

                if (data.meta?.variant) {
                    return `${data.meta.variant.width} ${data.meta.variant.unit} x ${data.meta.variant.height} ${data.meta.variant.unit}`
                }
            }
            return '';
        }
    }
})
</script>

<template>
    <MerchantLayout title="Designs">

        <download-design-modal :design="selectedDesign" :open="Boolean(openDownloadDesignModal && selectedDesign)" @close="openDownloadDesignModal = false"/>

        <div class="flex flex-col text-xs">
            <div class="p-1 min-w-full inline-block align-middle">
                <div class="border rounded-lg bg-white divide-y divide-gray-200">
                    <div class="py-2 px-4 flex justify-between items-center space-x-4">
                        <div class="flex items-center">
                            <div class="flex items-center">
                                <select
                                    :value="design_status"
                                    @change="handleDesignStatusChange"
                                    class="block w-full cursor-pointer rounded-md border-gray-300 py-1 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                    <option value="all">All</option>
                                    <option value="created">Created</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <div class="relative max-w-sm w-full">
                                <input v-model="search" class="py-1 px-2 border-gray-200 rounded text-xs w-72 focus:ring-blue-500" placeholder="Search">
                                <div v-if="search" class="absolute top-1.5 right-1 text-gray-500 cursor-pointer" @click="search = ''">
                                    <close-icon/>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <select
                                    :value="searchBy"
                                    @change="handleSearchByChange"
                                    class="block cursor-pointer rounded-md border-gray-300 py-1 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                    <option value="design_id">Design Id</option>
                                    <option value="customer">Customer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 0">
                        <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Design ID/File Name</th>
                            <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Size</th>
                            <th v-if="$page.props.auth.user.type !== 'custom'" scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th v-if="$page.props.auth.user.type !== 'custom'" scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Order ID</th>
                            <th v-if="$page.props.auth.user.type !== 'custom'" scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Order Status</th>
                            <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Paid At</th>
                            <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Design Status</th>
                            <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Design Type</th>
                            <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Created At</th>
                            <th scope="col" class="px-4 py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">

                        <tr v-for="design in designs.data">
                            <td class="px-4 py-2 whitespace-nowrap text-gray-800">
                                <div class="flex bg-gray-100 w-max pl-2 rounded">
                                    {{ design.id }}
                                </div>
                                <div v-if="design.file_name" class="max-w-xs truncate px-1">
                                    {{ design.file_name }}
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-800">
                                {{ getDesignSize(design) }}
                            </td>
                            <td v-if="$page.props.auth.user.type !== 'custom'" class="px-4 py-2 whitespace-nowrap text-gray-800">
                                <div v-if="design.customer_id || design.order_id" class="flex flex-col">
                                    <span>{{ design.customer_name }}</span>
                                    <small>{{ design.customer_email }}</small>
                                </div>
                            </td>
                            <td v-if="$page.props.auth.user.type !== 'custom'" class="px-4 py-2 whitespace-nowrap text-gray-800">
                                {{ design.order_id }}
                            </td>
                            <td v-if="$page.props.auth.user.type !== 'custom'" class="px-4 py-2 whitespace-nowrap text-gray-800">
                                <div
                                    v-if="design.order_status"
                                    class="capitalize rounded-full px-4 py-px bg-gray-200 max-w-min"
                                    :class="{
                                                'bg-yellow-200': design.order_status === 'paid',
                                                'bg-green-200': design.order_status === 'in-progress',
                                                'bg-green-500 text-white': design.order_status === 'completed',
                                            }"
                                >
                                    <span class="font-bold">&bull;</span>
                                    {{ design.order_status }}
                                </div>
                                <div v-else>
                                    In Cart
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-800">
                                {{ design.paid_at ? moment(design.paid_at).format('MM/DD - LT') : 'N/A' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-800">
                                <DesignStatus :design="design"/>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-800">
                                <DesignType :design="design"/>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-800">
                                {{ moment(design.created_at).format('MM/DD - LT') }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-800 text-right">
                                <div class="flex gap-1 flex-row-reverse">
                                    <a :href="getPreviewUrl(design)" v-tooltip:top="'Open Thumbnail'" target="_blank"
                                       class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white">
                                        <svg-icon type="mdi" :path="mdiImage" size="16"/>
                                    </a>
                                    <template v-if="design.status === 'completed'">
                                        <a v-if="design.is_paid || design.paid_at || $page.props.auth.user.credits === -1" :href="getDownloadUrl(design)" v-tooltip:top="'Download'" target="_blank"
                                           class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white">
                                            <svg-icon type="mdi" :path="mdiDownload" size="16"/>
                                        </a>
                                        <button v-else v-tooltip:top="'Download'" @click="handleDownloadDesign(design)"
                                                class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white">
                                            <svg-icon type="mdi" :path="mdiDownload" size="16"/>
                                        </button>
                                    </template>
                                    <button v-else v-tooltip:top="'Generate Design'" @click="handleDesignGenerate(design)" :disabled="design.status === 'processing'"
                                            class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white disabled:text-gray-300 disabled:pointer-events-none">
                                        <svg-icon type="mdi" :path="mdiCreation" size="16"/>
                                    </button>
                                    <a :href="route('merchant.design.edit', {design_id: design.id})" target="_blank"
                                       class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white">
                                        <svg-icon type="mdi" :path="mdiPencil" size="16"/>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="designs.data.length === 0">
                            <td colspan="12" class="px-4 py-2 whitespace-nowrap text-gray-400 h-40 text-lg text-center">
                                No Designs Found
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <Pagination :data="designs"/>
                </div>
            </div>
        </div>
    </MerchantLayout>
</template>

<style scoped>

</style>
