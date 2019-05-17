<template>
    <AdminLayout title="Designs">
        <add-design-note-modal :open="Boolean(openAddNoteModal && selectedDesign)" :design="selectedDesign" @close="openAddNoteModal = false"/>
        <view-design-log-modal :open="Boolean(openViewLogModal && selectedDesign)" :design_id="selectedDesign?.id" @close="openViewLogModal = false"/>

        <template #header>
            <div class="space-y-2 text-xs">
                <div>
                    <h2 class="my-2 text-xl font-semibold text-gray-800">Designs</h2>
                    <div class="flex items-center flex-wrap w-full sm:space-x-2">
                        <div class="inline-block w-80 relative mr-1 max-sm:w-full">
                            <gbs-select :options="users" :search="true" v-model="user" :clear="true">
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
                            </gbs-select>
                        </div>
                        <div class="flex max-sm:w-full max-sm:justify-between max-sm:items-center sm:flex-col">
                            <input type="text" v-model="customer" class="block max-sm:w-full rounded-md border-gray-300 py-1.5" placeholder="Customer"/>
                        </div>
                        <div class="flex max-sm:w-full max-sm:justify-between max-sm:items-center sm:flex-col">
                            <input type="text" v-model="order_id" class="block max-sm:w-full rounded-md border-gray-300 py-1.5" placeholder="Order ID"/>
                        </div>
                        <div class="flex max-sm:w-full max-sm:justify-between max-sm:items-center sm:flex-col">
                            <input type="text" v-model="design_id" class="block max-sm:w-full rounded-md border-gray-300 py-1.5" placeholder="Design ID">
                        </div>
                        <button
                            class="flex items-center py-2 btn btn-secondary"
                            @click="handleClearFilter"
                        >
                            Clear
                        </button>
                        <button
                            class="flex items-center btn btn-primary"
                            @click="handleSearchClick"
                        >
                            Search
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap space-x-4">
                    <label v-for="tag in tagOptions" :key="tag.value" class="flex items-center">
                        <input type="checkbox" v-model="tags" :value="tag.value" class="w-4 h-4 m-1 focus:ring-1"/>
                        {{ tag.label }}
                    </label>
                    <label>
                        Status:
                        <select v-model="status" class="py-0 border border-gray-300 rounded text-xs">
                            <option value="all">All</option>
                            <option value="failed">Failed</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="created">Created</option>
                            <option value="draft">Draft</option>
                        </select>
                    </label>
                    <label>
                        Type:
                        <select v-model="type" class="py-0 border border-gray-300 rounded text-xs">
                            <option value="all">All</option>
                            <option value="1">Gang Sheet</option>
                            <option value="2">Sticker</option>
                            <option value="6">Rolling Gang Sheet</option>
                        </select>
                    </label>
                    <label>
                        Method:
                        <select v-model="generation_method" class="py-0 border border-gray-300 rounded text-xs">
                            <option value="">Any</option>
                            <option value="inkscape">Inkscape</option>
                            <option value="dusk">Dusk</option>
                            <option value="imagick">Imagick</option>
                        </select>
                    </label>
                </div>
            </div>
        </template>

        <div class="flex flex-col h-full text-xs">
            <div class="border divide-y divide-gray-200 rounded-md">
                <table class="min-w-full divide-y divide-gray-200 0 rounded">
                    <thead class="bg-gray-50 font-bold">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Design ID/File Name</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">USER</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Size</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Tags</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Created At</th>
                        <th scope="col" class="px-4 py-2 text-start text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    <tr v-for="design in designs.data" :key="design.id" @click="selectedDesign = design" :class="{'bg-gray-200':selectedDesign?.id === design.id}">
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            <div class="font-medium flex bg-gray-100 w-max pl-2 rounded">
                                {{ design.id }}
                                <div
                                    v-tooltip:top="'Copy Design ID'"
                                    @click="handleDesignIdCopy(design.id)"
                                    class="flex items-center justify-center mx-1 rounded cursor-pointer"
                                >
                                    <svg-icon v-if="copiedDesignId === design.id" type="mdi" :path="mdiCheck" size="14"/>
                                    <svg-icon v-else type="mdi" :path="mdiContentCopy" size="14"/>
                                </div>
                            </div>
                            <div class="max-w-xs truncate px-1">
                                {{ design.file_name }}
                            </div>
                        </td>
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            <div v-if="design.user" class="flex items-center">
                                <div class="flex items-center w-max mr-2">
                                    [{{ design.user.id }}]
                                    <div class="mx-1 px-0.5"
                                         :class="{'bg-green-300': design.user.type === 'woo', 'bg-blue-300': design.user.type === 'custom', 'bg-gray-300': design.user.type === 'normal'}">
                                        {{ design.user.type }}
                                    </div>
                                    {{ design.user.company_name }}
                                </div>
                                <div
                                    v-tooltip:top="'Copy User ID'"
                                    @click="handleShopNameCopy(design.user.id)"
                                    class="w-5 h-6 flex items-center justify-center rounded cursor-pointer"
                                >
                                    <svg-icon v-if="copiedShopName === design.user.id" type="mdi" :path="mdiCheck" size="16"/>
                                    <svg-icon v-else type="mdi" :path="mdiContentCopy" size="16"/>
                                </div>
                            </div>
                            <div v-if="design.user">
                                <small>{{ design.user.website }}</small>
                            </div>
                        </td>
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            <div v-if="design.customer" class="flex flex-col">
                                <span>{{ design.customer.name }}</span>
                                <small class="text-gray-500">{{ design.customer.email }}</small>
                            </div>
                            <div v-else-if="design.order" class="flex flex-col">
                                <span>{{ design.order?.name }}</span>
                                <small class="text-gray-500">{{ design.order.email }}</small>
                                <small v-if="design.order.phone" class="text-gray-500">{{ design.order.phone }}</small>
                            </div>
                        </td>
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            <template v-if="design.order">
                                <div v-if="design.order.wc_order_id" class="font-medium bg-gray-200 w-max px-2 rounded-lg">
                                    WC ID: {{ design.order.wc_order_id }}
                                </div>
                                <div class="px-1">{{ design.order.id }}</div>
                            </template>
                        </td>
                        <td class="px-2 whitespace-nowrap text-gray-800">
                            <div v-if="design.data.meta?.variant">
                                {{ design.data.meta?.variant.width }} {{ design.data.meta?.variant.unit }} x {{ design.data.meta?.variant.height }} {{ design.data.meta?.variant.unit }}
                                [{{ design.quantity }}]
                            </div>
                            <div v-if="getDesignMeta(design, 'actual_height')" class="font-medium bg-gray-200 w-max px-2 mt-0.5 rounded">
                                {{ getActualHeight(design, design.data.meta?.variant?.unit) }}
                            </div>
                        </td>
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            <design-status :design="design" class="text-xs !justify-start"/>
                            <div v-if="getGenerationMethod(design)"
                                 class="text-xs bg-green-300 w-max mt-px h-4 flex items-center justify-center bottom-0 rounded-full px-2">
                                <b>{{ getGenerationMethod(design) }}</b>
                                <span v-if="getDesignMeta(design, 'generation_time')">
                                    [{{ getDesignMeta(design, 'generation_time') }}s|{{ getDesignMeta(design, 'completed_time') }}s]
                                </span>
                            </div>
                        </td>
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            <design-type :design="design" class="text-xs"/>
                        </td>
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            <div class="flex flex-col space-y-px">
                                <div v-if="getDesignMeta(design, 'has_svg')" class="bg-amber-300 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has SVG
                                </div>
                                <div v-if="getDesignMeta(design, 'has_gallery_images')" class="bg-emerald-300 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has Gallery Images
                                </div>
                                <div v-if="getDesignMeta(design, 'has_text')" class="bg-sky-300 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has Text
                                </div>
                                <div v-if="getDesignMeta(design, 'has_multi_line_text')" class="bg-sky-300 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has ML Text
                                </div>
                                <div v-if="getDesignMeta(design, 'has_overlay_color')" class="bg-purple-300 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has Overlay Color
                                </div>
                                <div v-if="getDesignMeta(design, 'has_large_image')" class="bg-purple-300 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has Large Image
                                </div>
                                <div v-if="getDesignMeta(design, 'has_single_svg')" class="bg-amber-500 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has Single SVG
                                </div>
                                <div v-if="getDesignMeta(design, 'has_file_name')" class="capitalize bg-pink-300 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has File Name - {{ getDesignMeta(design, "has_file_name") }}
                                </div>
                                <div v-if="getDesignMeta(design, 'has_problem')" class="capitalize bg-red-500 text-white w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has Problem
                                </div>
                                <div v-if="getDesignMeta(design, 'memo')" class="capitalize bg-gray-200 text-gray-500 w-max h-4 flex items-center justify-center rounded-full px-2">
                                    Has Memo
                                </div>
                                <div v-if="getDesignMeta(design, 'dropbox_failed')"
                                     class="capitalize bg-red-300 w-max h-4 flex items-center justify-center bottom-0 rounded-full px-2">
                                    Dropbox Failed
                                </div>
                            </div>
                        </td>
                        <td class="px-4 whitespace-nowrap text-gray-800">
                            {{ $filters.formatDateTime(design.created_at) }}
                            <div v-if="design.order">
                                {{ moment(design.order.created_at).format('MM/DD-HH:mm') }}
                                [{{ moment(design.order.created_at).diff(moment(design.created_at), 'days') }}]
                            </div>
                        </td>
                        <td class="px-2 whitespace-nowrap text-gray-800 w-28">
                            <div class="flex flex-wrap">
                                <Menu as="div" class="relative inline-block text-left w-6 h-6 border border-primary rounded-full text-primary mx-1 my-px">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <menu-button>
                                            <svg-icon type="mdi" :path="mdiRefresh" size="16"/>
                                        </menu-button>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <menu-items
                                            class="absolute right-0 z-50 mt-1 origin-top-right rounded-md bg-builder shadow-lg ring-1 ring-black bg-white ring-opacity-5 focus:outline-none">
                                            <div class="py-1 overflow-y-auto tiny-scroll-bar z-50">
                                                <menu-item>
                                                    <div class="cursor-pointer block px-4 py-2 text-sm text-gray-700 hover:bg-primary hover:text-white"
                                                         @click="handleReBuildDesign(design, 'inkscape')">
                                                        Inkscape
                                                    </div>
                                                </menu-item>
                                                <menu-item>
                                                    <div class="cursor-pointer block px-4 py-2 text-sm text-gray-700 hover:bg-primary hover:text-white"
                                                         @click="handleReBuildDesign(design, 'dusk')">
                                                        Dusk
                                                    </div>
                                                </menu-item>
                                                <menu-item>
                                                    <div class="cursor-pointer block px-4 py-2 text-sm text-gray-700 hover:bg-primary hover:text-white"
                                                         @click="handleReBuildDesign(design, 'imagick')">
                                                        Imagick
                                                    </div>
                                                </menu-item>
                                            </div>
                                        </menu-items>
                                    </transition>
                                </Menu>
                                <a :href="route('merchant.order.design.edit', { design: design })" target="_blank"
                                   class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white"
                                >
                                    <svg-icon type="mdi" :path="mdiPencil" size="16"/>
                                </a>
                                <a :href="route('admin.design.open', { design_id: design.id })" target="_blank"
                                   class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary mx-1 hover:text-white"
                                >
                                    <svg-icon type="mdi" :path="mdiDownload" size="16"/>
                                </a>
                                <a :href="route('admin.design.preview', {design_id: design.id})" target="_blank"
                                   class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white mx-1 my-px">
                                    <svg-icon type="mdi" :path="mdiImage" size="16"/>
                                </a>
                                <button @click="handleAddNoteClick(design)"
                                        class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white my-px">
                                    <svg-icon type="mdi" :path="mdiNotePlusOutline" size="16"/>
                                </button>
                                <button @click="handleViewLogClick(design)"
                                        class="w-6 h-6 flex justify-center items-center border border-primary rounded-full text-primary hover:bg-primary hover:text-white mx-1 my-px">
                                    <svg-icon type="mdi" :path="mdiNote" size="16"/>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="designs.data.length === 0">
                        <td colspan="9" class="px-4 py-2 whitespace-nowrap text-gray-400 h-40 text-center">
                            No Designs Found
                        </td>
                    </tr>
                    </tbody>
                </table>
                <pagination :data="designs" :per-page="perPage"/>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {router} from "@inertiajs/vue3";

