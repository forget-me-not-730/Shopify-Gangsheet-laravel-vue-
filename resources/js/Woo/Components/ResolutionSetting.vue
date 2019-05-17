<script>
import {defineComponent} from 'vue'
import {Disclosure, DisclosureButton, DisclosurePanel} from "@headlessui/vue";

export default defineComponent({
	name: "ResolutionSetting",
	components: {DisclosurePanel, DisclosureButton, Disclosure}
})
</script>

<template>
	<Disclosure :default-open="true" v-slot="{ open }">
		<DisclosureButton class="w-full border-b items-center justify-between flex border-gray-200 font-semibold text-gray-700 py-2 px-4 relative cursor-pointer">
			<span><i class="mdi mdi-layers-outline mr-2"></i>Resolution Setting</span>
			<i class="mdi" :class="[open ? 'mdi-chevron-up': 'mdi-chevron-down']"></i>
		</DisclosureButton>
		<DisclosurePanel class="text-gray-500 p-4 border-b">
			Coming Soon...
		</DisclosurePanel>
	</Disclosure>
</template>

<style scoped>

</style>
