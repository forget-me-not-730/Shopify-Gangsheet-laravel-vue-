<script>
import GsbImage from "@/Builder/Components/GsbImage.vue";
import SearchInput from "@/Builder/Components/SearchInput.vue";
import GangSheetGalleryImageItem from "@/Builder/GangSheet/GangSheetGalleryImageItem.vue";
import AlertCircleOutlineIcon from "@/Builder/Icons/AlertCircleOutlineIcon.vue";
import ChevronLeftIcon from "@/Builder/Icons/ChevronLeftIcon.vue";
import ChevronRightIcon from "@/Builder/Icons/ChevronRightIcon.vue";
import HomeIcon from "@/Builder/Icons/HomeIcon.vue";
import builderMixin from "@/Builder/Mixins/builderMixin";
import Spinner from "@/Components/Spinner.vue";
import {mdiAccountCircleOutline} from "@mdi/js";
import {defineComponent} from 'vue';
import ImageSortMenu from '../Components/ImageSortMenu.vue';

export default defineComponent({
    name: "GangSheetGalleryImages",
    components: {ChevronLeftIcon, GangSheetGalleryImageItem, ImageSortMenu, SearchInput, AlertCircleOutlineIcon, HomeIcon, GsbImage, Spinner, ChevronRightIcon},
    mixins: [builderMixin],
    emits: ['close'],
    data() {
        return {
            loadingImages: false,
            loadingCategories: false,
            perPage: 10,
            currentPage: 1,
            category_id: '',
            search: null,
            timer: null,
            categories: null,
            filteredCategories: [],
            selectedCategory: null,
            selectedSubCategory: null,
            merchantImages: [],
            viewImages: true,
            totalImages: 0,
            filter: 'all',
            hasMore: true,
            mdiAccountCircleOutline,
            links: [],
            selectedSortOptions: []
        }
    },
    computed: {
        options() {
            return (this.categories || []).map((category) => {
                return {
                    value: category.id,
                    label: category.name,
                    children: category.children.map(item => {
                        return {
                            value: item.id,
                            label: item.name
                        }
                    })
                }
            })
        },
        isDropDownMode() {
            return this.shopSettings.galleryMode === 'dropdown'
        },
        isSortable() {
            return this.shopSettings.enableSort === true
        },
        pages() {
            const pages = []
            for (let link of this.links) {
                if (link.url) {
                    let url = new URL(link.url)
                    link.page = Number(url.searchParams.get('page'))
                } else {
                    link.page = (pages[pages.length - 1]?.page ?? 0) + 1
                }
                pages.push(link)
            }
            return pages
        }
    },
    watch: {
        category_id() {
            if (this.viewImages) {
                this.reloadImages()
            }
        },
        filter() {
            if (this.viewImages) {
                this.reloadImages()
            } else {
                this.filterCategories()
            }
        },
        selectedSortOptions: {
            deep: true,
            handler() {
                this.currentPage = 0
                this.reloadImages()
            },
        }
    },
    mounted() {
        if (!this.categories) {
            this.getCategories()
        }
        this.initializeImageLoading()
    },
    methods: {
        initializeImageLoading() {
            const observable = this.$el.querySelector(".observable");
            const root = this.$el.querySelector(".observable-root");
            if (observable && root) {
                const options = {root: root};
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && this.hasMore && !this.loadingImages && this.viewImages) {
                            this.currentPage++
                            this.getImages()
                        }
                    });
                }, options)
                observer.observe(observable);
            }
        },
        reloadImages() {
            this.currentPage = 1
            this.merchantImages = []
            this.totalImages = 0
            this.hasMore = true
            this.getImages()
        },
        getImages() {
            if (this.loadingImages) {
                return
            }
            this.loadingImages = true
            axios.get(route('builder.shop-images', {
                shop_id: this.shop.id,
                page: this.currentPage,
                category_id: this.category_id,
                search: this.search,
                filter: this.filter,
                perPage: this.perPage,
                orderBy: this.selectedSortOptions,
                image_tags: this.product?.image_tags ?? [],
            })).then((res) => {
                if (res && res.data) {
                    this.merchantImages = this.merchantImages.concat(res.data.data)
                    this.currentPage = Number(res.data.current_page)
                    this.hasMore = res.data.current_page < res.data.last_page
                    this.totalImages = Number(res.data.total)
                    this.links = res.data.links
                }
            }).catch((e) => {
                Toast.error({
                    message: 'Failed to load images'
                })
            }).finally(() => {
                this.loadingImages = false
            })
        },
        onSearch() {
            if (!this.loadingImages) {
                if (this.viewImages) {
                    if (this.timer) {
                        clearTimeout(this.timer)
                    }
                    this.timer = setTimeout(() => {
                        this.reloadImages()
                    }, 800)
                } else {
                    this.filterCategories()
                }
            }
        },
        getCategories() {
            this.loadingCategories = true
            axios.get(route('builder.shop-image-categories', {
                user_id: this.shop.id
            })).then((res) => {
                if (res && res.data) {
                    this.categories = res.data
                }
            }).catch((e) => {
                Toast.error({
                    message: 'Failed to load Categories'
                })
            }).finally(() => {
                this.loadingCategories = false
                this.filterCategories()
            })
        },
        handleCategoryClick(category) {
            if (this.loadingCategories || this.loadingImages) {
                return
            }

            if (category) {
                if (category.parent_id) {
                    this.selectedSubCategory = category
                } else {
                    this.selectedCategory = category
                    this.selectedSubCategory = null
                }

                if (category.images_count > 0) {
                    this.category_id = category.id
                    this.viewImages = true
                } else {
                    this.merchantImages = []
                    this.currentPage = 1
                    this.totalImages = 0
                    this.viewImages = false
                    this.category_id = ''
                }
            } else {
                this.selectedCategory = null
                this.selectedSubCategory = null
                this.merchantImages = []
                this.totalImages = 0
                this.viewImages = false
                this.currentPage = 1
                this.category_id = ''
            }

            this.filterCategories()
        },
        filterCategories() {
            if (this.isDropDownMode) {
                return
            }

            let cats = this.categories || [];

            if (this.selectedSubCategory) {
                cats = [];
            } else if (this.selectedCategory) {
                if (this.selectedCategory.children.length) {
                    cats = this.selectedCategory.children;
                } else {
                    cats = [];
                }
            }

            if (this.search && cats.length) {
                cats = cats.filter(cat => cat.name.toLowerCase().includes(this.search.toLowerCase()));
            }

            this.filteredCategories = cats;
        },
        handlePageClick(page) {
            if (this.currentPage !== page) {
                this.currentPage = page
                this.merchantImages = []
                this.totalImages = 0
                this.hasMore = true
                this.getImages()
            }
        },
    }
})
</script>

