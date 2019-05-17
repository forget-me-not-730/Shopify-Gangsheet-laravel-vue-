<script>
import {Head} from "@inertiajs/vue3";
import BuilderLayout from "@/Layouts/BuilderLayout.vue";

export default {
    name: "InvalidMerchant",
    components: {BuilderLayout, Head},
    methods: {
        closeModal() {
			if(window.self === window.top) {
				window.history.back();
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
    <BuilderLayout title="Invalid Merchant">
        <div class="w-full h-full max-w-3xl mx-auto pt-10 bg-white p-3">
            <div class="bg-orange-100 border-t-4 border-orange-500 text-orange-700 p-5 rounded" role="alert">
                <p class="font-bold">Invalid Merchant</p>
                <p>The merchant was not found or is inactive.</p>
            </div>
            <button class="flex items-center bg-primary text-white rounded py-2 px-4 max-sm:text-sm max-sm:px-2 mt-5 mx-auto" @click="closeModal">
                Go Back
            </button>
        </div>
    </BuilderLayout>
</template>

<style scoped>

</style>
