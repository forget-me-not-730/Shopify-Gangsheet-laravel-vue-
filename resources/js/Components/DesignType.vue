<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: 'DesignType',
    props: {
        design: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            type: null,
        }
    },
    watch: {
        design: {
            immediate: true,
            deep: true,
            handler() {
                if (this.design) {
                    this.type = this.design.type
                }
            }
        }
    },
})
</script>

<template>
    <div class="w-max flex justify-center border bg-gray-200 text-gray-900 rounded-full items-center px-2 text-xs">
        <div v-if="type === 1">
            <span class="capitalize">Gang Sheet</span>
        </div>
        <div v-else-if="type === 2">
            <span class="capitalize">Sticker</span>
        </div>
        <div v-else-if="type === 6">
            <span class="capitalize">Rolling Gang Sheet</span>
        </div>
    </div>
</template>

<style scoped>

</style>
