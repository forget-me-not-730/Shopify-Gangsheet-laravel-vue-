<template>
    <MerchantLayout title="Settings">
        <div class="text-sm font-medium text-center text-gray-500 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px">
                <li v-for="tab in tabs" class="mr-2">
                    <a
                        href="#"
                        @click="onChangeTab(tab)"
                        class="inline-block p-4 border-b-2 border-primary rounded-t-lg hover:text-primary hover:border-primary"
                        :class="currentTab === tab.toLowerCase() ? 'text-primary border-primary': 'border-transparent'"
                    >
                        {{ tab }}
                    </a>
                </li>
            </ul>
        </div>
        <SettingProfileTab v-if="currentTab === 'profile'"/>
        <SettingCompanyTab v-if="currentTab === 'company'"/>
        <SettingPasswordTab v-if="currentTab === 'password'"/>
        <SettingBuilderTab v-if="currentTab === 'settings'"/>
    </MerchantLayout>
</template>

<script>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import SettingProfileTab from '@/Components/Merchant/SettingProfileTab.vue'
import SettingCompanyTab from '@/Components/Merchant/SettingCompanyTab.vue'
import SettingPasswordTab from '@/Components/Merchant/SettingPasswordTab.vue'
import SettingBuilderTab from '@/Components/Merchant/SettingBuilderTab.vue'

export default {
    name: 'Settings',
    components: {SettingPasswordTab, SettingCompanyTab, SettingProfileTab, MerchantLayout, SettingBuilderTab},
    data() {
        return {
            tabs: ['Profile', 'Company', 'Settings', 'Password'],
            currentTab: 'profile'
        }
    },
    mounted() {
        let hash = ''
        if (this.$page.props.hashtag) {
            hash = `#${this.$page.props.hashtag}`
            window.location.hash = hash
        } else {
            hash = location.hash
        }

        if (hash) {
            this.currentTab = hash.replace('#', '').toLowerCase()
        }
    },
    methods: {
        onChangeTab(tab) {
            this.currentTab = tab.toLowerCase()
        }
    }
}
</script>

<style scoped>

</style>
