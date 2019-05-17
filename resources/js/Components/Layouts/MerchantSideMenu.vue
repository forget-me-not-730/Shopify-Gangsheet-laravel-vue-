<script setup>
import Logo from '@/Components/Logo.vue'
import SideLink from '@/Components/SideLink.vue'
import {Link} from '@inertiajs/vue3'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiFormatFont, mdiAccountMultiple} from '@mdi/js'
import {reactive} from 'vue'
import {usePage} from '@inertiajs/vue3'

const props = defineProps({
    show: Boolean,
    toggleSidebar: {
        type: Function,
        default: {}
    },
})

const user = reactive(usePage().props.auth.user)

</script>

<template>
    <div class="relative z-30">

        <div class="fixed inset-0 lg:relative lg:flex h-screen" :class="show?'flex':'hidden'">

            <div class="absolute inset-0 bg-gray-600 bg-opacity-75 lg:hidden" v-on:click.prevent="toggleSidebar"></div>

            <div class="flex w-64 flex-col z-30">
                <div class="flex min-h-0 flex-1 flex-col border-r border-gray-200 bg-gray-100">
                    <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
                        <div class="flex flex-shrink-0 items-center px-4">
                            <Logo class="mx-auto cursor-pointer" @click="$inertia.get(route('home'))"/>
                        </div>
                        <nav class="mt-5 flex-1" aria-label="Sidebar">
                            <div class="space-y-1 px-2">

                                <SideLink :href="route('merchant.dashboard.index')" :active="route().current('merchant.dashboard.index')">
                                    <i class="mdi mdi-home text-2xl mr-1"/>
                                    Dashboard
                                </SideLink>

                                <SideLink v-if="user.type === 'custom' || user.type === 'woo'" :href="route('merchant.dashboard.api')" :active="route().current('merchant.dashboard.api')">
                                    <i class="mdi mdi-developer-board text-2xl mr-1"/>
                                    API Tokens
                                </SideLink>

                                <SideLink v-if="user.type !== 'custom'" :href="route('merchant.product.index')" :active="route().current('merchant.product.index')">
                                    <i class="mdi mdi-package-variant text-2xl mr-1"/>
                                    Products
                                </SideLink>

                                <template v-if="user.type !== 'custom'">
                                    <SideLink :href="route('merchant.order.index')" :active="route().current('merchant.order.index')">
                                        <i class="mdi mdi-cart text-2xl mr-1"/>
                                        Orders
                                    </SideLink>
                                </template>

                                <SideLink :href="route('merchant.design.index')" :active="route().current('merchant.design.index')">
                                    <i class="mdi mdi-alpha-d-box-outline text-2xl mr-1"/>
                                    Designs
                                </SideLink>

                                <SideLink :href="route('merchant.image.index')" :active="route().current('merchant.image.index')">
                                    <i class="mdi mdi-image-multiple text-2xl mr-1"/>
                                    Gallery
                                </SideLink>

                                <SideLink :href="route('merchant.customer.index')" :active="route().current('merchant.customer.index')">
                                    <svg-icon type="mdi" :path="mdiAccountMultiple" class="mr-1"/>
                                    Customers
                                </SideLink>

                                <SideLink :href="route('merchant.font.index')" :active="route().current('merchant.font.index')">
                                    <svg-icon type="mdi" :path="mdiFormatFont" class="mr-1"/>
                                    Fonts
                                </SideLink>

                                <SideLink v-if="user.credits !== -1" :href="route('merchant.payment.index')" :active="route().current('merchant.payment.index')">
                                    <i class="mdi mdi-currency-usd text-2xl mr-1"/>
                                    Payments
                                </SideLink>

                                <SideLink :href="route('merchant.transaction.index')" :active="route().current('merchant.transaction.index')">
                                    <i class="mdi mdi-currency-usd text-2xl mr-1"/>
                                    Transactions
                                </SideLink>

                                <SideLink :href="route('merchant.setting.index')" :active="route().current('merchant.setting.index')">
                                    <i class="mdi mdi-cog text-2xl mr-1"/>
                                    Settings
                                </SideLink>

                                <SideLink :href="route('merchant.support.index')" :active="route().current('merchant.support.index')">
                                    <i class="mdi mdi-help-box text-2xl mr-1"/>
                                    Support Ticket
                                </SideLink>

                                <SideLink :href="route('logout')" @click.prevent="$inertia.post(route('logout'))">
                                    <i class="mdi mdi-logout text-2xl mr-1"/>
                                    Logout
                                </SideLink>

                            </div>
                        </nav>
                    </div>
                    <div class="flex flex-shrink-0 border-t border-gray-200 p-4">
                        <Link href="#" class="group block flex-shrink-0 cursor-pointer">
                            <div class="flex items-center">
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                                        {{ $page.props.auth.user.name }} ( {{ $page.props.auth.user.id }})
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
