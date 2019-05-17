<script>
import {defineComponent} from 'vue'
import GallerySubCategoryItem from "@/Components/Merchant/GallerySubCategoryItem.vue";
import GalleryImageItem from "@/Components/Merchant/GalleryImageItem.vue";
import GalleryMenu from "@/Components/Merchant/GalleryMenu.vue";
import Spinner from "@/Components/Spinner.vue";
import {mdiChevronDown, mdiChevronUp, mdiCloseCircleOutline} from '@mdi/js';
import SvgIcon from '@jamescoyle/vue-icon';
import GalleryMixin from "@/Components/Merchant/GalleryMixin";
import PrimaryButton from '../PrimaryButton.vue';

export default defineComponent({
    name: "GalleryExplorer",
    components: {Spinner, GalleryMenu, GalleryImageItem, GallerySubCategoryItem, SvgIcon, PrimaryButton},
    mixins: [GalleryMixin],
    data() {
        return {
            showSubCategories: true,

            mdiChevronDown,
            mdiChevronUp,
            mdiCloseCircleOutline,
            statusOptions: [
                {value: 2, label: 'All'},
                {value: 1, label: 'Active'},
                {value: 0, label: 'Inactive'},
            ],
            draggedImage: null,
            draggedOverImage: null
        }
    },
    computed: {
        activeObject() {
            if (this.pageData.viewData === 'gallery') {
                if (this.pageData.activePath) {
                    return _.get(this.pageData.gallery, this.pageData.activePath)
                } else {
                    return this.pageData.gallery
                }
            } else if (this.pageData.viewData === 'tags') {
                return _.get(this.pageData.tags, this.pageData.activePath)
            } else {
                return this.pageData.searchedImages
            }
        },
        subcategories() {
            if (!this.pageData.activePath) {
                return this.pageData.gallery
            }

            if (!this.activeObject) {
                return []
            }

            if (this.activeObject.parent_id) {
                return []
            }

            return this.activeObject.children ?? []
        },
        images() {
            return this.activeObject?.images ?? []
        },
        isLoading() {
            if (this.pageData.viewData === 'gallery') {
                return this.pageData.loadingCategories.includes(this.activeObject?.id)
            } else if (this.pageData.viewData === 'tags') {
                return this.pageData.loadingTags.includes(this.activeObject?.id)
            } else {
                return this.pageData.loadingImages
            }
        },
        filteredImages() {
            const search = this.pageData.searchImage.trim().toLowerCase()
            let images = this.images
            if (search) {
                images = images.filter(im => im.title.toLowerCase().includes(search))
            }

            if (this.pageData.statusFilter === 1) {
                images = images.filter(im => Boolean(im.status))
            } else if (this.pageData.statusFilter === 0) {
                images = images.filter(im => !Boolean(im.status))
            }

            return images
        },
    },
    mounted() {
        this.initializeImageLoading()
    },
    methods: {
        hasNextItems() {
            if (this.activeObject?.hasMore !== undefined) {
                return this.activeObject.hasMore
            }

            return this.images.length < (this.activeObject?.images_count || this.activeObject?.user_images_count || 0)
        },
        handleSelectAllImages(e) {
            if (e.target.checked) {
                this.pageData.selectedImages = this.images.map(im => im.id)
            } else {
                this.pageData.selectedImages = []
            }
        },
        initializeImageLoading() {
            const observable = document.querySelector(".observable");
            const root = document.querySelector(".observable-root");
            if (observable && root) {
                const options = {root: root};
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && this.hasNextItems() && !this.isLoading) {
                            this.loadImages()
                        }
                    });
                }, options)
                observer.observe(observable);
            }
        },
        clearSearch() {
            this.pageData.searchImage = ''
            this.pageData.viewData = 'gallery'
            this.pageData.selectedImages = []
        },
        handleSearchAll() {
            if (this.pageData.searchImage) {
                this.pageData.viewData = 'search'
                this.searchImages(true)
            }
        },
        handleDragStart(image) {
            this.draggedImage = image;
        },
        handleDragOver(image) {
            this.draggedOverImage = image;
        },
        handleDrop() {
            if (this.draggedImage && this.draggedOverImage) {
                const draggedIndex = this.images.findIndex(img => img.id === this.draggedImage.id);
                const draggedOverIndex = this.images.findIndex(img => img.id === this.draggedOverImage.id);

                this.images.splice(draggedIndex, 1);
                this.images.splice(draggedOverIndex, 0, this.draggedImage);

                this.updateImageOrder();
            }
            this.draggedImage = null;
            this.draggedOverImage = null;
        },
        updateImageOrder() {
            const imageOrder = this.images.map((image, index) => ({id: image.id, order: index}));
            axios.post(route('merchant.image.reorder'), {ids: imageOrder.map(im => im.id), orders: imageOrder.map(im => im.order)})
                .then((res) => {
                    // handle success
                });
        }
    }
})
</script>

