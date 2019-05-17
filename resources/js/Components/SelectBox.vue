<template>
    <select v-model="selected" @change="handleChange" class="w-full rounded-full border-gray-300 focus:border-primary focus:ring-primary shadow-sm px-4">
        <option v-for="option in options" :value="option.value">{{ option.label }}</option>
    </select>
</template>

<script>

export default {
    props: {
        modelValue: [String, Number],
        options: Array
    },
    data() {
        return {
            selected: this.modelValue
        }
    },
    watch: {
        modelValue: function (newVal) {
            this.selected = newVal;
        },
        selected: function (newVal) {
            if (newVal !== this.modelValue) {
                this.$emit('update:modelValue', newVal);
            }
        }
    },
    methods: {
        handleChange(event) {
            this.$emit('change', event.target.value);
        }
    }
}

</script>

<style scoped>

</style>
