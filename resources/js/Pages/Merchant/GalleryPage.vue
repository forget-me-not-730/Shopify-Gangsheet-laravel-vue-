<script>
import {Head, usePage} from '@inertiajs/vue3'
import MerchantSideMenu from '@/Components/Layouts/MerchantSideMenu.vue'
import MerchantNav from '@/Components/Layouts/MerchantNav.vue'
import Confirmation from "@/Components/Confirmation.vue";
import Spinner from "@/Components/Spinner.vue";
import Splitter from "@/Components/Merchant/Splitter.vue";
import GalleryTreeView from "@/Components/Merchant/GalleryTreeView.vue";
import GalleryExplorer from "@/Components/Merchant/GalleryExplorer.vue";
import InputPrompt from "@/Components/InputPrompt.vue";
import GalleryImageUpload from "@/Components/Merchant/GalleryImageUpload.vue";
import GalleryMovingIndicator from "@/Components/Merchant/GalleryMovingIndicator.vue";
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";
import GalleryMixin from "@/Components/Merchant/GalleryMixin";

export default {
    name: "GalleryPage",
    mixins: [ConfirmationMixin, GalleryMixin],
    components: {
        GalleryMovingIndicator,
        GalleryImageUpload,
        InputPrompt,
        GalleryExplorer,
        GalleryTreeView,
        Splitter,
        Confirmation,
        Spinner,
        Head,
        MerchantNav,
        MerchantSideMenu
    },
    props: {
        title: String,
        gallery: {
            type: Array,
            required: true
        },
        tags: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            loading: false,
            timer: 0,
            showingSidebar: false
        };
    },
    watch: {
        'pageData.activePath': function (val, oldValue) {
            this.pageData.selectedImages = []

            if (this.pageData.viewData === 'gallery') {
                this.loadActiveCategoryImages()

                const categoryPath = val.split('.')[0]
                const oldCategoryPath = oldValue?.split('.')[0]

                if (categoryPath !== oldCategoryPath) {
                    this.pageData.selectedSubcategories = []
                }
            } else if (this.pageData.viewData === 'tags') {
                this.loadActiveTagImages()
            }
        },
        'pageData.viewData': function () {
            this.pageData.selectedImages = []
            this.loadActiveImages(true)
        },
        'pageData.statusFilter': function () {
            this.pageData.selectedImages = []
            this.loadActiveImages(true)
        }
    },
    created() {
        this.pageData = {
            viewData: 'gallery',
            gallery: this.gallery,
            tags: this.tags,
            searchedImages: {},
            activePath: '',
            search: '',
            searchImage: '',
            statusFilter: 1,
            loadingCategories: [],
            loadingTags: [],
            loadingImages: false,
            uploadImage: false,
            selectedCategories: [],
            selectedSubcategories: [],
            selectedImages: [],
            selectedTags: [],
            selectedOrderBy: []
        }
    },
    mounted() {
        if (usePage().props.errors.message) {
            Toast.error({
                message: usePage().props.errors.message
            })
        }

        if (this.$page.props.hashtag) {
            window.location.hash = `#${this.$page.props.hashtag}`
        }
        this.initializeDragMoving()
    },
    methods: {
        toggleSidebar() {
            this.showingSidebar = !this.showingSidebar
        },
        initializeDragMoving() {
            const handleMouseDown = (e) => {

                const clientX = e.clientX
                const clientY = e.clientY

                function preventEvents(e) {
                    e.preventDefault()
                    e.stopPropagation()
                }

                const handleMoseMove = (e) => {
                    if (Math.abs(e.clientX - clientX) < 8 && Math.abs(e.clientY - clientY) < 8) {
                        return
                    }

                    e.preventDefault()

                    document.addEventListener('click', preventEvents, true)

                    let movingTo = null

                    if (this.pageData.selectedSubcategories?.length) {
                        movingTo = 'category'
                    } else if (this.pageData.selectedImages?.length) {
                        movingTo = 'both'
                    }

                    if (movingTo) {
                        const elIndicator = document.querySelector('.gallery-moving-indicator')

                        elIndicator.style.setProperty('--offset-x', `${e.pageX - 62}px`)
                        elIndicator.style.setProperty('--offset-y', `${e.pageY - 64}px`)
                        elIndicator.style.display = 'flex'

                        const elCategory = e.target.closest('.gallery-category')
                        const elSubcategory = e.target.closest('.gallery-subcategory')

                        document.querySelectorAll('.gallery-drop-target').forEach(el => {
                            el.classList.remove('gallery-drop-target')
                        })

                        if (movingTo === 'category' && elCategory) {
                            elCategory.classList.add('gallery-drop-target')
                        } else if (movingTo === 'both') {
                            if (elCategory) {
                                elCategory.classList.add('gallery-drop-target')
                            }

                            if (elSubcategory) {
                                elSubcategory.classList.add('gallery-drop-target')
                            }
                        }
                    }
                }

                const handleMouseUp = () => {
                    const elIndicator = document.querySelector('.gallery-moving-indicator')
                    elIndicator.style.display = 'none'

                    const elTargetCategory = document.querySelector('.gallery-drop-target')

                    document.removeEventListener('mousemove', handleMoseMove)
                    document.removeEventListener('mouseup', handleMouseUp)
                    document.removeEventListener('click', preventEvents, true)

                    if (elTargetCategory) {
                        elTargetCategory.classList.remove('gallery-drop-target')
                        const targetCategoryId = elTargetCategory.dataset.categoryId

                        this.confirmation = {
                            title: 'Move Items',
                            description: 'Are you sure you want to move the selected items?',
                            onConfirm: async () => {
                                await axios.post(route('merchant.image.move'), {
                                    user_id: this.$page.props.auth.user.id,
                                    category_ids: this.pageData.selectedSubcategories,
                                    image_ids: this.pageData.selectedImages,
                                    category_id: targetCategoryId
                                }).then(async (res) => {
                                    if (res.data.success) {
                                        this.pageData.selectedSubcategories = []
                                        this.pageData.selectedImages = []
                                        this.pageData.gallery = res.data.gallery

                                        await this.loadActiveCategoryImages(true)
                                        await this.loadActiveTagImages(true)
                                    }
                                })
                            }
                        }
                    }
                }

                if (e.target.closest('.gallery-item')) {
                    if (this.pageData.selectedSubcategories?.length || this.pageData.selectedImages?.length) {
                        document.addEventListener('mousemove', handleMoseMove)
                        document.addEventListener('mouseup', handleMouseUp)
                    }
                }
            }

            document.addEventListener('mousedown', handleMouseDown)
        }
    }
};
</script>

