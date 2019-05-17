<script>
import {defineComponent} from "vue";
import Spinner from "@/Components/Spinner.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiCloudUploadOutline} from "@mdi/js";
import GsbImage from "@/Builder/Components/GsbImage.vue";
import {getSessionId} from "@/Builder/Utils/helpers";
import builderMixin from "@/Builder/Mixins/builderMixin";
import axios from 'axios';
import SearchInput from "@/Builder/Components/SearchInput.vue";
import Dropdown from "@/Builder/Components/Dropdown.vue";
import DotsHorizontalIcon from "@/Builder/Icons/DotsHorizontalIcon.vue";
import {deleteCustomerImages} from "@/Builder/Apis/builderApi";
import GangSheetUploadImageItem from "@/Builder/GangSheet/GangSheetUploadImageItem.vue";
import GangSheetGalleryImageItem from "@/Builder/GangSheet/GangSheetGalleryImageItem.vue";
import ChevronLeftIcon from "@/Builder/Icons/ChevronLeftIcon.vue";
import ChevronRightIcon from "@/Builder/Icons/ChevronRightIcon.vue";
import ReloadIcon from "@/Builder/Icons/ReloadIcon.vue";

export default defineComponent({
    name: "GangSheetUploadImages",
    components: {ReloadIcon, ChevronRightIcon, ChevronLeftIcon, GangSheetGalleryImageItem, GangSheetUploadImageItem, DotsHorizontalIcon, Dropdown, SearchInput, GsbImage, SvgIcon, Spinner},
    mixins: [builderMixin],
    data() {
        return {
            search: "",
            currentPage: 0,
            loading: false,
            hasMore: true,
            totalImages: 0,

            mdiCloudUploadOutline,
            customerImages: [],
            links: [],
            timer: 0
        };
    },
    computed: {
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
        search() {
            clearTimeout(this.timer)
            this.timer = setTimeout(() => {
                this.reloadImages();
            }, 1000)
        },
        customer() {
            this.reloadImages()
        }
    },
    mounted() {
        this.initializeImageLoading();
    },
    methods: {
        initializeImageLoading() {
            const observable = this.$el.querySelector(".observable");
            const root = this.$el.querySelector(".observable-root");
            if (observable && root) {
                const options = {root: root};
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && this.hasMore && !this.loading && this.customerImages) {
                            this.currentPage++
                            this.loadUploadImages()
                        }
                    });
                }, options)
                observer.observe(observable);
            }
        },
        reloadImages() {
            this.currentPage = 1
            this.customerImages = []
            this.totalImages = 0
            this.hasMore = true
            this.loadUploadImages()
        },
        handlePageClick(page) {
            if (this.currentPage !== page) {
                this.currentPage = page
                this.customerImages = []
                this.hasMore = true
                this.loadUploadImages()
            }
        },
        loadUploadImages() {
            if (this.loading) {
                return
            }
            this.loading = true;
            axios
                .get(
                    route("builder.customer-images", {
                        session_id: getSessionId(),
                        customer_id: this.customer?.id,
                        shop_id: this.shop.id,
                        page: this.currentPage,
                        search: this.search
                    })
                )
                .then((res) => {
                    if (res && res.data) {
                        this.customerImages = this.customerImages.concat(res.data.data);
                        this.currentPage = Number(res.data.current_page)
                        this.hasMore = res.data.current_page < res.data.last_page
                        this.links = res.data.links
                        this.totalImages = Number(res.data.total)
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        handleDeleteImage(image) {
            this.customerImages = this.customerImages.filter((img) => img.id !== image.id);
            deleteCustomerImages([image.id])
        }
    },
});
</script>

<template>
    <div class="w-full h-full flex flex-col">
        <div class="my-2 w-full flex items-center px-2">
            <search-input v-model="search" class="w-full"/>
            <div class="cursor-pointer h-full aspect-square rounded-lg ml-1 flex items-center justify-center hover:bg-gray-300 hover:bg-opacity-50 border border-gray-300"
                 @click="reloadImages">
                <reload-icon size="20"/>
            </div>
        </div>
        <div class="observable-root flex-1 h-1">
            <div class="h-full overflow-y-auto tiny-scroll-bar p-2">
                <div class="grid grid-cols-2 gap-2">
                    <div v-for="image in customerImages" :key="image"
                         class="aspect-square cursor-pointer border border-gray-300 hover:gs-border-primary rounded overflow-hidden active:opacity-50 relative">
                        <gang-sheet-upload-image-item :image="image" @close="$emit('close')"/>
                        <div class="absolute z-50 gs-bg-primary right-0 top-0">
                            <Dropdown width="16">
                                <template #trigger>
                                    <dots-horizontal-icon size="18"/>
                                </template>
                                <template #content>
                                    <div class="w-full text-xs text-red-500 px-3 py-2" @click="handleDeleteImage(image)">
                                        Delete
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>

                <div v-if="!customerImages.length && !loading" class="py-16 flex justify-center items-center text-gray-400">
                    {{ $t('No Images') }}
                </div>

                <div class="observable min-h-[10px] flex items-center text-sm justify-center">
                    <div v-if="loading" class="py-10 flex items-center">
                        <spinner class="mr-2"/>
                        Loading ...
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-builder z-10 border-t text-xs py-px">
            <div class="flex w-max mx-auto border-l">
                <div
                    v-for="page in pages"
                    @click="handlePageClick(page.page)"
                    class="cursor-pointer flex items-center h-[30px] min-w-[30px] border-r justify-center"
                    :class="page.active ? 'gs-bg-primary': 'hover:text-gray-900 hover:bg-gray-50'"
                >
                    <chevron-left-icon v-if="page.label.includes('Previous')" size="18"/>
                    <chevron-right-icon v-else-if="page.label.includes('Next')" size="18"/>
                    <span v-else>{{ page.label }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
