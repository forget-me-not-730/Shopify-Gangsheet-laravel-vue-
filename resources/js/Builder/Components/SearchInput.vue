<script setup>
import {computed} from "vue";
import CloseCircleOutlineIcon from "@/Builder/Icons/CloseCircleOutlineIcon.vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue', 'input'])

const searchText = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value)
    }
})

const handleClear = () => {
    searchText.value = ''
    emit('input', '')
}

</script>

<template>
    <div class="inp-builder py-1 pl-1 relative">
        <div>
            <svg
                class="text-gray-400"
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.3-4.3"/>
            </svg>
        </div>
        <input v-model="searchText" @input="emit('input', $event)" class="inp-no-style flex-1 shrink-0 px-1 py-0 text-xs" placeholder="Search"/>
        <div v-if="searchText" @click="handleClear" class="absolute right-1 hover:text-gray-600 cursor-pointer">
            <close-circle-outline-icon size="16"/>
        </div>
    </div>
</template>
