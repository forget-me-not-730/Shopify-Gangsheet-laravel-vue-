<template>
    <modal :open="open" @close="$emit('close')" :classes="classes">
        <div class="bg-builder border sm:rounded h-full flex flex-col">
            <div class="flex items-center justify-between relative p-2">
                <h1 class="text-xl font-bold">{{ $t('Add Image') }}</h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="h-1 flex-1">
                <tab-group :selected-index="selectedTab" as="div" class="w-full h-full flex flex-col">
                    <tab-list as="div" class="w-full flex border-b gs-border-primary px-2 pt-2">
                        <tab v-slot="{ selected }" as="div" class="flex-1">
                            <div class="py-1.5" :class="[selected ? 'gs-bg-primary rounded-t-md' : '']" @click="selectedTab = 0">
                                Recent Images
                            </div>
                        </tab>
                        <tab v-slot="{ selected }" as="div" class="flex-1">
                            <div class="py-1.5" :class="[selected ? 'gs-bg-primary rounded-t-md' : '']" @click="selectedTab = 1">
                                Upload Images
                            </div>
                        </tab>
                        <tab v-slot="{ selected }" as="div" class="flex-1">
                            <div class="py-1.5" :class="[selected ? 'gs-bg-primary rounded-t-md' : '']" @click="selectedTab = 2">
                                Gallery Images
                            </div>
                        </tab>
                    </tab-list>
                    <tab-panels as="div" class="mt-2 flex-1 h-px">
                        <tab-panel class="w-full h-full p-2">
                            <image-upload @close="$emit('close')"/>
                        </tab-panel>
                        <tab-panel class="w-full h-full">
                            <gang-sheet-upload-images @close="$emit('close')"/>
                        </tab-panel>
                        <tab-panel class="w-full h-full">
                            <gang-sheet-gallery-images @close="$emit('close')"/>
                        </tab-panel>
                    </tab-panels>
                </tab-group>
            </div>
        </div>
    </modal>
</template>

<script>

import Modal from "./Modal.vue";
import ImageUpload from "@/Builder/Components/ImageUpload.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import {TabGroup, TabList, Tab, TabPanels, TabPanel} from '@headlessui/vue'
import GangSheetGalleryImages from "@/Builder/GangSheet/GangSheetGalleryImages.vue";
import GangSheetUploadImages from "@/Builder/GangSheet/GangSheetUploadImages.vue";

export default {
    name: "ImageUploadModal",
    components: {GangSheetUploadImages, GangSheetGalleryImages, TabPanel, TabPanels, Tab, TabList, TabGroup, CloseIcon, ImageUpload, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        },
        data: {
            type: [Object, null],
            default: null
        }
    },
    data() {
        return {
            selectedTab: 0
        }
    },
    watch: {
        open(value) {
            if (value && this.data) {
                this.selectedTab = this.data.from ?? 0
            }
        }
    },
    computed: {
        classes() {
            return {
                body: '!w-full h-full'
            }
        }
    }
}
</script>
