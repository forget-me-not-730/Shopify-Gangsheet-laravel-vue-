<template>
    <MerchantLayout title="Dashboard">
        <div>
            <div v-if="$page.props.auth.user.type === 'custom'" class="shadow rounded-lg bg-white">
                <dl class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0">
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Total Designs</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.total_orders }}
                        </dd>
                    </div>
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Month Designs</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.month_orders }}
                        </dd>
                    </div>
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 lg:border-l">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Today Designs</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.today_orders }}
                        </dd>
                    </div>
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Available Credits</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.credits }}
                        </dd>
                    </div>
                </dl>
            </div>
            <div v-else class="shadow rounded-lg bg-white">
                <dl class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0">
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Total Orders</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.total_orders }}
                        </dd>
                    </div>
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Month Orders</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.month_orders }}
                        </dd>
                    </div>
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 lg:border-l">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Today Orders</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.today_orders }}
                        </dd>
                    </div>
                    <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                        <dt class="text-sm font-medium leading-6 text-gray-500">Available Credits</dt>
                        <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                            {{ stats.credits }}
                        </dd>
                    </div>
                </dl>
            </div>
            <div v-if="newOrders" class="shadow p-3 h-[600px] flex justify-center rounded-lg bg-white mt-4">
                <Bar :data="{labels: newOrders?.labels, datasets:[{label: dailyGangSheetLabels, backgroundColor:['#dcfce7'], data:newOrders?.data}]}" :options="{responsive:true, maintainAspectRatio: false}"/>
            </div>
        </div>
    </MerchantLayout>
</template>

<script>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import {Bar} from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js'

export default {
    name: 'Dashboard',
    components: {MerchantLayout, Bar},
    props: {
        stats: Object
    },
    setup() {
        ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)
    },
    mounted() {
        this.loadData()
    },
    data() {
        return {
            newOrders: null
        }
    },
    computed: {
        dailyGangSheetLabels() {
            if (this.$page.props.auth.user.type === 'custom') {
                return 'Gang Sheet Designs'
            } else {
                return 'Gang Sheet Orders'
            }
        },
    },
    methods: {
        loadData() {
            axios.get(route('merchant.dashboard.data'))
                .then((res) => {
                    this.newOrders = res.data.newOrders
                })
                .catch(error => {
                    console.log(error)
                })
        }
    }
}
</script>

<style scoped>

</style>
