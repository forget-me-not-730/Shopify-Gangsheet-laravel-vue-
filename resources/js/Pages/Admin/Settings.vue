<template>
    <AdminLayout title="Settings">
        <div class="text-sm font-medium text-center text-gray-500 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px">
                <li v-for="tab in tabs" class="mr-2">
                    <a
                        href="#"
                        @click="onChangeTab(tab)"
                        class="inline-block p-4 border-b-2 border-primary rounded-t-lg hover:text-primary hover:border-primary"
                        :class="currentTab === tab ? 'text-primary border-primary': 'border-transparent'"
                    >
                        {{ tab }}
                    </a>
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
export default {
    name: "Settings",
    components: {AdminLayout, },
    data(){
        return {
            tabs: ['General', 'Prices'],
            currentTab: 'General'
        }
    },
    methods: {
        onChangeTab(tab){
            this.currentTab = tab
        }
    }
}
</script>

<style scoped>

</style>
