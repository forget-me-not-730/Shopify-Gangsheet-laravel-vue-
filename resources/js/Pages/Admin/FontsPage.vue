<script>
import {defineComponent} from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import Pagination from '@/Components/Pagination.vue'
import AddFontModal from '@/Components/Modals/AddFontModal.vue'
import {mdiDownload, mdiCheckCircleOutline} from '@mdi/js'
import GbsSelect from '@/Components/Select.vue'
import {router, useForm} from '@inertiajs/vue3'
import {debounce} from 'lodash'

export default defineComponent({
    name: 'FontsPage',
    components: {GbsSelect, AddFontModal, Pagination, SvgIcon, AdminLayout},
    props: {
        fonts: {
            type: Object,
            required: true
        },
        defaultFont: {
            type: String,
            default: ''
        },
        filters: {
            type: Object,
            required: true
        }
    },
    data() {
        let defaultFontName = this.defaultFont

        if (this.fonts.data.length) {
            defaultFontName = this.fonts.data.find(font => font.name === defaultFontName)?.name

            if (!defaultFontName) {
                defaultFontName = this.fonts.data[0]?.name
            }
        }

        return {
            openAddModal: false,
            form: useForm({
                font_ids: []
            }),

            defaultFontName: defaultFontName,

            filter: {
                status: this.$props.filters.status,
                perPage: this.$props.filters.perPage,
                search: this.$props.filters.search ?? '',
            },

            mdiDownload,
            mdiCheckCircleOutline
        }
    },
    watch: {
        filter: {
            deep: true,
            handler() {
                debounce(() => {
                    const url = new URL(window.location.href)

                    url.searchParams.set('status', this.filter.status)
                    url.searchParams.set('search', this.filter.search)
                    url.searchParams.set('page', 1)

                    router.get(url.toString())
                }, 500)()
            }
        }
    },
    methods: {
        handleSelectAll(e) {
            if (e.target.checked) {
                this.form.font_ids = this.fonts.data.map(font => font.id)
            } else {
                this.form.font_ids = []
            }
        },
        handleSearch() {

        },
        handleActivate() {
            this.form.post(route('admin.font.activate'), {
                onSuccess: () => {
                    window.Toast.success({
                        message: 'Fonts activated successfully'
                    })
                },
                onError: () => {
                    window.Toast.error({
                        message: 'Error activating fonts'
                    })
                }
            })
        },
        handleInactivate() {
            this.form.post(route('admin.font.inactivate'), {
                onSuccess: () => {
                    this.form.font_ids = []

                    window.Toast.success({
                        message: 'Fonts inactivated successfully'
                    })
                },
                onError: () => {
                    window.Toast.error({
                        message: 'Error inactivating fonts'
                    })
                }
            })
        },
        handleSetDefaultFont(font) {
            if (confirm('Are you sure you want to set this font as default?')) {
                axios.post(route('admin.font.default'), {
                    font: font.name
                }).then(response => {
                    this.defaultFontName = font.name

                    window.Toast.success({
                        message: 'Default font set successfully'
                    })
                }).catch(error => {
                    window.Toast.error({
                        message: 'Error setting default font'
                    })
                })
            }
        },
        handleTypeChange(font) {
            axios.post(route('admin.font.updateType'), {
                name: font.name,
                type: font.types
            }).then(response => {
                window.Toast.success({
                    message: 'Font type updated successfully'
                });
            }).catch(error => {
                window.Toast.error({
                    message: 'Error updating font type'
                });
            })
        }
    }
})
</script>

