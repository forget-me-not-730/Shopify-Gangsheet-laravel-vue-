<script>
import { defineComponent } from 'vue'
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import confirmationMixin from '@/Mixins/ConfirmationMixin'
import SvgIcon from '@jamescoyle/vue-icon'
import {
    mdiCloseCircleOutline,
    mdiCheckCircleOutline,
    mdiDownload,
    mdiPlus
} from '@mdi/js'
import Pagination from '@/Components/Pagination.vue'
import { router } from '@inertiajs/vue3'
import AddMerchantFontModal from '@/Components/Modals/AddMerchantFontModal.vue'
import { debounce } from 'lodash'

export default defineComponent({
    name: 'FontsPage',
    components: { AddMerchantFontModal, Pagination, MerchantLayout, SvgIcon },
    mixins: [confirmationMixin],
    props: {
        adminFonts: {
            type: Array,
            required: true
        },
        fonts: {
            type: Object,
            required: true
        },
        filters: {
            type: Object,
            required: true
        }
    },
    data () {
        let defaultFontName = this.$page.props.auth.user?.settings?.defaultFont

        if (this.fonts.data.length) {
            defaultFontName = this.fonts.data.find(font => font.name === defaultFontName && font.style === 'normal' && font.weight === 'normal')?.name

            if (!defaultFontName) {
                defaultFontName = this.fonts.data.find(font => font.style === 'normal' && font.weight === 'normal')?.name
            }
        }

        return {
            selectedFonts: [],
            defaultFontName: defaultFontName,

            filter: {
                search: this.filters.search
            },

            openAddFontModal: false,

            mdiCloseCircleOutline,
            mdiCheckCircleOutline,
            mdiDownload,
            mdiPlus
        }
    },
    watch: {
        filter: {
            deep: true,
            handler () {
                debounce(() => {
                    this.reloadFonts()
                }, 750)()
            }
        }
    },
    methods: {
        reloadFonts () {
            this.openAddFontModal = false

            const url = new URL(window.location.href)

            url.searchParams.set('search', this.filter.search || '')

            url.searchParams.set('page', 1)

            router.get(url.toString())
        },
        handleSelectAll (e) {
            if (e.target.checked) {
                this.selectedFonts = this.fonts.data.map(design => design.id)
            } else {
                this.selectedFonts = []
            }
        },
        handleDelete () {
            this.confirmation = {
                title: 'Remove Fonts?',
                description: 'Are you really sure you want to remove selected fonts?',
                onConfirm: async () => {
                    NProgress.start()
                    await axios.post(route('merchant.font.delete'), {
                        ids: this.selectedFonts
                    }).then((res) => {
                        if (res.data.success) {
                            window.Toast.success({
                                message: 'Successfully removed.'
                            })
                            this.selectedFonts = []
                            router.reload({ only: ['fonts'] })
                        } else {
                            window.Toast.error({
                                message: res.data.error
                            })
                        }
                        NProgress.done()
                    })
                }
            }
        },
        handleSetDefaultFont (font) {
            this.confirmation = {
                title: 'Set as Default Font?',
                description: 'Are you really sure you want to set this font as default?',
                onConfirm: async () => {
                    NProgress.start()
                    await axios.post(route('merchant.font.default'), {
                        font: font.name
                    }).then((res) => {
                        if (res.data.success) {
                            window.Toast.success({
                                message: 'Successfully set as default.'
                            })
                            this.defaultFontName = font.name
                        } else {
                            window.Toast.error({
                                message: res.data.error
                            })
                        }
                        NProgress.done()
                    })
                }
            }

        }
    }
})
</script>

