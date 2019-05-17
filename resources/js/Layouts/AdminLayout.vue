<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminSideMenu from "@/Components/Layouts/AdminSideMenu.vue";
import AdminNav from "@/Components/Layouts/AdminNav.vue";

defineProps({
    title: String,
});

const showingSidebar = ref(false);
const toggleSidebar = () => {
    showingSidebar.value = ! showingSidebar.value;
}

</script>

<template>
    <div>
        <Head :title="title" />

        <div class="flex min-h-screen">

            <AdminSideMenu :show="showingSidebar" :toggleSidebar="toggleSidebar"/>

            <div class="flex min-w-0 flex-1 flex-col max-h-screen overflow-y-auto">

                <AdminNav :toggleSidebar="toggleSidebar"/>

                <div class="flex flex-1 items-stretch">
                    <main class="flex-1">
                        <div class="p-2 h-full flex flex-col">
                            <div v-if="$slots.header" class="pt-2 md:flex md:items-center md:justify-between md:mx-6">
                                <slot name="header" />
                            </div>
                            <div class="py-2 flex-1 md:mx-6">
                                <slot />
                            </div>
                        </div>
                    </main>
                </div>

            </div>
        </div>
    </div>
</template>
