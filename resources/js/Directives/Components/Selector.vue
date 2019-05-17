<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "Selector",
    props: {
        options: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            viewAll: false
        }
    },
    computed: {
        filteredOptions() {
            if (!this.search) {
                return this.options
            }
            return this.options.filter(option => option.toLowerCase().includes(this.search.toLowerCase()))
        },
        visibleOptions() {
            return this.viewAll ? this.filteredOptions : this.filteredOptions.slice(0, 5)
        }
    },
    methods: {
        handleOptionClick(value) {
            this.onSelect(value)
        }
    }
})
</script>

<template>
    <div v-show="filteredOptions.length" class="w-full bg-white shadow border rounded-lg">
        <div class="w-full overflow-y-auto max-h-[400px]">
            <div v-for="option in visibleOptions" class="cursor-pointer p-2 hover:bg-gray-200" @click="handleOptionClick(option)">
                {{ option }}
            </div>
        </div>
        <template v-if="filteredOptions.length > 5">
            <hr/>
            <div class="cursor-pointer text-xs text-blue-500 hover:underline p-2" @click.stop.prevent="viewAll = !viewAll">
                {{ viewAll ? 'View less' : 'View all' }}
            </div>
        </template>
    </div>
</template>

<style scoped>

</style>
