<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm text-gray-700 text-left">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
