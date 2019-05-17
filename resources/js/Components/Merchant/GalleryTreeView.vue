<script>
import {defineComponent} from 'vue'
import GalleryCategory from "@/Components/Merchant/GalleryCategory.vue";
import GalleryDraggable from "@/Components/Merchant/GalleryDraggable.vue";
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";
import GalleryTag from "@/Components/Merchant/GalleryTag.vue";
import {mdiUnfoldLessHorizontal, mdiUnfoldMoreHorizontal, mdiReload, mdiCloseCircleOutline, mdiChevronDown} from '@mdi/js';
import SvgIcon from '@jamescoyle/vue-icon';
import GalleryMixin from "@/Components/Merchant/GalleryMixin";
import GalleryCategoryRoot from './GalleryCategoryRoot.vue';

export default defineComponent({
    name: "GalleryTreeView",
    components: {GalleryTag, GalleryDraggable, GalleryCategory, GalleryCategoryRoot, SvgIcon},
    mixins: [ConfirmationMixin, GalleryMixin],
    data() {
        return {
            refreshing: false,

            mdiUnfoldLessHorizontal,
            mdiUnfoldMoreHorizontal,
            mdiCloseCircleOutline,
            mdiChevronDown,
            mdiReload
        }
    },
    methods: {
        getPrevCategory(index) {
            if (index > 0) {
                return this.pageData.gallery[index - 1];
            }
            return null;
        },
        handleDraggableChange(e) {
            this.pageData.activePath = `[${e.toIndex}]`
            const category_ids = this.pageData.gallery.map(({id}) => id);
            axios.post(route('merchant.image.category.reorder'), {ids: category_ids})
                .then(res => {
                    // ignore
                })
        },
        handleExpandAll() {
            if (this.pageData.viewData === 'gallery') {
                this.pageData.gallery.forEach((category) => {
                    category.open = true
                })
            }
        },
        handleCollapseAll() {
            if (this.pageData.viewData === 'gallery') {
                this.pageData.gallery.forEach((category) => {
                    category.open = false
                })
            }
        },
        handleReloadAll() {
            this.refreshing = true
            this.reloadAll().finally(() => {
                this.loadActiveImages(true)
                this.refreshing = false
            })
        },
        handleViewDataChange(viewData) {
            this.pageData.viewData = viewData;
            this.pageData.activePath = '[0]'
        }
    }
})
</script>

<template>
    <div class="w-full h-full flex flex-col py-4 pl-2">
        <div class="flex text-xs border-b mb-1 cursor-pointer mx-1">
            <div class="flex-1 p-2 text-center" :class="{'bg-gray-200': pageData.viewData === 'gallery'}" @click="handleViewDataChange('gallery')">
                Categories ({{ pageData.gallery?.length ?? 0 }})
            </div>
            <div class="flex-1 p-2 text-center" :class="{'bg-gray-200': pageData.viewData === 'tags'}" @click="handleViewDataChange('tags')">
                Tags ({{ pageData.tags?.length ?? 0 }})
            </div>
        </div>
        <div class="flex items-center justify-between p-1">
            <div class="relative w-full h-full">
                <input v-model="pageData.search"
                       class="w-full rounded-md border-0 px-4 py-2 text-gray-900 text-xs shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-primary focus:outline-none sm:leading-6"
                       :placeholder="pageData.viewData === 'gallery' ? 'Search Categories...': 'Search Tags...'"/>
                <div v-if="pageData.search" class="absolute right-0 top-0 bottom-0 w-6 flex items-center justify-center cursor-pointer" @click="pageData.search = ''">
                    <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="16" class="text-gray-500 hover:text-gray-700"></svg-icon>
                </div>
            </div>
            <div class="px-1 flex space-x-1">
                <div class="cursor-pointer flex items-center justify-center text-gray-500 w-5 rounded h-5 hover:bg-gray-200" @click="handleReloadAll">
                    <svg-icon type="mdi" :class="{'animate-spin': refreshing}" :path="mdiReload" size="18"/>
                </div>
                <div class="cursor-pointer flex items-center justify-center text-gray-500 w-5 rounded h-5 hover:bg-gray-200" @click="handleCollapseAll">
                    <svg-icon type="mdi" :path="mdiUnfoldMoreHorizontal" size="18"/>
                </div>
                <div class="cursor-pointer flex items-center justify-center text-gray-500 w-5 rounded h-5 hover:bg-gray-200" @click="handleExpandAll">
                    <svg-icon type="mdi" :path="mdiUnfoldLessHorizontal" size="18"/>
                </div>
            </div>
        </div>
        <div class="flex-1 relative h-px overflow-y-auto overflow-x-hidden pb-[150px] observable-tree-root">
            <div class="w-full space-y-2" v-if="pageData.viewData === 'gallery'">
                <div v-if="pageData.gallery?.length" class="w-full">
                    <gallery-category-root/>
                    <gallery-draggable v-model="pageData.gallery" :disabled="Boolean(pageData.search)" @exchange="handleDraggableChange">
                        <template #item="{index}">
                            <gallery-category :path="`[${index}]`" :prev-category="getPrevCategory(index)"/>
                        </template>
                    </gallery-draggable>
                </div>
                <div v-else class="text-center text-gray-400">
                    <hr/>
                    <div class="py-2">No Categories</div>
                </div>
            </div>
            <div class="w-full mt-4 space-y-2" v-if="pageData.viewData === 'tags'">
                <gallery-tag v-for="(tag, tagIndex) in pageData.tags" :key="tag.id" :path="`[${tagIndex}]`"/>
            </div>
        </div>
    </div>
</template>
