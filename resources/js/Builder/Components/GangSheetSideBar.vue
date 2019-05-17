<script>

import ImageUpload from '@/Builder/Components/ImageUpload.vue'
import SettingPanel from '@/Builder/Components/SettingPanel.vue'

export default {
    name: 'GangSheetSideBar',
    components: {SettingPanel, ImageUpload}
}

</script>

<template>
    <div class="w-full h-full p-2 pb-10 relative overflow-y-auto tiny-scroll-bar flex flex-col">
        <ImageUpload/>
        <hr class="mt-2">
        <SettingPanel/>
    </div>
</template>

<style scoped>

</style>