<template>
    <AdminLayout title="Fonts">
        <div class="shopify-root w-full  min-h-full">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <select v-model="filter.status" class="select-primary py-1 rounded border-gray-400 w-36">
                        <option value="all">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="name_and_number">Name and Number</option>
                    </select>

                    <div class="relative max-w-lg ml-2">
                        <label class="sr-only">Search</label>
                        <input v-model="filter.search" type="text" @input="handleSearch"
                               class="py-1.5 px-3 ps-9 block w-full border-gray-500 shadow-sm rounded text-sm focus:z-10 focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none"
                               placeholder="Search Font">
                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                            <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div>
                    <button @click="openAddModal = true" class="bg-blue-500 text-white px-4 py-2 rounded">
                        Add Font
                    </button>
                </div>
            </div>
            <div class="border rounded overflow-hidden mt-2">
                <table class="w-full">
                    <thead class="bg-gray-50">
                    <tr v-if="form.font_ids.length > 0">
                        <th colspan="7" class="px-2 py-1">
                            <div class="w-full flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox" :checked="form.font_ids.length === this.fonts.data.length" class="checkbox-primary all mr-2" @change="handleSelectAll"/>
                                    <span class="pt-0.5">{{ form.font_ids.length }} Selected</span>
                                </div>
                                <div class="space-x-2">
                                    <button :disabled="form.processing" class="bg-blue-500 text-white px-4 py-2 rounded disabled:bg-gray-300" @click="handleActivate">
                                        Activate
                                    </button>
                                    <button :disabled="form.processing" class="bg-red-500 text-white px-4 py-2 rounded disabled:bg-gray-300" @click="handleInactivate">
                                        Deactivate
                                    </button>
                                </div>
                            </div>
                        </th>
                    </tr>
                    <tr v-else class="text-xs text-gray-500">
                        <th class=" text-left px-2 py-3">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" class="checkbox-primary mr-1" @change="handleSelectAll"/>
                                Name
                            </label>
                        </th>
                        <th class="text-left p-2">
                            Full Name
                        </th>
                        <th class="text-left p-2">
                            Status
                        </th>
                        <th class="text-left p-2">
                            Default
                        </th>
                        <th class="text-left p-2">
                            Weight
                        </th>
                        <th class="text-left p-2">
                            Style
                        </th>
                        <th class="text-left p-2">
                            Type
                        </th>
                    </tr>
                    </thead>
                    <tr v-for="font in fonts.data" :key="font.id" class="border-b hover:bg-gray-100">
                        <td class="p-2 text-left">
                            <label class="flex items-center cursor-pointer">
                                <input :value="font.id" v-model="form.font_ids" type="checkbox" class="mr-2"/>
                                {{ font.name }}
                            </label>
                        </td>
                        <td class="text-left p-2 whitespace-nowrap" :style="{ fontFamily: font.name }">
                            AbBbCc123
                        </td>
                        <td class="text-left p-2">
                            <span v-if="font.status" class="text-green-500">Active</span>
                            <span v-else class="text-red-500">Inactive</span>
                        </td>
                        <td class="text-left p-2">
                            <div v-if="font.name === defaultFontName" class="rounded-full bg-green-200 text-xs text-green-900 w-max px-3 p-0.5 flex items-center">
                                <svg-icon type="mdi" :path="mdiCheckCircleOutline" size="14" class="mr-1"/>
                                Default
                            </div>
                            <div v-else class="cursor-pointer" @click="handleSetDefaultFont(font)">Set as Default</div>
                        </td>
                        <td class="capitalize text-left p-2">
                            {{ font.weights }}
                        </td>
                        <td class="capitalize text-left p-2">
                            {{ font.styles }}
                        </td>
                        <td class="capitalize text-left p-2">
                            <select v-model="font.types" @change="handleTypeChange(font)" class="block w-full rounded-md border bg-gray-50 py-1.5 text-sm px-2">
                                <option value="general">General</option>
                                <option value="name_and_number">Name And Number</option>
                            </select>
                        </td>
                    </tr>
                    <tr v-if="fonts.data.length === 0">
                        <td colspan="6" class="h-40 text-center text-gray-400">
                            No fonts found
                        </td>
                    </tr>
                </table>
                <div class="w-full bg-gray-50 py-1">
                    <Pagination :data="fonts"/>
                </div>
            </div>
        </div>

        <AddFontModal v-if="openAddModal" @close="openAddModal = false"/>
    </AdminLayout>
</template>

<style scoped>

</style>
