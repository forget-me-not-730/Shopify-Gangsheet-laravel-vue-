<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {onMounted, ref, watch} from "vue";
import { Bar } from 'vue-chartjs'

import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js'

const props = defineProps(["stats"])
const newMerchants = ref([])
const newOrders = ref([])
console.log(props.stats)
ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const getDashboardData = () => {
    axios.get(route('admin.dashboard.data'))
        .then(function(response) {
            console.log(response.data)
            newMerchants.value = response.data.newMerchants
            newOrders.value = response.data.newOrders
        })
        .catch(error => {
            console.log(error);
        });
}


onMounted( () => {
    getDashboardData()
})
</script>

<template>
    <AdminLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Hello, {{$page.props.auth.user.name}}!
            </h2>
        </template>
        <div class="shadow rounded-lg bg-white">
            <dl class="mx-auto grid max-w-7xl grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 lg:px-2 xl:px-0">
                <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8">
                    <dt class="text-sm font-medium leading-6 text-gray-500">Installed Shops</dt>
                    <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                        {{ stats?.shops }}
                    </dd>
                </div>
                <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                    <dt class="text-sm font-medium leading-6 text-gray-500">Total Orders</dt>
                    <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                        {{ stats?.orders }}
                    </dd>
                </div>
                <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 lg:border-l">
                    <dt class="text-sm font-medium leading-6 text-gray-500">Revenue</dt>
                    <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                        ${{ stats?.revenue }}
                    </dd>
                </div>
                <div class="flex items-baseline flex-wrap justify-between gap-y-2 gap-x-4 border-t border-gray-900/5 px-4 py-10 sm:px-6 lg:border-t-0 xl:px-8 sm:border-l">
                    <dt class="text-sm font-medium leading-6 text-gray-500">Today New Shops</dt>
                    <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">
                        {{ stats?.today_shops }}
                    </dd>
                </div>
            </dl>
        </div>
        <div v-if="newMerchants" class="shadow p-3 h-[500px] flex justify-center rounded-lg bg-white mt-4">
            <Bar :data="{labels: newMerchants?.labels, datasets:[{label:'New Merchants', backgroundColor:['#dbeafe'], data:newMerchants?.data}]}" :options="{responsive:true}" />
        </div>
        <div v-if="newOrders" class="shadow p-3 h-[500px] flex justify-center rounded-lg bg-white mt-4">
            <Bar :data="{labels: newOrders?.labels, datasets:[{label:'Gang Sheet Orders', backgroundColor:['#dcfce7'], data:newOrders?.data}]}" :options="{responsive:true}" />
        </div>
    </AdminLayout>
</template>
