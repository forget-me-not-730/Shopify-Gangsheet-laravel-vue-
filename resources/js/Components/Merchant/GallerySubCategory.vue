<script>
import {defineComponent} from 'vue'
import GalleryCategoryActions from "@/Components/Merchant/GalleryCategoryActions.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiDragVertical} from '@mdi/js'

export default defineComponent({
    name: "GallerySubCategory",
    components: {SvgIcon, GalleryCategoryActions},
    props: {
        path: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            mdiDragVertical
        }
    },
    computed: {
        subCategory() {
            return _.get(this.pageData.gallery, this.path)
        },
        categoryPath() {
            return this.path.split('.').slice(0, -1).join('.')
        },
        category() {
            return _.get(this.pageData.gallery, this.categoryPath)
        },
        show() {
            if (this.pageData.search) {
                const search = this.pageData.search.toLowerCase()

                if (this.category.name.toLowerCase().includes(search)) {
                    return true
                }

                if (this.subCategory.name.toLowerCase().includes(search)) {
                    return true
                }
            } else {
                return Boolean(this.subCategory)
            }
        },
        imageCount() {
            return this.subCategory.images_count || this.subCategory.images?.length || 0
        },
        isActive() {
            return this.pageData.viewData === 'gallery' && this.pageData.activePath === this.path
        }
    },
    methods: {
        handleCategoryClick() {
            if (window.isCategoryDragging) {
                return
            }
            this.pageData.searchImage = ''
            this.pageData.viewData = 'gallery'
            this.pageData.activePath = this.path
        }
    }
})
</script>

<template>
    <div class="relative w-full" :class="[isActive ? 'z-30' : 'z-20', {'gallery-sub-category-item': show}]">
        <template v-if="show">
            <div
                class="gallery-subcategory flex items-center w-full py-2 pl-12 cursor-pointer pr-2"
                :data-category-id="subCategory.id"
                :class="[isActive ? 'bg-gray-200' : 'hover:bg-gray-100']"
                @click="handleCategoryClick"
            >
                <label @click.stop="">
                    <input name="subcategory" v-model="pageData.selectedSubcategories" :value="subCategory.id" type="checkbox"/>
                </label>
                <div class="text-sm ml-1 capitalize whitespace-nowrap overflow-hidden text-ellipsis">
                    {{ subCategory.name }}
                </div>
                <div class="text-xs ml-2 text-gray-400 flex items-center">
                    <span>[</span>
                    <span>{{ imageCount }}</span>
                    <span>]</span>
                </div>
                <div @mousedown.prevent.stop="" @mouseup.prevent.stop="" class="h-full w-10 ml-auto flex items-center z-50 justify-evenly"
                     :class="[isActive ? 'bg-gray-200' : 'bg-gray-50 hover:bg-gray-100']">
                    <div class="w-5">
                        <svg-icon type="mdi" :path="mdiDragVertical" size="16"/>
                    </div>
                    <gallery-category-actions :path="path"/>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.gallery-sub-category-item.draggable-item {
    opacity: 0.8;
}

.gallery-sub-category-item.draggable-item::before {
    display: none;
}

.gallery-sub-category-item::before {
    content: '';
    position: absolute;
    left: 28px;
    bottom: calc(50% - 2px);
    width: 20px;
    height: 32px;
    border-left: solid 1px #8080807f;
    border-bottom: solid 1px #8080807f;
}

.gallery-sub-category-item:not(:first-child)::before {
    height: 40px;
}
</style>
