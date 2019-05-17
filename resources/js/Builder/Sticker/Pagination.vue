<script>
import { defineComponent } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiChevronLeft, mdiChevronRight, mdiChevronDoubleLeft, mdiChevronDoubleRight } from '@mdi/js'

export default defineComponent({
    name: 'Pagination',
    components: { SvgIcon },
    props: {
        data: {
            type: Object,
            required: true
        },
        perPage: {
            type: [Number, String],
            default: 10
        }
    },
    data () {
        return {
            mdiChevronLeft,
            mdiChevronRight,
            mdiChevronDoubleLeft,
            mdiChevronDoubleRight
        }
    },
    computed: {
        pages () {
            let pages = []
            for (let link of this.data.links) {
                if (link.url) {
                    let url = new URL(link.url)
                    link.page = url.searchParams.get('page')
                }
                pages.push(link)
            }
            return pages
        }
    }
})
</script>

<template>
    <div class="w-full">
        <table class="max-sm:hidden border-collapse mx-auto">
            <tr>
                <td
                    v-for="page in pages"
                    @click="$emit('page', page.page)"
                    class="cursor-pointer px-4 py-2 text-sm border"
                    :class="page.active ? 'bg-primary text-white border-primary font-semibold': 'text-gray-900 hover:bg-gray-50'"
                >
                    <svg-icon v-if="page.label.includes('Previous')" type="mdi" :path="mdiChevronLeft" size="18"/>
                    <svg-icon v-else-if="page.label.includes('Next')" type="mdi" :path="mdiChevronRight" size="18"/>
                    <span v-else>{{ page.label }}</span>
                </td>
            </tr>
        </table>
        <div class="sm:hidden w-full flex items-center justify-between relative py-2">
            <div class="flex items-center space-x-1 text-xs">
                <select :value="perPage" @change="$emit('perPage', $event.target.value)" class="select-primary rounded text-xs h-[35px] py-0 border-gray-300 w-18px">
                    <option :value="9">9</option>
                    <option :value="12">12</option>
                    <option :value="24">24</option>
                    <option :value="48">48</option>
                </select>

                <span v-if="data.total">
                {{ data.from }} - {{ data.to }} of {{ data.total }}
            </span>
            </div>
            <div class="flex items-center h-8 bg-gray-50 rounded overflow-hidden border border-gray-300">
                <button @click="$emit('page', 1)" :disabled="data.current_page === 1"
                        class="h-full aspect-square flex items-center justify-center border-r border-r-gray-300 hover:bg-gray-100 cursor-pointer disabled:text-gray-200">
                    <svg-icon type="mdi" :path="mdiChevronDoubleLeft" size="18"/>
                </button>
                <button @click="$emit('page', data.current_page - 1)" :disabled="!data.prev_page_url"
                        class="h-full aspect-square flex items-center justify-center border-r border-r-gray-300 hover:bg-gray-100 cursor-pointer disabled:text-gray-200">
                    <svg-icon type="mdi" :path="mdiChevronLeft" size="18"/>
                </button>
                <button @click="$emit('page', data.current_page + 1)" :disabled="!data.next_page_url"
                        class="h-full aspect-square flex items-center justify-center border-r border-r-gray-300 hover:bg-gray-100 cursor-pointer disabled:text-gray-200">
                    <svg-icon type="mdi" :path="mdiChevronRight" size="18"/>
                </button>
                <button @click="$emit('page', data.last_page)" :disabled="data.current_page === data.last_page"
                        class="h-full aspect-square flex items-center justify-center hover:bg-gray-100 cursor-pointer disabled:text-gray-200">
                    <svg-icon type="mdi" :path="mdiChevronDoubleRight" size="18"/>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
