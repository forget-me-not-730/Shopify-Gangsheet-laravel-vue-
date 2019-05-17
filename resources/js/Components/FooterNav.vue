<script setup>
import Logo from '@/Components/Logo.vue'
import {Link} from '@inertiajs/vue3'

const navigation = {
    solutions: [
        {name: 'Home', href: route('home')},
        {name: 'Demo Store', href: 'https://dtf-gsb-demo-store.myshopify.com'},
    ]
}
</script>

<template>
    <footer class="bg-[#160C22]">
        <div class="mx-auto max-w-8xl px-6 pb-8 pt-16 sm:pt-16 lg:px-8 lg:pt-16">
            <div class="xl:grid xl:grid-cols-5 xl:gap-8">
                <div class="space-y-8 xl:col-span-2">
                    <Logo class="invert"/>
                    <img src="/assets/images/usa.png" alt="USA" class="w-[102px] h-auto">
                </div>
                <div class="mt-6 md:grid md:grid-cols-3 md:gap-8 xl:col-span-3">
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-white">Build A Gang Sheet</h3>
                        <ul role="list" class="mt-6 space-y-2">
                            <li v-for="item in navigation.solutions" :key="item.name">
                                <Link :href="item.href" class="text-sm leading-6 text-gray-200 hover:text-primary">{{ item.name }}</Link>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-10 md:mt-0">
                        <h3 class="text-sm font-semibold leading-6 text-white">Contact</h3>
                        <div role="list" class="mt-6 space-y-2">
                            <p class="text-sm leading-6 text-gray-200">Phone: <a href="tel:+14046479762" class="hover:text-primary">1-404-647-9762</a></p>
                            <p class="text-sm leading-6 text-gray-200">Email: <a href="mailto:justin@thedripapps.com" class="hover:text-primary">justin@thedripapps.com</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full text-gray-400 text-center py-6 mt-16 sm:mt-20 bg-[#0E0716]">
            &copy; {{ (new Date()).getFullYear() }} BAGS. All rights reserved.
        </div>
    </footer>
</template>
