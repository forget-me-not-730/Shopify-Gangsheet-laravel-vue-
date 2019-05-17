<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiImagePlus, mdiPlus, mdiDotsVertical, mdiChevronDown, mdiSortAlphabeticalAscending, mdiSortAlphabeticalDescending, mdiCalendar, mdiClockOutline, mdiTrendingUp, mdiTrendingDown, mdiSort} from "@mdi/js";
import {Menu, MenuButton, MenuItems, MenuItem} from "@headlessui/vue";
import PromptMixin from "@/Mixins/PromptMixin";
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";
import GalleryMixin from "@/Components/Merchant/GalleryMixin";
import AddGalleryImageTagModal from "@/Components/Merchant/AddGalleryImageTagModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

export default defineComponent({
    name: "GalleryMenu",
    components: {AddGalleryImageTagModal, MenuItem, MenuItems, MenuButton, Menu, SvgIcon, PrimaryButton, DangerButton},
    mixins: [PromptMixin, ConfirmationMixin, GalleryMixin],
    data() {
        return {
            openAddTagsModal: false,

            mdiPlus,
            mdiImagePlus,
            mdiDotsVertical,
            mdiChevronDown,
            mdiSort,
            mdiSortAlphabeticalAscending,
            mdiSortAlphabeticalDescending,
            mdiCalendar,
            mdiClockOutline,
            mdiTrendingUp,
            mdiTrendingDown,
            sortOptions: [
                {value: 'abc', label: 'A to Z', group: 'order', icon: mdiSortAlphabeticalAscending},
                {value: 'z-a', label: 'Z to A', group: 'order', icon: mdiSortAlphabeticalDescending},
                {value: 'newest', label: 'Newest', group: 'date', icon: mdiCalendar},
                {value: 'oldest', label: 'Oldest', group: 'date', icon: mdiClockOutline},
                {value: 'most-used', label: 'Most Used', group: 'usage', icon: mdiTrendingUp},
                {value: 'least-used', label: 'Least Used', group: 'usage', icon: mdiTrendingDown}
            ]
        }
    },
    computed: {
        hasCategoriesSelected() {
            return this.pageData.selectedCategories.length > 0
        },
        hasSubcategoriesSelected() {
            return this.pageData.selectedSubcategories.length > 0
        },
        hasImagesSelected() {
            return this.pageData.selectedImages.length > 0
        },
        hasTagsSelected() {
            return this.pageData.selectedTags.length > 0
        },
        canAddTags() {
            return this.hasImagesSelected || this.hasSubcategoriesSelected || this.hasCategoriesSelected
        },
        canRemoveTags() {
            return this.hasImagesSelected || this.hasSubcategoriesSelected
        },
        canSetAsBestSeller() {
            return this.hasImagesSelected && !this.hasSubcategoriesSelected
        },
        canDropBestSeller() {
            return this.hasImagesSelected && !this.hasSubcategoriesSelected && this.pageData.selectedImages.some(imageId => {
                const image = this.activeObject.images.find(img => img.id === imageId)
                return image.best_seller
            })
        },
        canDelete() {
            return this.hasImagesSelected && !this.hasSubcategoriesSelected
        },
        canActivate() {
            return (this.pageData.statusFilter === 0 || this.pageData.statusFilter === 2) && (this.hasImagesSelected || this.hasSubcategoriesSelected)
        },
        canInactivate() {
            return (this.pageData.statusFilter === 1 || this.pageData.statusFilter === 2) && (this.hasImagesSelected || this.hasSubcategoriesSelected)
        },
        canDeleteTags() {
            return this.hasTagsSelected && this.pageData.selectedTags.every(tagId => {
                const tag = this.pageData.tags.find(t => t.id === tagId)
                return (tag.user_images_count || tag.images?.length || 0) === 0
            })
        }
    },
    methods: {
        handleAddCategoryClick() {
            this.prompt = {
                title: 'Add Category',
                id: null,
                placeholder: 'Enter category name',
                validator: this.validatorCategoryName,
                action: 'Submit',
                onConfirm: async (inputValue, imageValue, enableColorOverlay) => {
                    await axios.post(route('merchant.image.category.create'), {name: inputValue, image: imageValue, color_overlay: enableColorOverlay}).then((res) => {
                        if (res.data.success) {
                            this.pageData.gallery.unshift(res.data.category)
                        }
                    })
                }
            }
        },
        handleAddImage() {
            this.pageData.viewData = 'gallery'
            this.pageData.uploadImage = true
        },
        handleActionSetAsBestSeller() {
            this.confirmation = {
                title: 'Set as Best Seller selected images?',
                description: 'If you set as best seller these images, they will be on best seller tab in the gang sheet builder.',
                action: 'Set as BestSeller',
                onConfirm: async () => {
                    await axios.post(route('merchant.image.update'), {
                        image_ids: this.pageData.selectedImages,
                        best_seller: true
                    }).then((res) => {
                        if (res.data.success) {
                            this.activeObject.images.forEach(image => {
                                if (this.pageData.selectedImages.includes(image.id)) {
                                    image.best_seller = true
                                }
                            })
                            this.pageData.selectedImages = []
                        }
                    })
                }
            }
        },
        handleActionDropBestSeller() {
            this.confirmation = {
                title: 'Drop Best Seller from the selected images?',
                description: 'If you drop as best seller these images, they will be removed on best seller tab in the gang sheet builder.',
                action: 'Drop as BestSeller',
                onConfirm: async () => {
                    await axios.post(route('merchant.image.update'), {
                        image_ids: this.pageData.selectedImages,
                        best_seller: false
                    }).then((res) => {
                        if (res.data.success) {
                            this.activeObject.images.forEach(image => {
                                if (this.pageData.selectedImages.includes(image.id)) {
                                    image.best_seller = false
                                }
                            })
                        }
                        this.pageData.selectedImages = []
                    })
                }
            }
        },
        handleRemoveTags() {
            this.confirmation = {
                title: 'Remove Tags?',
                description: 'Are you really sure you want to remove all tags from the selected images?',
                action: 'Remove',
                type: 'danger',
                onConfirm: async () => {
                    await axios.post(route('merchant.image.remove-tags'), {
                        image_ids: this.pageData.selectedImages
                    }).then((res) => {
                        if (res.data.success) {
                            this.setTags(res.data.tags)
                            this.loadActiveImages(true)
                            this.pageData.selectedImages = []
                        }
                    })
                }
            }
        },
        handleActionInactive() {
            this.confirmation = {
                title: 'Inactivate Items?',
                description: 'If you inactivate these items, they will be not available in the gang sheet builder.',
                action: 'Inactivate',
                onConfirm: async () => {
                    await axios.post(route('merchant.image.update-status'), {
                        image_ids: this.pageData.selectedImages,
                        category_ids: this.pageData.selectedSubcategories,
                        status: 'inactive'
                    }).then(async (res) => {
                        if (res.data.success) {
                            await this.reloadAll()
                            await this.loadActiveImages(true)
                            this.pageData.selectedImages = [];
                            this.pageData.selectedSubcategories = [];
                        }
                    })
                }
            }
        },
        handleActionDelete() {
            this.confirmation = {
                title: 'Delete Images?',
                description: 'Are you sure you want to delete the selected images?',
                action: 'Delete',
                type: 'danger',
                onConfirm: async () => {
                    await axios.post(route('merchant.image.delete'), {
                        ids: this.pageData.selectedImages
                    }).then(async (res) => {
                        if (res.data.success) {
                            await this.reloadAll()
                            await this.loadActiveImages(true)
                            this.pageData.selectedImages = [];
                        }
                    })
                }
            }
        },
        handleActionActive() {
            this.confirmation = {
                title: 'Activate Items?',
                description: 'If you activate these items, they will be available in the gang sheet builder.',
                action: 'Activate',
                onConfirm: async () => {
                    await axios.post(route('merchant.image.update-status'), {
                        image_ids: this.pageData.selectedImages,
                        category_ids: this.pageData.selectedSubcategories,
                        status: 'active'
                    }).then(async (res) => {
                        if (res.data.success) {
                            await this.reloadAll()
                            await this.loadActiveImages(true)
                            this.pageData.selectedImages = [];
                            this.pageData.selectedSubcategories = [];
                        }
                    })
                }
            }
        },
        handleDeleteTags() {
            this.confirmation = {
                title: 'Delete Tags',
                description: 'Are you sure you want to delete the selected tags?',
                type: 'danger',
                action: 'Delete',
                onConfirm: async () => {
                    await axios.post(route('merchant.image.tag.delete', {
                        tag_ids: this.pageData.selectedTags
                    })).then((res) => {
                        if (res.data.success) {
                            this.pageData.tags = this.pageData.tags.filter(tag => !this.pageData.selectedTags.includes(tag.id))

                            if (this.pageData.activePath === this.path) {
                                if (!_.get(this.pageData.tags, this.path)) {
                                    if (this.pageData.viewData === 'tags') {
                                        if (this.pageData.tags.length > 0) {
                                            this.pageData.activePath = `[${this.pageData.tags.length - 1}]`
                                        } else {
                                            this.pageData.viewData = 'gallery'
                                            this.pageData.activePath = '[0]'
                                        }
                                    }
                                }
                            }

                            this.pageData.selectedTags = []

                            this.loadActiveTagImages(true)
                        }
                    })
                }
            }
        },
        handleSelectOrderBy(option) {
            let newSelection = [...this.pageData.selectedOrderBy]

            if (newSelection.includes(option.value)) {
                newSelection = newSelection.filter(val => val !== option.value)
            } else {
                newSelection = newSelection.filter(val => {
                    const optionFromSameGroup = this.sortOptions.find(opt => opt.value === val)
                    return optionFromSameGroup.group !== option.group
                })
                newSelection.push(option.value)
            }

            if (newSelection.length === 0) {
                newSelection = ['abc']
            }

            this.pageData.selectedOrderBy = newSelection
            this.loadActiveCategoryImages(true)
        }
    }
})
</script>

