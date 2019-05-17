<script>
import { defineComponent } from 'vue'
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/vue'
import HomePage from '@/Woo/Pages/HomePage.vue'
import ProductsPage from '@/Woo/Pages/ProductsPage.vue'
import OrdersPage from '@/Woo/Pages/OrdersPage.vue'
import ImagesPage from '@/Woo/Pages/ImagesPage.vue'
import SupportPage from '@/Woo/Pages/SupportPage.vue'
import wooMixin from '@/Woo/WooMixin'
import Spinner from '@/Components/Spinner.vue'
import PaymentsPage from '@/Woo/Pages/PaymentsPage.vue'
import DesignPage from '@/Woo/Pages/DesignPage.vue'

export default defineComponent({
    name: 'AdminApp',
    components: {
        DesignPage,
        PaymentsPage,
        Spinner,
        SupportPage,
        ImagesPage,
        OrdersPage,
        ProductsPage,
        HomePage,
        TabPanel,
        TabPanels,
        Tab,
        TabList,
        TabGroup
    },
    mixins: [wooMixin],
    data() {
        const tabs = [
            { slug: 'home', title: 'Home'},
            { slug: 'products', title: 'Products'},
            { slug: 'orders', title: 'Orders'},
            // { slug: 'designs', title: 'Designs'},
            { slug: 'payments', title: 'Payments'},
            { slug: 'images', title: 'Images'},
            { slug: 'support', title: 'Support'}
        ];
        const params = new URLSearchParams(window.location.search);
        const selectedTabSlug = params.get('tab');
        const selectedTab = tabs.findIndex((p) => p.slug === selectedTabSlug);

        return {
            selectedTab,
            tabs
        }
    },
    methods: {
        selectTab(tab) {
            const searchURL = new URL(window.location);
            searchURL.searchParams.set('page', 'gang-sheet');
            searchURL.searchParams.set('tab', tab?.slug);
            window.history.pushState({}, '', searchURL);
            this.selectedTab = this.tabs.findIndex((p) => p.slug === tab?.slug);
        }
    }
})
</script>

<template>
    <div class="woo-root w-full h-full">
        <div class="rounded bg-white border overflow-hidden min-h-full">
            <TabGroup :selectedIndex="selectedTab">
                <TabList class="flex px-3 space-x-2 items-center">
                    <Tab v-for="tab, index in tabs" as="template" v-slot="{ selected }" :key="index">
                        <div class="pt-3 pb-2 px-2 cursor-pointer" :class="{'font-semibold text-blue-500': selected}">
                            <a @click="selectTab(tab)">
                                {{ tab.title }}
                            </a>
                        </div>
                    </Tab>
                    <div class="w-full flex justify-end">
                        Current Version: {{ gs_version || '-' }}
                    </div>
                </TabList>
                <hr/>
                <TabPanels>
                    <TabPanel>
                        <HomePage/>
                    </TabPanel>
                    <TabPanel>
                        <ProductsPage/>
                    </TabPanel>
                    <TabPanel>
                        <OrdersPage/>
                    </TabPanel>
                    <!-- <TabPanel>
                        <DesignPage/>
                    </TabPanel> -->
                    <TabPanel>
                        <PaymentsPage/>
                    </TabPanel>
                    <TabPanel>
                        <ImagesPage/>
                    </TabPanel>
                    <TabPanel>
                        <SupportPage/>
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>
    </div>
</template>

<style scoped>

</style>
