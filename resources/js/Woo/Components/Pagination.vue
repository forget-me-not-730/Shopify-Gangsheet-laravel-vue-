<script>
import {defineComponent} from 'vue'

export default defineComponent({
	name: "Pagination",
	props: {
		links: {
			type: Array,
			required: true
		}
	},
	computed: {
		pages() {
			let pages = [];
			for (let link of this.links) {
				if (link.label.includes('Previous')) {
					link.label = '<i class="mdi mdi-chevron-left"></i>';
				} else if (link.label.includes('Next')) {
					link.label = '<i class="mdi mdi-chevron-right"></i>';
				}
				if (link.url) {
					let url = new URL(link.url)
					link.page = url.searchParams.get('page')
				}
				pages.push(link)
			}
			return pages;
		}
	},
	methods: {
		onChangePage(page) {
			this.$emit('change', page.page)
		},
	}
})
</script>

<template>
	<table class="border-collapse mx-auto">
		<tr>
			<td
				v-for="page in pages"
				@click="onChangePage(page)"
				v-html="page.label"
				class="cursor-pointer min-w-[32px] h-8 text-center text-sm border px-2"
				:class="page.active ? 'bg-blue-500 text-white border-blue-500 font-semibold ': 'text-gray-900 hover:bg-gray-50'"
			/>
		</tr>
	</table>
</template>

<style scoped>

</style>
