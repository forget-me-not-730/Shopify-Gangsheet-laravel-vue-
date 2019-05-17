<script>
import {Head} from "@inertiajs/vue3";
import BuilderLayout from "@/Layouts/BuilderLayout.vue";

export default {
	name: "InvalidProduct",
	components: {BuilderLayout, Head},
	props: {
		merchant: Object
	},
	methods: {
		closeModal() {
			if (window.self === window.top) {
				window.location.href = this.merchant.website;
			} else {
				window.parent.postMessage({
					action: 'gs_close_builder'
				}, '*')
			}
		}
	}
}
</script>

<template>
	<BuilderLayout title="Invalid Product">
		<div class="w-full h-full mx-auto mt-10 bg-white max-w-3xl p-3">
			<div class="bg-orange-100 border-t-4 border-orange-500 text-orange-700 p-5 rounded" role="alert">
				<p class="font-bold">Invalid Product</p>
				<p>This product either does not exist or is inactive.</p>
			</div>
			<button class="flex items-center bg-primary text-white rounded py-2 px-4 max-sm:text-sm max-sm:px-2 mt-8 mx-auto" @click="closeModal">
				Go To Merchant
			</button>
		</div>
	</BuilderLayout>
</template>

<style scoped>

</style>