<template>
    <merchant-layout title="Fonts">

        <add-merchant-font-modal v-if="openAddFontModal" :fonts="adminFonts" @close="reloadFonts"/>

        <div class="shopify-root w-full min-h-full">
            <div class="rounded-xl bg-white border min-h-full">
                <template v-if="fonts.data.length">
                    <div class="w-full flex justify-between my-2 px-2">
                        <div class="flex items-center space-x-2">
                            <div class="border rounded w-56 transition-all px-1 py-0 flex items-center relative overflow-hidden">
                                <div>
                                    <svg class="text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.3-4.3"/>
                                    </svg>
                                </div>
                                <input v-model="filter.search" class="inp-no-style flex-1 shrink-0 px-1 py-0.5 text-xs" placeholder="Search"/>
                                <div v-if="filter.search" @click="filter.search = ''" class="absolute right-0 px-1 bg-gray-100 text-gray-500 hover:text-gray-600 cursor-pointer">
                                    <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="14"/>
                                </div>
                            </div>
                        </div>
                        <button class="btn-primary" @click="openAddFontModal = true">+ Add Fonts</button>
                    </div>

                    <table class="w-full">
                        <thead>
                        <tr v-if="selectedFonts.length > 0">
                            <th colspan="8" class="p-2 border-t border-b">
                                <div class="w-full flex items-center justify-between">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="selectedFonts.length === fonts.data.length" class="checkbox-primary all mr-2" @change="handleSelectAll"/>
                                        <span class="pt-0.5">{{ selectedFonts.length }} Selected</span>
                                    </label>
                                    <div>
                                        <button class="btn-danger" @click="handleDelete">Delete</button>
                                    </div>
                                </div>
                            </th>
                        </tr>
                        <tr v-else class="bg-gray-100 border-t border-b text-xs text-gray-500">
                            <th class="w-48 text-left px-2 py-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" class="checkbox-primary mr-2" @change="handleSelectAll"/>
                                    <span>Font Name</span>
                                </label>
                            </th>
                            <th class="text-left px-2 py-3">
                                Full Name
                            </th>
                            <th class="text-left px-2 py-3">
                                Weight
                            </th>
                            <th class="text-left px-2 py-3">
                                Style
                            </th>
                            <th class="text-left px-2 py-3">
                                Default Font
                            </th>
                            <th class="text-left px-2 py-3">
                                Download
                            </th>
                            <th class="text-left px-2 py-3">
                            </th>
                        </tr>
                        </thead>
                        <tr v-for="font in fonts.data" :key="font.id" class="border-b hover:bg-gray-100" :class="{'bg-gray-100': selectedFonts.includes(font.id)}">
                            <td class="text-left px-2 py-3">
                                <label class="flex cursor-pointer items-center whitespace-nowrap">
                                    <input :value="font.id" v-model="selectedFonts" type="checkbox" class="checkbox-primary mr-2"/>
                                    <span>{{ font.name }}</span>
                                </label>
                            </td>
                            <td class="text-left px-2 py-3" :style="{fontWeight: font.weight, fontFamily: font.name, fontStyle: font.style}">
                                {{ font.full_name }}
                            </td>
                            <td class="text-left px-2 py-3 capitalize">
                            <span :style="{fontWeight: font.weight}">
                            {{ font.weight }}
                            </span>
                            </td>
                            <td class="text-left px-2 py-3 capitalize">
                            <span :style="{fontStyle: font.style}">
                                {{ font.style }}
                            </span>
                            </td>
                            <td>
                                <template v-if="font.weight === 'normal' && font.style === 'normal'">
                                    <div v-if="font.name === defaultFontName" class="rounded-lg bg-green-200 text-xs text-green-900 w-max px-3 p-0.5 flex items-center">
                                        <svg-icon type="mdi" :path="mdiCheckCircleOutline" size="14" class="mr-1"/>
                                        Default
                                    </div>
                                    <div v-else class="cursor-pointer" @click="handleSetDefaultFont(font)">Set as Default</div>
                                </template>
                            </td>
                            <td class="text-left px-2 py-3">
                                <a :href="font.file_url" download class="text-blue-500 text-xs flex items-center">
                                    <SvgIcon type="mdi" :path="mdiDownload" size="16"/>
                                    Download
                                </a>
                            </td>
                            <td class="px-2 py-3"></td>
                        </tr>
                        <tr v-if="fonts.data.length === 0">
                            <td colspan="6" class="h-40 text-center text-gray-400">
                                No fonts listed
                            </td>
                        </tr>
                    </table>

                    <Pagination :data="fonts"/>
                </template>
                <div v-else class="flex items-center justify-center flex-col p-20">
                    <h3 class="text-base font-bold">Using Custom Fonts</h3>
                    <p class="max-w-md text-center mt-3">You have the option to utilize custom fonts when adding text to the builder. You can either upload your font files or select your preferred
                        fonts
                        from the provided list.</p>

                    <button class="btn-primary mt-6 flex items-center" @click="openAddFontModal = true">
                        <svg-icon type="mdi" :path="mdiPlus" size="14"/>
                        Add Fonts
                    </button>
                </div>
            </div>
        </div>
    </merchant-layout>
</template>

<style scoped>

</style>
