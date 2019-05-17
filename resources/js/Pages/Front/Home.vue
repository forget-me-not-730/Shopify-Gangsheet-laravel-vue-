<script setup>
import {Head} from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import HatHeadComponent from "@/Components/HatHeadComponent.vue";

</script>

<template>
    <Head title="Home">
        <title>Home</title>
    </Head>

    <AppLayout>
        <div class="bg-[#e5dfeb] relative">
            <img src="/assets/images/hero_1.png" alt="" class="absolute top-0 left-0">
            <img src="/assets/images/hero_2.png" alt="" class="absolute bottom-0 right-0">

            <div class="mx-auto max-w-8xl px-6 py-16 sm:py-32 lg:flex lg:items-center lg:gap-x-10 lg:px-8 lg:py-40 z-10 relative">
                <div class="mx-auto max-w-2xl lg:mx-0 lg:flex-auto">
                    <div class="uppercase py-1 text-primary font-bold text-5xl">
                        Build A Gang Sheet
                    </div>
                    <div class="mt-4">
                        The worlds first fully integrated e-commerce gang sheet builder.
                    </div>
                    <div class="mt-10 flex items-center gap-x-6">
                        <a :href="'https://dtf-gsb-demo-store.myshopify.com/'" target="_blank" class="rounded-full bg-white px-16 py-4 font-erica-one text-2xl uppercase text-primary shadow-xl">
                            Demo Store
                        </a>
                    </div>
                </div>
                <HatHeadComponent/>
            </div>
        </div>
    </AppLayout>
</template>
