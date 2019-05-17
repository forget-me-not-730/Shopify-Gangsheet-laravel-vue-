<template>
    <div>
        <Head :title="title"/>

        <Confirmation/>

        <div class="flex min-h-screen">

            <MerchantSideMenu :show="showingSidebar" :toggleSidebar="toggleSidebar"/>

            <div class="flex min-w-0 flex-1 flex-col max-h-screen overflow-y-auto">

                <MerchantNav :toggleSidebar="toggleSidebar"/>

                <main class="flex-1 h-px bg-gray-50">
                    <div class="p-2 h-full flex flex-col">
                        <div v-if="$slots.header" class="pt-2 md:flex md:items-center md:justify-between md:mx-6">
                            <slot name="header"/>
                        </div>

                        <div class="py-2 flex-1 h-px md:mx-6">
                            <slot/>
                        </div>
                    </div>

                </main>

            </div>
        </div>
    </div>
</template>

<script>

import {Head, usePage} from '@inertiajs/vue3'
import MerchantSideMenu from '@/Components/Layouts/MerchantSideMenu.vue'
import MerchantNav from '@/Components/Layouts/MerchantNav.vue'
import Confirmation from '@/Components/Confirmation.vue'

export default {
    name: 'MerchantLayout',
    components: {Confirmation, Head, MerchantNav, MerchantSideMenu},
    props: {
        title: String
    },
    data() {
        return {
            showingSidebar: false
        }
    },
    methods: {
        toggleSidebar() {
            this.showingSidebar = !this.showingSidebar
        }
    },
    mounted() {
        if (usePage().props.errors.message) {
            Toast.error({
                message: usePage().props.errors.message
            })
        }

        if (this.$page.props.hashtag) {
            window.location.hash = `#${this.$page.props.hashtag}`
        }
    }
}
</script>

<style scoped>

</style>
