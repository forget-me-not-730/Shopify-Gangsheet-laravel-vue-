<script setup>
import {router} from "@inertiajs/vue3";

const props = defineProps({
    toggleSidebar: {
        type: Function,
        default: {}
    },
});
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="flex justify-between items-center py-2 pr-4 bg-white border-b border-gray-200">

        <div class="flex w-full h-[40px] justify-end items-center">

<!--            <Notifications />-->

            <button type="button" class="lg:hidden -mr-3 inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-500 hover:text-gray-900" v-on:click.prevent="toggleSidebar()">
                <span class="sr-only">Open sidebar</span>
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
    </div>
</template>
