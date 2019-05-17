<script>
import {defineComponent} from 'vue'
import Spinner from "@/Builder/Components/Spinner.vue";
import GalleryCategoryActions from "@/Components/Merchant/GalleryCategoryActions.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiTrashCanOutline} from '@mdi/js'
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";
import GalleryMixin from "@/Components/Merchant/GalleryMixin";

export default defineComponent({
    name: "GalleryTag",
    components: {SvgIcon, GalleryCategoryActions, Spinner},
    mixins: [ConfirmationMixin, GalleryMixin],
    props: {
        path: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            mdiTrashCanOutline
        }
    },
    computed: {
        tag() {
            return _.get(this.pageData.tags, this.path)
        },
        imageCount() {
            return this.tag.user_images_count || this.tag.images?.length || 0
        },
        isActive() {
            return this.pageData.viewData === 'tags' && this.pageData.activePath === this.path
        },
        show() {
            if (this.pageData.search) {
                const search = this.pageData.search.toLowerCase()
                if (this.tag.name.toLowerCase().includes(search)) {
                    return true
                }
            } else {
                return true
            }
        },
    },
    methods: {
        handleTagClick() {
            this.pageData.viewData = 'tags'
            this.pageData.searchImage = ''
            this.pageData.activePath = this.path
        },
        handleDeleteTag() {
            if (this.imageCount === 0) {
                this.confirmation = {
                    title: 'Delete Tag',
                    description: 'Are you sure you want to delete this tag?',
                    type: 'danger',
                    action: 'Delete',
                    onConfirm: async () => {
                        await axios.post(route('merchant.image.tag.delete', {
                            tag_ids: [this.tag.id]
                        })).then(() => {
                            this.pageData.tags = this.pageData.tags.filter(tag => tag.id !== this.tag.id)

                            if (this.pageData.activePath === this.path) {
                                if (!_.get(this.pageData.tags, this.path)) {
                                    const regex = /\[(\d+)]$/g;
                                    const index = regex.exec(this.path)[1];
                                    if (index > 0) {
                                        this.pageData.activePath = `[${index - 1}]`
                                    } else {
                                        this.pageData.viewData = 'gallery'
                                        this.pageData.activePath = '[0]'
                                    }
                                }
                            }

                            this.loadActiveTagImages(true)
                        })
                    }
                }
            }
        }
    }
})
</script>

<template>
    <div class="w-full" @click="handleTagClick">
        <div v-if="show" class="flex w-full items-center py-2 cursor-pointer relative z-10 pl-6" :class="[isActive ? 'bg-gray-200' : 'hover:bg-gray-100']">
            <div @click.stop="">
                <input v-model="pageData.selectedTags" :value="tag.id" type="checkbox"/>
            </div>
            <div class="text-sm ml-1 whitespace-nowrap overflow-hidden text-ellipsis">
                {{ tag.name }}
            </div>
            <div class="text-xs ml-1 text-gray-400 flex items-center">
                <span>[</span>
                <span>{{ imageCount }}</span>
                <span>]</span>
            </div>
            <div @click.stop="handleDeleteTag" class="h-full w-8 shrink-0 ml-auto flex items-center z-50 justify-evenly" :class="[isActive ? 'bg-gray-200' : 'bg-gray-50 hover:bg-gray-100']">
                <div class="w-5" :class="{'text-gray-400': imageCount > 0}">
                    <svg-icon type="mdi" :path="mdiTrashCanOutline" size="16"/>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