<template>
    <div :style="{userSelect: 'none'}">
        <Head :title="title"/>

        <input-prompt/>
        <confirmation/>
        <gallery-moving-indicator/>

        <div class="flex min-h-screen" :class="{'pointer-events-none': pageData.uploadImage}">

            <MerchantSideMenu :show="showingSidebar" :toggleSidebar="toggleSidebar"/>

            <div class="flex min-w-0 flex-1 flex-col max-h-screen overflow-y-auto">

                <MerchantNav :toggleSidebar="toggleSidebar"/>

                <div class="h-full overflow-auto">
                    <splitter>
                        <template v-slot:header>
                            <div class="flex space-x-2">
                                <h4 class="font-semibold">{{ this.pageData.viewData === 'search' ? 'Searched Images' : this.activeObject?.name }}</h4>
                                <span v-if="this.pageData.viewData !== 'search'" class="relative bg-gray-200 rounded border px-2 h-4 text-2xs top-[-4px]">
                                    {{ this.pageData.viewData === 'gallery' ? 'Category' : 'Tag' }}
                                </span>
                            </div>
                        </template>
                        <template v-slot:left>
                            <gallery-tree-view/>
                        </template>
                        <template v-slot:right>
                            <gallery-image-upload v-if="pageData.uploadImage" class="pointer-events-auto"/>
                            <gallery-explorer/>
                        </template>
                    </splitter>
                </div>

            </div>
        </div>
    </div>
</template>

<style>
.gallery-drop-target {
    position: relative;
    background-color: #007a5c45 !important;
}

.gallery-drop-target:hover {
    background-color: #007a5c45 !important;
}

.gallery-drop-target::before {
    position: absolute;
    content: '-> Move to';
    bottom: 100%;
    font-size: 10px;
    border-radius: 1px;
    padding: 0 2px;
    left: 0;
    background-color: #007a5c;
    color: white;
}
</style>
