<script>
import {defineComponent} from 'vue'
import Modal from '@/Builder/Modals/Modal.vue'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import Spinner from '@/Components/Spinner.vue'
import {getSessionId} from '@/Builder/Utils/helpers'
import eventBus from '@/Builder/Utils/eventBus'
import GsbImage from '@/Builder/Components/GsbImage.vue'
import Variants from '@/Builder/Components/Variants.vue'
import {ART_BOARD_TYPES, MODAL_NAMES} from '@/Builder/Utils/constants'
import {MenuItem} from '@headlessui/vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiChevronLeft, mdiChevronRight, mdiAccountCircleOutline, mdiCheckCircleOutline} from '@mdi/js'
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from '@headlessui/vue'
import CustomerDesigns from '@/Builder/Components/CustomerDesigns.vue'
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import SearchInput from "@/Builder/Components/SearchInput.vue";

export default defineComponent({
    name: 'CustomerDesignsModal',
    components: {
        SearchInput,
        CloseIcon,
        CustomerDesigns,
        MenuItem,
        Variants,
        GsbImage,
        Spinner,
        Modal,
        SvgIcon,
        TabPanel,
        TabPanels,
        Tab,
        TabList,
        TabGroup
    },
    mixins: [gangSheetMixin],
    props: {
        open: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            loading: false,
            links: [],
            total: 0,
            currentPage: 1,
            search: '',
            searchTimer: 0,
            variant_id: 'all',
            selectedTabIndex: 0,

            mdiChevronLeft,
            mdiChevronRight,
            mdiAccountCircleOutline,
            mdiCheckCircleOutline
        }
    },
    watch: {
        variant_id() {
            if (this.open) {
                this.currentPage = 1
                this.getDesigns()
            }
        },
        customer() {
            this.getDesigns()
        }
    },
    computed: {
        classes() {
            return {
                body: '!max-w-5xl'
            }
        },
        pages() {
            let pages = []
            for (let link of this.links) {
                if (link.url) {
                    let url = new URL(link.url)
                    link.page = url.searchParams.get('page')
                }
                pages.push(link)
            }
            return pages
        },
        showVariantSelector() {
            return this.product?.art_board_type !== ART_BOARD_TYPES.ROLLING_GANG_SHEET
        }
    },
    mounted() {
        this.getDesigns()
    },
    methods: {
        onChangePage(page) {
            if (page.page && !this.loading && this.currentPage !== page.page) {
                this.currentPage = page.page
                this.getDesigns()
            }
        },
        async getDesigns() {
            return new Promise((resolve) => {
                this.loading = true

                axios.get(route('builder.customer-designs', {
                    shop_id: this.shop.id,
                    session_id: getSessionId(),
                    customer_id: this.customer?.id || null,
                    product_id: this.product?.id,
                    woo_product_id: this.product?.woo_product_id,
                    variant_id: this.variant_id || this.variant.id,
                    search: this.search,
                    page: this.currentPage,
                    trashed: this.selectedTabIndex === 1 // 1: archived tab
                })).then((res) => {
                    if (res && !res.data.error) {
                        this.customerDesigns = res.data.data
                        this.currentPage = res.data.current_page
                        this.links = res.data.links
                    } else {
                        console.error(res)
                    }
                    resolve(true)
                }).catch((e) => {
                    console.error(e)
                    Toast.error({
                        message: 'Failed to load designs'
                    })
                    resolve(false)
                }).finally(() => {
                    this.loading = false
                })
            })
        },
        handleSearchInput() {
            clearTimeout(this.searchTimer)
            this.searchTimer = setTimeout(() => {
                this.loading = true
                this.getDesigns()
            }, 750)
        },
        handleViewProfile() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.CUSTOMER_PROFILE
            })
        },
        handleSignIn() {
            eventBus.$emit(eventBus.CUSTOMER_LOGIN)
        },
        async onChangeTab(nextIndex) {
            this.selectedTabIndex = nextIndex
            await this.getDesigns()
        }
    }
})
</script>

<template>
    <modal :open="open" :classes="classes">
        <div class="flex flex-col bg-builder border sm:rounded max-sm:h-full sm:max-h-full min-h-[600px]">
            <div class="flex justify-between items-center relative px-4 py-2">
                <div>
                    <template v-if="builderSettings.enableCustomerAccount && !isCustomApi">
                        <template v-if="customer">
                            <div v-if="customer.email" @click="handleViewProfile"
                                 class="text-left text-sm flex cursor-pointer items-center py-2">
                                <svg-icon type="mdi" :path="mdiAccountCircleOutline" size="18" class="mr-2"/>
                                {{ customer.name }}
                            </div>
                        </template>
                        <button v-else class="btn-builder h-7" @click="handleSignIn">
                            {{ $t('Sign In') }}
                        </button>
                    </template>
                </div>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="flex-1 flex flex-col px-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                <TabGroup :selected-index="selectedTabIndex" @change="onChangeTab">
                    <TabList class="flex flex-wrap items-center justify-between">
                        <div class="flex sm:max-w-[300px] w-full my-2 border-b gs-border-primary overflow-hidden rounded-t">
                            <Tab as="template" v-slot="{ selected }">
                                <div class="cursor-pointer flex-1  py-1 whitespace-nowrap p-2" :class="{'text-white rounded-t gs-bg-primary': selected}">
                                    {{ $t('My Designs') }}
                                </div>
                            </Tab>
                            <Tab as="template" v-slot="{ selected }">
                                <div class="cursor-pointer flex-1 py-1" :class="{'text-white rounded-t gs-bg-primary': selected}">
                                    {{ $t('Archived') }}
                                </div>
                            </Tab>
                        </div>
                        <div class="flex max-sm:w-full items-center justify-between">
                            <div v-if="showVariantSelector" class="w-42 h-10 shrink-0 mr-2">
                                <variants v-model="variant_id" :has-all="true" class="h-full"/>
                            </div>
                            <search-input v-model="search" class="w-full h-10" @input="handleSearchInput"/>
                        </div>
                    </TabList>

                    <TabPanels class="my-2 flex-1 h-full relative">
                        <div v-if="loading" class="absolute pointer-events-none top-0 left-0 bg-black bg-opacity-20 h-full w-full flex items-center justify-center">
                            <spinner class="!h-6 !w-6"/>
                        </div>
                        <TabPanel>
                            <customer-designs v-if="!loading" type="available" :customer-designs="customerDesigns" :pages="pages" @reload="getDesigns" @page="onChangePage"/>
                        </TabPanel>
                        <TabPanel>
                            <customer-designs v-if="!loading" type="archived" :customer-designs="customerDesigns" :pages="pages" @reload="getDesigns" @page="onChangePage"/>
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
