<script setup>
import {computed} from "vue";

const props = defineProps({
    modelValue: {
        type: [String, Boolean, Object, Array, null, undefined, Number],
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const value = computed({
    get: () => Boolean(props.modelValue),
    set: (value) => {
        if (!props.disabled) {
            emit('update:modelValue', value)
        }
    }
})

</script>

<template>
    <label :class="['relative inline-flex items-center', disabled ? 'cursor-not-allowed opacity-30' : 'cursor-pointer']">
        <input type="checkbox" v-model="value" :checked="value" :disabled="disabled" class="sr-only peer">
        <div
            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-px peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-builder after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all  peer-checked:gs-bg-primary"></div>
    </label>
</template>
