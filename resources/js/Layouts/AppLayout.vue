<script setup>
import FooterNav from "@/Components/FooterNav.vue";
import HeaderNav from "@/Components/HeaderNav.vue";
</script>

<template>
    <div>
        <div class="min-h-screen">
            <HeaderNav />
            <main>
                <slot />
            </main>

            <FooterNav />
        </div>
    </div>
</template>
