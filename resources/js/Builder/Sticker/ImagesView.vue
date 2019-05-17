<script>
import {defineAsyncComponent} from "vue";
import {TabGroup, TabList, Tab, TabPanels, TabPanel} from '@headlessui/vue'

const UploadImages = defineAsyncComponent(() => import('./StickerUploadImages.vue'))
const GalleryImages = defineAsyncComponent(() => import('./StickerGalleryImages.vue'))

export default {
    name: "ImagesView",
    components: {TabGroup, TabList, Tab, TabPanels, TabPanel, GalleryImages, UploadImages},
    data() {
        return {
            tab: 'uploads'
        }
    }
}
</script>

<template>
    <div class="w-full h-full flex flex-col">
        <TabGroup>
            <TabList :selected-index="0" class="flex space-x-1 rounded-xl bg-white p-1">
                <Tab v-slot="{ selected }" class="flex-1">
                    <button class="w-full py-1 rounded-lg" :class="{'bg-gray-100': selected}">
                        Uploads
                    </button>
                </Tab>
                <Tab v-slot="{ selected }" class="flex-1 rounded-lg">
                    <button class="w-full py-1 rounded-lg" :class="{'bg-gray-100': selected}">
                        Gallery
                    </button>
                </Tab>
            </TabList>

            <TabPanels class="flex-1 h-1">
                <TabPanel class="w-full h-full">
                    <upload-images/>
                </TabPanel>
                <TabPanel class="w-full h-full">
                    <gallery-images/>
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>
</template>

<style scoped>

</style>