<template>
    <div class="w-full h-full relative flex flex-col">
        <div class="px-2 py-2 relative z-50">
            <div class="w-full space-y-2 text-xs">
                <div class="flex items-center gap-1">
                    <image-sort-menu v-if="isSortable" v-model="selectedSortOptions"/>
                    <search-input v-model="search" @input="onSearch" class="w-full"/>
                </div>
                <el-tree-select v-if="isDropDownMode && categories" v-model="category_id" :data="options"
                                :render-after-expand="false" filterable check-strictly clearable placeholder="Select Category..."
                                :default-expand-all="true"/>
                <div v-else
                     class="flex items-center text-sm cursor-pointer overflow-hidden whitespace-nowrap text-ellipsis">
                    <div class="flex items-center" @click="handleCategoryClick(null)">
                        <home-icon size="20"/>
                        <span>All</span>
                    </div>
                    <div v-if="selectedCategory" class="flex items-center"
                         @click="handleCategoryClick(selectedCategory)">
                        <chevron-right-icon size="20"/>
                        <span>{{ selectedCategory.name }}</span>
                    </div>
                    <div v-if="selectedSubCategory" class="flex items-center">
                        <chevron-right-icon size="20"/>
                        <span>{{ selectedSubCategory.name }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="observable-root flex-1 px-2 pb-4 overflow-y-auto tiny-scroll-bar z-10">
            <div v-if="merchantImages?.length" class="bg-orange-400 bg-opacity-50 flex items-start p-1 rounded text-xs">
                <alert-circle-outline-icon class="w-4 h-4 inline-block mr-1 shrink-0 mt-0.5"/>
                {{ $t('The watermark you see on images is for online protection only and will NOT be printed.') }}
            </div>

            <div class="grid grid-cols-2 gap-2 mt-2">
                <template v-if="!isDropDownMode">
                    <div v-for="category in filteredCategories" :key="category.id"
                         class="relative group aspect-square cursor-pointer border border-gray-300 hover:gs-border-primary active:opacity-50 rounded overflow-hidden"
                         @click="handleCategoryClick(category)">
                        <gsb-image :src="category.image_url"/>
                        <div
                            class="absolute z-10 inset-0 flex items-center justify-center text-white bg-gray-900 bg-opacity-50 px-2">
                            {{ category.name }}
                        </div>
                        <div
                            class="absolute inset-0 bg-green-500 bg-opacity-75 left-full transition-all group-hover:left-0">
                        </div>
                        <div class="absolute z-50 rounded-full px-4 bg-builder top-1 right-1 text-xs">
                            <span v-if="category.images_count">{{ category.images_count }} images</span>
                        </div>
                        <div
                            class="absolute z-50 rounded-full px-4 bg-builder bottom-1 right-1 text-xs bg-primary text-white">
                            <span v-if="category.children && category.children?.length">{{ category.children?.length }}
                                categories</span>
                        </div>
                    </div>
                </template>

                <template v-if="viewImages">
                    <div v-for="image in merchantImages" :key="image.id"
                         class="aspect-square relative border cursor-pointer border-gray-300 active:opacity-50 rounded hover:gs-border-primary overflow-hidden">
                        <gang-sheet-gallery-image-item :image="image" class="w-full h-full" @close="$emit('close')"/>
                        <div v-if="image.best_seller"
                             class="absolute z-50 rounded-full px-2 gs-bg-primary top-1 left-1 text-xs">
                            Best Seller
                        </div>
                    </div>
                </template>
            </div>

            <div v-if="!merchantImages.length && !filteredCategories.length && !loadingImages && !loadingCategories"
                 class="py-16 text-gray-400 text-center w-full">
                {{ $t('No Images') }}
            </div>

            <div class="observable min-h-[10px] flex items-center text-sm justify-center">
                <div v-if="loadingImages || loadingCategories" class="py-10 flex items-center">
                    <spinner class="mr-2"/>
                    Loading ...
                </div>
            </div>
        </div>

        <div class="bg-builder z-10 border-t text-xs py-px">
            <div class="flex w-max mx-auto border-l">
                <div v-for="page in pages" @click="handlePageClick(page.page)"
                     class="cursor-pointer flex items-center h-[30px] min-w-[30px] border-r justify-center"
                     :class="page.active ? 'gs-bg-primary' : 'hover:text-gray-900 hover:bg-gray-50'">
                    <chevron-left-icon v-if="page.label.includes('Previous')" size="18"/>
                    <chevron-right-icon v-else-if="page.label.includes('Next')" size="18"/>
                    <span v-else>{{ page.label }}</span>
                </div>
            </div>
        </div>
    </div>
</template>


<style lang="scss">
@import 'element-plus/dist/index.css';

:root {
    --el-fill-color-blank: var(--gs-bg-color);
    --el-fill-color-light: var(--gs-primary-color);
    --el-tree-text-color: var(--gs-fg-color);
    --el-text-color-regular: var(--gs-fg-color);
    //--el-border-color-light: var(--gs-bg-color);
    --el-bg-color-overlay: var(--gs-bg-color) !important;
}

.el-select__input.el-select__input--iOS {
    background-color: transparent !important;
}

.el-select-dropdown__item.hover:not(.selected),
.el-select-dropdown__item:hover:not(.selected) {
    color: var(--gs-primary-fg-color);
}

.el-select,
.el-input__wrapper,
.select-trigger.el-tooltip__trigger.el-tooltip__trigger {
    --el-color-primary: var(--gs-primary-color);
    background-color: transparent !important;
    width: 100%;
    font-weight: 300;
    border-radius: 6px;
}

.el-select * {
    font-size: inherit !important;
}

.el-input__inner {
    color: var(--gs-fg-color) !important;
}

.el-input__inner:focus {
    outline: 0;
    border: none !important;
    box-shadow: none;
}

.el-select .el-input__wrapper.is-focus {
    box-shadow: 0 0 0 2px var(--el-select-input-focus-border-color) inset !important;
}
</style>
