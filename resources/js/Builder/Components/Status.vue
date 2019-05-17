<script setup>
const props = defineProps({
    status: {
        type: String,
        required: true
    },
})
</script>

<template>
    <div class="flex w-24 text-xs">
        <div v-if="status === 'draft'" class="w-max flex justify-center border border-gray-200 text-gray-600 bg-gray-200 rounded-lg items-center px-3">
            <div class="mr-1 w-1.5 h-1.5 bg-gray-500 shrink-0 rounded-full"></div>
            <span class="capitalize">Draft</span>
        </div>
        <div v-if="status === 'created'" class="w-max flex justify-center border border-gray-300 text-gray-500 rounded-lg items-center px-3">
            <span class="capitalize">Created</span>
        </div>
        <div v-else-if="status === 'pending'" class="w-max flex justify-center text-yellow-700 bg-yellow-300 rounded-full items-center px-3">
            <span class="capitalize">Pending</span>
        </div>
        <div v-else-if="status === 'processing'" class="w-max flex justify-center border border-success text-success rounded-full items-center px-3">
            <span class="relative flex h-2 w-2 mr-1">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
            </span>
            <span class="capitalize">Processing</span>
        </div>
        <div v-else-if="status === 'completed'"
             class="w-max flex justify-center bg-green-700 text-white rounded-lg items-center px-3">
            <span class="capitalize">Completed</span>
        </div>
        <div v-else-if="status === 'fulfilled'"
             class="w-max flex justify-center bg-green-300 text-green-800 rounded-lg items-center px-3">
            <span class="capitalize">Fulfilled</span>
        </div>
        <div v-else-if="status === 'failed'"
             class="w-max flex justify-center bg-red-700 text-white rounded-full items-center px-3">
            <span class="capitalize">Failed</span>
        </div>
        <div v-else-if="status === 'damaged'"
             class="w-max flex justify-center bg-red-700 text-white rounded-full items-center px-3">
            <span class="capitalize">Damaged</span>
        </div>
    </div>
</template>
