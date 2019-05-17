<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'bg-gray-200 text-gray-900 group flex items-center px-2 py-2 text-sm font-semibold rounded-md'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md';
});
</script>

<template>
    <Link :href="href" class="items-center" :class="classes">
        <slot />
    </Link>
</template>
