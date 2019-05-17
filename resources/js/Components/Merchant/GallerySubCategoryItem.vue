<script>
import {defineComponent} from 'vue'
import {mdiProgressCheck, mdiPencilCircleOutline, mdiDeleteCircleOutline, mdiImageOutline, mdiFolderImage, mdiSquareEditOutline} from '@mdi/js';
import SvgIcon from '@jamescoyle/vue-icon';
import moment from 'moment-timezone';
import confirmationMixin from '@/Builder/Mixins/confirmationMixin';
import PromptMixin from "@/Mixins/PromptMixin";

export default defineComponent({
    name: "GallerySubCategoryItem",
    mixins: [PromptMixin, confirmationMixin],
    components: {
        SvgIcon
    },
    props: {
        subcategory: {
            type: Object,
            required: true
        },
        index: {
            type: Number,
            default: 0
        }
    },
    data() {
        return {
            editing: false,
            mdiProgressCheck,
            mdiPencilCircleOutline,
            mdiDeleteCircleOutline,
            mdiImageOutline,
            mdiFolderImage,
            mdiSquareEditOutline
        }
    },
    computed: {
        imageCount() {
            return this.subcategory.images_count || this.subcategory.images?.length || 0
        },
        subCategoryCount() {
            return this.subcategory.children?.length ?? 0
        }
    },
    methods: {
        moment,
        handleImagesClick() {
            this.$nextTick(() => {
                if (this.pageData.activePath) {
                    this.pageData.activePath += `.children[${this.index}]`
                } else {
                    this.pageData.activePath = `[${this.index}]`
                }
            })
        },
        handleEdit() {
            this.editing = true;
            this.prompt = {
                title: 'Edit Category',
                id: this.subcategory.id,
                placeholder: 'Enter category name',
                action: 'Update',
                value: this.subcategory.name,
                image_url: this.subcategory.image_url,
                enableColorOverlay: this.subcategory.color_overlay,
                onConfirm: async (inputValue, imageValue, enableColorOverlay, hasOldImage, oldImageUrl) => {
                    try {
                        await axios.post(route('merchant.image.category.update', {category_id: this.subcategory.id}),
                            {
                                name: inputValue,
                                image: imageValue,
                                color_overlay: enableColorOverlay,
                                hasOldImage: oldImageUrl ? !hasOldImage : hasOldImage
                            }
                        ).then((res) => {
                            if (res.data.success) {
                                this.subcategory.name = inputValue
                                this.subcategory.image_url = res.data.image_url
                                this.subcategory.color_overlay = enableColorOverlay
                                window.Toast.success({
                                    message: 'Category updated successfully.'
                                })
                            } else {
                                window.Toast.error({
                                    message: 'Failed to update Category.'
                                })
                            }
                        }).catch((error) => {
                            console.error("Error updating category:", error);
                        });

                    } catch (error) {
                        console.error("Error in onConfirm:", error);
                    }
                    this.editing = false;
                }
            }
        },
        handleImageError(e) {
            e.target.src = '/assets/images/placeholder.webp';
        },
        handleSubCategoryDelete() {
            this.confirmation = {
                title: 'Remove Category?',
                description: `Are you really sure you want to remove ${this.subcategory.name} category?`,
                action: 'Remove',
                type: 'danger',
                onConfirm: async () => {
                    await axios.delete(route('merchant.image.category.delete', {category_id: this.subcategory.id}))
                        .then((res) => {
                            if (res.data.success) {
                                if (this.pageData.activePath) {
                                    let subCategories = _.get(this.pageData.gallery, this.pageData.activePath)
                                    subCategories.children = subCategories.children.filter(subct => subct.id != this.subcategory.id);
                                } else {
                                    this.pageData.gallery = this.pageData.gallery.filter(ct => ct.id != this.subcategory.id)
                                }
                            }
                        })
                }
            }
        }
    }
})
</script>

<template>
    <div :data-category-id="subcategory.id" class="gallery-subcategory gallery-item flex shadow border rounded-xl px-2 py-2 relative hover:bg-gray-100 rounded-tl-none rounded-br-none">
        <div class="w-20 h-20 shrink-0 mr-1 flex flex-col">
            <label class="w-full h-20 cursor-pointer" @mousedown.prevent="" @mousemove.prevent="">
                <input type="checkbox" :value="subcategory.id" v-model="pageData.selectedSubcategories" class="absolute top-1 left-1 z-10">
                <img :src="this.subcategory.image_url + '?v=' + Date.now()" class="w-full h-full border border-gray-300" alt="Image" @error="handleImageError"/>
            </label>
        </div>
        <div class="flex-1 w-1">
            <div class="flex font-semibold cursor-text break-word text-sm py-1 px-2" @click.prevent.stop="">
                {{ this.subcategory.name }}
            </div>
            <div class="flex space-x-2 items-center text-gray-400 text-xs ml-2 my-1">
                <div>{{ moment(subcategory.created_at).format('l') }}</div>
            </div>

            <div class="flex w-full justify-between items-center flex-wrap pl-2 my-2 pr-2">
                <div class="flex items-center text-xs opacity-70 hover:underline" @click="handleImagesClick">
                    <svg-icon type="mdi" :path="mdiFolderImage" size="14" class="mr-1"></svg-icon>
                    <span>{{ imageCount }} images</span>
                </div>
                <div class="flex" :class="{'pointer-events-none opacity-20': editing}">
                    <svg-icon type="mdi" :path="mdiProgressCheck" size="20" :class="subcategory.status? 'text-primary': 'text-red-600' "></svg-icon>
                    <svg-icon type="mdi" :path="mdiPencilCircleOutline" size="20" class="text-gray-600 hover:text-green-600" @click.prevent.stop="handleEdit"></svg-icon>
                    <svg-icon v-if="!imageCount && !subCategoryCount" type="mdi" :path="mdiDeleteCircleOutline" size="20" class="text-gray-600 hover:text-red-600"
                              @click.prevent.stop="handleSubCategoryDelete"></svg-icon>
                </div>
            </div>
        </div>


    </div>
</template>

<style scoped>

</style>
