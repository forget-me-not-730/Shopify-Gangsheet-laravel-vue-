<script>
import {defineComponent} from "vue";
import Spinner from "@/Components/Spinner.vue";
import Modal from "@/Builder/Modals/Modal.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiClose} from "@mdi/js";
import TagsInput from '@/Components/TagsInput.vue';
import GalleryMixin from "@/Components/Merchant/GalleryMixin";
import DangerButton from "@/Components/DangerButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

export default defineComponent({
    name: "AddGalleryImageTagModal",
    components: {SvgIcon, Modal, Spinner, TagsInput, DangerButton, PrimaryButton},
    mixins: [GalleryMixin],
    props: {
        open: {
            type: Boolean,
            default: false,
        }
    },
    data() {
        return {
            loading: false,
            tags: [],
            mdiClose
        }
    },
    watch: {
        open() {
            this.tags = [];
            this.loading = false;
        },
    },
    methods: {
        handleConfirm() {
            this.loading = true;

            axios.post(route('merchant.image.add-tags'), {
                image_ids: this.pageData.selectedImages,
                category_ids: [...this.pageData.selectedSubcategories, ...this.pageData.selectedCategories],
                tag_names: this.tags
            }).then((res) => {
                if (res.data.success) {
                    this.setTags(res.data.tags)
                    this.setGallery(res.data.gallery)

                    this.pageData.selectedImages = []
                    this.pageData.selectedCategories = []
                    this.pageData.selectedSubcategories = []

                    this.loadActiveTagImages(true)
                    this.loadActiveCategoryImages(true)
                }
            }).finally(() => {
                this.loading = false
                this.$emit('close')
            })
        },
    }
});
</script>

<template>
    <Modal :open="open">
        <div class="rounded-2xl bg-white w-full max-w-md mx-auto border text-xs text-left">
            <div class="bg-gray-100 py-3 px-4 flex items-center justify-between rounded-t-2xl">
                <h2 class="text-base text-left font-semibold">
                    Add tags to selected images
                </h2>

                <div class="flex items-center justify-center cursor-pointer text-gray-700" @click="$emit('close')">
                    <svg-icon type="mdi" :path="mdiClose" size="20"/>
                </div>
            </div>
            <TagsInput v-model="tags" class="p-4"/>
            <div class="flex items-center justify-end space-x-3 py-3 px-4 border-t">
                <DangerButton
                    @click="$emit('close')"
                    :disabled="loading"
                >
                    Cancel
                </DangerButton>
                <PrimaryButton
                    @click="handleConfirm"
                    :disabled="loading || !tags.length"
                >
                    <spinner v-if="loading" class="mr-1"/>
                    Add Tags
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
</style>