import {
    mdiDownload,
    mdiPencil,
    mdiRefresh,
    mdiCheck,
    mdiContentCopy,
    mdiImage,
    mdiNotePlusOutline,
    mdiNote
} from "@mdi/js";
import GbsSelect from "@/Components/Select.vue";
import Pagination from "@/Components/Pagination.vue";
import DesignStatus from '@/Components/DesignStatus.vue'
import DesignType from '@/Components/DesignType.vue'

import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import moment from "moment";
import AddDesignNoteModal from "@/Components/Modals/AddDesignNoteModal.vue";
import ViewDesignLogModal from "@/Components/Modals/ViewDesignLogModal.vue";
import { convertDimension } from "@/Builder/Utils/helpers";

export default {
    components: {
        AddDesignNoteModal,
        ViewDesignLogModal,
        MenuItems,
        MenuItem,
        MenuButton,
        Menu,
        DesignStatus,
        DesignType,
        Pagination,
        GbsSelect,
        AdminLayout,
        SvgIcon,
    },
    props: {
        filter: {
            type: Object,
            required: true,
        },
        designs: {
            type: Object,
            required: true,
        },
        users: {
            type: Array,
            required: true,
        },
        perPage: {
            type: Number,
            default: 10,
        },
    },
    data() {
        return {
            user: null,
            order_id: this.$props.filter.order_id ?? "",
            customer: this.$props.filter.customer ?? "",
            design_id: this.$props.filter.design_id ?? "",
            tags: this.$props.filter.tags ?? [],
            generation_method: this.$props.filter.generation_method ?? '',
            status: this.$props.filter.status ?? 'all',
            type: this.$props.filter.type ?? 'all',
            copiedShopName: null,
            copiedDesignId: null,
            selectedDesign: null,
            openAddNoteModal: false,
            openViewLogModal: false,
            mdiDownload,
            mdiPencil,
            mdiRefresh,
            mdiContentCopy,
            mdiCheck,
            mdiImage,
            mdiNotePlusOutline,
            mdiNote,
            tagOptions: [
                {value: "has_text", label: "Has Text"},
                {value: "has_multi_line_text", label: "Has ML Text"},
                {value: "has_overlay_color", label: "Has Overlay Color"},
                {value: "has_svg", label: "Has SVG"},
                {value: "has_gallery_images", label: "Has Gallery Image"},
                {value: "has_file_name", label: "Has File Name"},
                {value: "has_large_image", label: "Has Large Image"},
                {value: "has_single_svg", label: "Has Single Svg"},
                {value: "has_problem", label: "Has Problem"},
                {value: "memo", label: "Has Memo"},
                {value: "dropbox_failed", label: "Dropbox Failed"},
            ]
        };
    },
    mounted() {
        this.user = this.users.find((u) => u.id == this.filter.user_id)
    },
    methods: {
        moment,
        handleReBuildDesign(design, method) {
            if (method === 'inkscape' && this.getDesignMeta(design, 'disable_inkscape')) {
                return
            }
            design.status = "processing";
            axios.post(route("admin.design.rebuild", {design_id: design.id, method: method}))
                .then((res) => {
                    design.meta_data = res.data.meta_data;
                });
        },
        getDesignMeta(design, key) {
            return design.meta_data?.find(m => m.key === key)?.value;
        },
        getGenerationMethod(design) {
            return this.getDesignMeta(design, 'generation_method')?.substring(0, 3).toUpperCase()
        },
        getGenerationStart(design) {
            if (this.getDesignMeta(design, 'generation_start')) {
                return moment(this.getDesignMeta(design, 'generation_start') * 1000).format('MM/DD-HH:mm')
            }
            return null;
        },
        getActualHeight(design, unit = 'inch') {
            let actualHeight = this.getDesignMeta(design, 'actual_height')

            actualHeight = convertDimension(actualHeight, 'px', unit)

            return `${actualHeight} ${unit}`;
        },
        handleShopNameCopy(shopName) {
            navigator.clipboard.writeText(shopName).then(() => {
                this.copiedShopName = shopName;
                setTimeout(() => this.copiedShopName = null, 1000);
            });
        },
        handleDesignIdCopy(designId) {
            navigator.clipboard.writeText(designId).then(() => {
                this.copiedDesignId = designId;
                setTimeout(() => this.copiedDesignId = null, 1000);
            });
        },
        handleSearchClick() {
            if (this.design_id) {
                router.get(route("admin.design.index", {id: this.design_id}));
                return;
            }

            const url = new URL(window.location.href);
            const params = {
                id: null,
                user_id: this.user?.id,
                order_id: this.order_id,
                customer: this.customer,
                status: this.status,
                type: this.type,
                generation_method: this.generation_method,
                tags: this.tags.length ? this.tags.join(",") : null,
            };

            Object.entries(params).forEach(([key, value]) => {
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });

            url.searchParams.set("page", 1);
            router.get(url.toString());
        },
        handleClearFilter() {
            router.get(route("admin.design.index"));
        },
        handleAddNoteClick(design) {
            this.openAddNoteModal = true
            this.selectedDesign = design
        },
        handleViewLogClick(design) {
            this.openViewLogModal = true
            this.selectedDesign = design
        },
    }
};
</script>
