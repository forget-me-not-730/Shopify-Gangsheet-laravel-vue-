<script>
import {defineComponent} from 'vue'
import {router} from '@inertiajs/vue3'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiChevronDoubleRight, mdiChevronDoubleLeft} from '@mdi/js'

export default defineComponent({
    name: 'Pagination',
    components: {
        SvgIcon
    },
    props: {
        data: {
            type: Object,
            required: true,
        }
    },
    data() {
        return {
            mdiChevronDoubleRight,
            mdiChevronDoubleLeft
        }
    },
    methods: {
        handlePageClick(url) {
            router.get(url)
        },
    }
})
</script>

<template>
    <div class="py-2 px-4 flex items-center justify-between">
        <nav class="flex items-center space-x-1">
            <button
                type="button"
                class="h-6 w-6 inline-flex justify-center items-center gap-x-2 text-sm rounded text-gray-800 hover:bg-gray-200"
                @click="handlePageClick(data.first_page_url)"
            >
                <svg-icon type="mdi" :path="mdiChevronDoubleLeft" size="16" class="text-gray-500"/>
            </button>

            <button
                v-for="link in data.links.slice(1, Math.min(11, data.links.length - 1))" type="button"
                class="h-6 w-max px-2 flex justify-center items-center text-gray-800 hover:bg-gray-200 text-sm rounded"
                :class="{'bg-green-500 text-white hover:text-gray-500':link.active}"
                @click="handlePageClick(link.url)"
            >
                {{ link.label }}
            </button>

            <button
                type="button"
                class="h-6 w-6 inline-flex justify-center items-center text-sm rounded text-gray-800 hover:bg-gray-200"
                @click="handlePageClick(data.last_page_url)"
            >
                <svg-icon type="mdi" :path="mdiChevronDoubleRight" size="16" class="text-gray-500"/>
            </button>
        </nav>
        <div>
            Showing {{ data.from }} - {{ data.to }} of {{ data.total }}
        </div>
    </div>
</template>

<style scoped>

</style>
