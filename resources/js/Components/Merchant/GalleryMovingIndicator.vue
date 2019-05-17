<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiFolderOutline, mdiImageOutline} from "@mdi/js";

export default defineComponent({
    name: "GalleryMovingIndicator",
    components: {
        SvgIcon
    },
    data() {
        return {
            mdiFolderOutline,
            mdiImageOutline
        }
    }
})
</script>

<template>
    <div class="gallery-moving-indicator text-xs fixed w-16 h-16 rounded left-0 bg-gray-200 top-0 border z-[99] p-2">
        <div class="flex flex-col justify-center space-y-1">
            <div v-if="pageData.selectedSubcategories?.length" class="flex items-center">
                <svg-icon type="mdi" :path="mdiFolderOutline" size="14" class="mr-1"></svg-icon>
                <span class="bg-primary rounded text-2xs text-white px-2">{{ pageData.selectedSubcategories.length }}</span>
            </div>
            <div v-if="pageData.selectedImages?.length" class="flex items-center">
                <svg-icon type="mdi" :path="mdiImageOutline" size="14" class="mr-1"></svg-icon>
                <span class="bg-primary text-white rounded text-2xs px-2">{{ pageData.selectedImages.length }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gallery-moving-indicator {
    --offset-x: 0px;
    --offset-y: 0px;
    transform: translate(var(--offset-x), var(--offset-y));
    display: none;
}
</style>
