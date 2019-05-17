<script setup>
import Logo from '@/Components/Logo.vue'
import SideLink from '@/Components/SideLink.vue'
import { Link } from '@inertiajs/vue3'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiFormatFont } from '@mdi/js'

const props = defineProps({
    show: Boolean,
    toggleSidebar: {
        type: Function,
        default: {}
    },
})
</script>

<template>
    <div class="relative z-10">

        <div class="fixed inset-0 lg:relative lg:flex h-screen" :class="show?'flex':'hidden'">

            <div class="absolute inset-0 bg-gray-600 bg-opacity-75 lg:hidden" v-on:click.prevent="toggleSidebar"></div>

            <div class="flex w-48 flex-col z-10">
                <div class="flex min-h-0 flex-1 flex-col border-r border-gray-200 bg-gray-100">
                    <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
                        <div class="flex flex-shrink-0 items-center px-4">
                            <Logo class="mx-auto cursor-pointer" @click="$inertia.get(route('home'))"/>
                        </div>

                        <nav class="mt-5 flex-1" aria-label="Sidebar">
                            <div class="space-y-1 px-2">
                                <SideLink :href="route('admin.dashboard.index')" :active="route().current('admin.dashboard.index')">
                                    <i class="mdi mdi-home text-2xl mr-1"/>
                                    Dashboard
                                </SideLink>
                                <hr>
                                <SideLink :href="route('admin.merchant.index')" :active="route().current('admin.merchant.*')">
                                    <i class="mdi mdi-store text-2xl mr-1"/>
                                    Merchants
                                </SideLink>
                                <SideLink :href="route('admin.product.index')" :active="route().current('admin.product.index')">
                                    <i class="mdi mdi-cart text-2xl mr-1"/>
                                    Products
                                </SideLink>
                                <SideLink :href="route('admin.order.index')" :active="route().current('admin.order.index')">
                                    <i class="mdi mdi-cart text-2xl mr-1"/>
                                    Orders
                                </SideLink>
                                <SideLink :href="route('admin.design.index')" :active="route().current('admin.design.index')">
                                    <i class="mdi mdi-cart text-2xl mr-1"/>
                                    Designs
                                </SideLink>
                                <SideLink :href="route('admin.font.index')" :active="route().current('admin.font.index')">
                                    <svg-icon type="mdi" :path="mdiFormatFont" class="mr-1"/>
                                    Fonts
                                </SideLink>
                                <SideLink :href="route('admin.transaction.index')" :active="route().current('admin.transaction.index')">
                                    <i class="mdi mdi-cart text-2xl mr-1"/>
                                    Transactions
                                </SideLink>
                                <SideLink :href="route('admin.setting.index')" :active="route().current('admin.setting.index')">
                                    <i class="mdi mdi-cog text-2xl mr-1"/>
                                    Settings
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
                                    <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ $page.props.auth.user.name }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
