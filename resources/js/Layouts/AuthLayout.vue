<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HatHeadComponent from "@/Components/HatHeadComponent.vue";
</script>
<template>
    <AppLayout>
        <div class="bg-[#e5dfeb] relative">
            <img src="/assets/images/hero_1.png" alt="" class="absolute top-0 left-0">
            <img src="/assets/images/hero_2.png" alt="" class="absolute bottom-0 right-0">

            <div class="mx-auto max-w-8xl px-6 py-8 sm:py-32 lg:flex lg:items-center lg:gap-x-10 lg:px-8 lg:py-20 z-10 relative">
                <div class="mx-auto max-w-2xl lg:mx-0 lg:flex-auto">
                    <slot/>
                </div>
                <HatHeadComponent/>
            </div>
        </div>
    </AppLayout>
</template>