<template>
    <div class="w-full flex flex-col h-full">
        <div class="max-md:p-2 p-4 flex justify-between text-xs">
            <div class="flex items-center">
                <div class="relative w-max h-full">
                    <input v-model.trim="pageData.searchImage" :disabled="pageData.loadingImages"
                           class="block w-full rounded-md border-0 px-4 py-2 text-gray-900 text-xs shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-primary focus:outline-none sm:leading-6"
                           placeholder="Search Images..."/>
                    <div v-if="pageData.searchImage" class="absolute right-0 top-0 bottom-0 w-6 flex items-center justify-center cursor-pointer" @click="clearSearch">
                        <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="16" class="text-gray-500 hover:text-gray-700"></svg-icon>
                    </div>
                </div>

                <button :disabled="pageData.loadingImages || !pageData.searchImage" class="border disabled:bg-gray-100 rounded-md px-4 h-full ml-2" @click="handleSearchAll">
                    Search All
                </button>
            </div>
            <gallery-menu/>
        </div>
        <hr/>
        <div class="flex-1 w-full h-px relative">
            <div class="observable-root w-full h-full overflow-y-auto">
                <div v-if="subcategories.length" class="mt-3">
                    <label class="text-xs flex items-center font-semibold px-2 space-x-2" @click="showSubCategories = !showSubCategories">
                        <svg-icon type="mdi" :path="showSubCategories ? mdiChevronDown : mdiChevronUp" size="20" class="text-gray-600"></svg-icon>
                        <span v-if="!pageData.activePath">Categories</span>
                        <span v-else>Subcategories</span>
                        <span v-if="pageData.selectedSubcategories.length">({{ pageData.selectedSubcategories.length }} selected)</span>
                    </label>
                    <div v-if="showSubCategories" class="sub-category-wrapper p-4">
                        <gallery-sub-category-item v-for="(subcategory, index) in subcategories" :subcategory="subcategory" :index="index"/>
                    </div>
                    <hr/>
                </div>
                <div v-if="pageData.activePath" class="mt-3">
                    <div class="px-2 space-x-1 flex flex-wrap items-center justify-between">
                        <div class="text-xs flex items-center font-semibold w-max">
                            <input type="checkbox" :checked="(images?.length || 0) > 0 && pageData.selectedImages.length === images.length" class="mr-2"
                                   :class="{'all': pageData.selectedImages.length }"
                                   @change="handleSelectAllImages"/>
                            <span>Images</span>
                            <span v-if="pageData.selectedImages.length">({{ pageData.selectedImages.length }} selected)</span>
                        </div>
                        <div class="inline-flex space-x-2 pr-4">
                            <label v-for="option in statusOptions" :key="option.value">
                                <input type="radio" :disabled="isLoading" :value="option.value" v-model="pageData.statusFilter"/>
                                <span class="ml-2">{{ option.label }}</span>
                            </label>
                        </div>
                    </div>
                    <div v-if="images.length" class="images-wrapper p-4">
                        <gallery-image-item v-for="(image, index) in filteredImages" :key="image.id" :image="image" :index="index"
                                            @dragstart="handleDragStart(image)" @dragover.prevent="handleDragOver(image)" @drop.prevent="handleDrop"/>
                    </div>
                </div>
                <div v-if="!images.length && pageData.activePath" class="flex items-center justify-center p-4 ">
                    <div v-if="!isLoading" class="flex flex-col justify-center items-center">
                        <svg fill="#8080807f" width="150px" height="150px" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M10.5 5c-.073 0-.14.015-.207.045L.83 9.35c-.737.335-1.033 1.227-.687 1.945L6.355 24.18c.347.718 1.24 1.07 1.967.664l3.422-1.907c.588-.298.044-1.18-.486-.873L7.834 23.97c-.196.11-.467.01-.58-.224l-.414-.857 1.885-.943c.605-.304.078-1.16-.45-.894l-1.87.935L1.044 10.86c-.113-.233-.023-.498.2-.6l9.464-4.305c.485-.222.287-.955-.207-.955zm4.777-1c-.19 0-.377.035-.552.104-.35.137-.648.407-.81.775L8.122 18.15c-.32.737.02 1.61.757 1.93l13.277 5.797c.737.32 1.61-.02 1.93-.757l5.795-13.277c.32-.737-.023-1.61-.76-1.93L15.847 4.12c-.184-.08-.378-.12-.57-.12zm-.015.994c.06.002.122.016.183.043l13.278 5.795c.244.107.345.37.238.613l-4.82 11.047-14.13-6.166 4.822-11.05c.08-.182.248-.286.43-.282zM9.61 17.242l14.13 6.168-.572 1.313c-.107.244-.37.347-.613.24L9.277 19.168c-.244-.107-.347-.37-.24-.615zM7.5 16c-.22-.002-.408.133-.475.342l-1 3c-.194.583.733.967.95.316l1-3c.112-.323-.133-.656-.475-.658zm9-3c-.075 0-.156.02-.223.053l-4 2c-.596.267-.093 1.19.446.894l3.605-1.802 1.756 2.632c.14.21.413.282.64.17l3.604-1.802 1.756 2.632c.352.547 1.19-.033.832-.554l-2-3c-.14-.21-.413-.282-.64-.17l-3.604 1.802-1.756-2.632c-.094-.142-.246-.226-.416-.223zm.5-5c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 1c.563 0 1 .437 1 1s-.437 1-1 1-1-.437-1-1 .437-1 1-1zM6 11c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 1c.563 0 1 .437 1 1s-.437 1-1 1-1-.437-1-1 .437-1 1-1z"/>
                        </svg>
                        <div class="text-gray-400">
                            {{ images.length ? 'No images found' : 'No images available' }}
                        </div>
                        <PrimaryButton v-if="!pageData.uploadImage && images.length === 0" class="mt-5 font-thin" @click="pageData.uploadImage = true">Upload Images</PrimaryButton>
                    </div>
                </div>

                <div v-if="isLoading" class="flex flex-col justify-center items-center space-y-3 mt-10">
                    <spinner class="!w-6 !h-6"/>
                    <span>
                        {{ pageData.viewData === 'search' ? 'Searching images...' : 'Loading images...' }}
                    </span>
                </div>

                <div class="observable w-full h-36"/>
            </div>

            <div v-if="images.length" class="absolute left-0 bottom-0 py-1 px-4 w-max bg-white text-sm z-20">
                <span>1 ~ {{ images.length }} of {{ (activeObject.images_count || activeObject.user_images_count) }} images loaded.</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.images-wrapper,
.sub-category-wrapper {
    display: grid;
    grid-gap: 12px;
}

.sub-category-wrapper {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.images-wrapper {
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
}
</style>
