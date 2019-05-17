<script>
import {defineComponent} from 'vue'
import Pagination from "@/Woo/Components/Pagination.vue";
import WooDesignStatus from "@/Woo/Components/WooDesignStatus.vue";
import Spinner from "@/Components/Spinner.vue";
import {getShopPayments} from "@/Woo/Apis/gsbApi";

export default defineComponent({
	name: "PaymentsPage",
	components: {WooDesignStatus, Pagination, Spinner},
	data() {
		return {
			transactions: [],
			loading: true,
			currentPage: 1,
			total: 0,
			links: [],
			search: ''
		}
	},
	mounted() {
		this.loadPayments()
	},
	methods: {
		loadPayments() {
			this.loading = true;
			getShopPayments().then(res => {
				if (res.data.success) {
					this.transactions = res.data.transactions
					this.links = res.data.links
					this.total = res.data.total
				}
				this.loading = false
			})
		}
	}
})
</script>

<template>
	<div class="p-5">
		<div class="overflow-x-auto">
			<table class="w-full border-collapse border text-center">
				<thead class="bg-primary-tbl-header">
				<tr class="border-b border-t">
					<td class="text-center p-2"># ID</td>
					<td class="text-center p-2">Transaction ID</td>
					<td class="text-center p-2">Amount</td>
					<td class="text-center p-2">Status</td>
					<td class="text-center p-2">Created At</td>
				</tr>
				</thead>
				<tbody>
				<tr v-if="loading">
					<td colspan="5" class="py-12">
						<div class="flex items-center justify-center">
							<spinner class="w-6 h-6"/>
						</div>
					</td>
				</tr>
				<tr v-else v-for="transaction of transactions" class="border-b border-t hover:bg-gray-100 cursor-pointer">
					<td class="text-center p-2">{{ transaction.id }}</td>
					<td class="text-center p-2">{{ transaction.transaction_id }}</td>
					<td class="text-center p-2">${{ transaction.amount }}</td>
					<td class="text-center p-2">{{ transaction.status }}</td>
					<td class="text-center p-2">{{ new Date(transaction.created_at).toLocaleDateString() }}</td>
				</tr>
				<tr v-if="!loading && transactions.length === 0">
					<td colspan="5" class="py-12">
						<div class="flex items-center justify-center text-gray-300">
							No transactions
						</div>
					</td>
				</tr>
				</tbody>
			</table>
			<div class="w-full my-2">
				<Pagination :links="links"/>
			</div>
		</div>
	</div>
</template>

<style scoped>

</style>