<template>
    <div class="flex space-x-2">

        <add-gallery-image-tag-modal :open="openAddTagsModal" @close="openAddTagsModal = false"/>

        <Menu as="div" class="relative inline-block text-left z-20">
            <div class="w-full h-full flex items-center justify-center">
                <MenuButton
                    class="flex items-center gap-1 rounded border border-gray-300 px-4 py-2">
                    <SvgIcon type="mdi" :path="mdiSort" class="w-5 h-5"/>
                    <span>Sort</span>
                    <div v-if="this.pageData.selectedOrderBy.length > 0"
                        class="h-4 w-4 rounded-full bg-primary text-white text-2xs flex items-center justify-center">
                        {{ this.pageData.selectedOrderBy.length }}
                    </div>
                </MenuButton>
            </div>
            <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                <MenuItems
                    class="absolute left-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg z-50 border border-gray-200 focus:outline-none">
                    <div class="py-1">
                        <div class="px-2 py-1 text-xs text-gray-500">Title</div>
                        <MenuItem v-for="option in sortOptions.filter(opt => opt.group === 'order')" :key="option.value"
                                v-slot="{ active }">
                            <button @click="handleSelectOrderBy(option)"
                                    :class="[
                                        'w-full text-left px-4 py-2 text-sm flex items-center gap-2',
                                        active ? 'bg-black/10' : '',
                                        this.pageData.selectedOrderBy.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'
                                    ]">
                                <span class="flex-1">{{ option.label }}</span>
                                <span v-if="this.pageData.selectedOrderBy.includes(option.value)" class="gs-text-primary">✓</span>
                                <SvgIcon type="mdi" :path="option.icon" :size="18"
                                        :class="this.pageData.selectedOrderBy.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'"/>
                            </button>
                        </MenuItem>

                        <div class="border-t mt-1"></div>
                        <div class="px-2 py-1 text-xs text-gray-500">Date</div>
                        <MenuItem v-for="option in sortOptions.filter(opt => opt.group === 'date')" :key="option.value"
                                v-slot="{ active }">
                            <button @click="handleSelectOrderBy(option)"
                                    :class="[
                                        'w-full text-left px-4 py-2 text-sm flex items-center gap-2',
                                        active ? 'bg-black/10' : '',
                                        this.pageData.selectedOrderBy.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'
                                    ]">
                                <span class="flex-1">{{ option.label }}</span>
                                <span v-if="this.pageData.selectedOrderBy.includes(option.value)" class="gs-text-primary">✓</span>
                                <SvgIcon type="mdi" :path="option.icon" :size="18"
                                        :class="this.pageData.selectedOrderBy.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'"/>
                            </button>
                        </MenuItem>

                        <div class="border-t mt-1"></div>
                        <div class="px-2 py-1 text-xs text-gray-500">Usage</div>
                        <MenuItem v-for="option in sortOptions.filter(opt => opt.group === 'usage')" :key="option.value"
                                v-slot="{ active }">
                            <button @click="handleSelectOrderBy(option)"
                                    :class="[
                                        'w-full text-left px-4 py-2 text-sm flex items-center gap-2',
                                        active ? 'bg-black/10' : '',
                                        this.pageData.selectedOrderBy.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'
                                    ]">
                                <span class="flex-1">{{ option.label }}</span>
                                <span v-if="this.pageData.selectedOrderBy.includes(option.value)" class="gs-text-primary">✓</span>
                                <SvgIcon type="mdi" :path="option.icon" :size="18"
                                        :class="this.pageData.selectedOrderBy.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'"/>
                            </button>
                        </MenuItem>
                    </div>
                </MenuItems>
            </transition>
        </Menu>

        <Menu as="div" class="relative inline-block text-left z-20">
            <div class="w-full h-full flex items-center justify-center">
                <menu-button>
                    <div class="md:hidden">
                        <svg-icon type="mdi" :path="mdiDotsVertical" size="24"/>
                    </div>
                    <div class="max-md:hidden border rounded border-gray-300 px-4 py-2 flex items-center">
                        <span class="mr-1">Actions</span>
                        <svg-icon type="mdi" :path="mdiChevronDown" size="20"/>
                    </div>
                </menu-button>
            </div>
            <transition enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <menu-items
                    class="absolute right-0 z-60 mt-1 origin-top-right rounded-md bg-builder shadow-lg ring-1 ring-black bg-white ring-opacity-5 focus:outline-none">
                    <div class="py-1 overflow-y-auto tiny-scroll-bar z-50">
                        <menu-item class="xl:hidden">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4 hover:bg-gray-100"
                                @click="handleAddCategoryClick"
                            >
                                Add Category
                            </div>
                        </menu-item>
                        <menu-item :disabled="!pageData.gallery.length" class="lg:hidden">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4 hover:bg-gray-100"
                                @click="handleAddImage"
                            >
                                Add Images
                            </div>
                        </menu-item>
                        <hr class="md:hidden"/>
                        <menu-item :disabled="!canSetAsBestSeller" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100']"
                                @click="handleActionSetAsBestSeller"
                            >
                                Set as BestSeller
                            </div>
                        </menu-item>
                        <menu-item :disabled="!canDropBestSeller" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100']"
                                @click="handleActionDropBestSeller"
                            >
                                Drop BestSeller
                            </div>
                        </menu-item>
                        <hr/>
                        <menu-item :disabled="!canAddTags" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100']"
                                @click="openAddTagsModal = true"
                            >
                                Add Tags
                            </div>
                        </menu-item>
                        <menu-item :disabled="!canRemoveTags" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100']"
                                @click="handleRemoveTags"
                            >
                                Remove Tags
                            </div>
                        </menu-item>
                        <hr/>
                        <menu-item :disabled="!canActivate" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100']"
                                @click="handleActionActive"
                            >
                                Activate
                            </div>
                        </menu-item>
                        <menu-item :disabled="!canInactivate" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100']"
                                @click="handleActionInactive"
                            >
                                Inactivate
                            </div>
                        </menu-item>
                        <hr/>
                        <menu-item :disabled="!canDelete" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100 text-red-500']"
                                @click="handleActionDelete"
                            >
                                Delete Images
                            </div>
                        </menu-item>
                        <menu-item :disabled="!canDeleteTags" v-slot="{ disabled }">
                            <div
                                class="cursor-pointer flex shrink-0 w-36 p-2 px-4"
                                :class="[disabled ? 'text-gray-400' : 'hover:bg-gray-100 text-red-500']"
                                @click="handleDeleteTags"
                            >
                                Delete Tags
                            </div>
                        </menu-item>
                    </div>
                </menu-items>
            </transition>
        </Menu>

        <PrimaryButton class="max-xl:hidden flex items-center" @click="handleAddCategoryClick">
            <svg-icon type="mdi" :path="mdiPlus" size="16"/>
            Add Category
        </PrimaryButton>

        <PrimaryButton class=" max-lg:hidden flex items-center" :disabled="!pageData.gallery.length"
                       @click="handleAddImage">
            <svg-icon type="mdi" :path="mdiImagePlus" size="16" class="mr-0.5"/>
            Add Images
        </PrimaryButton>
    </div>
</template>

<style scoped>

</style>
