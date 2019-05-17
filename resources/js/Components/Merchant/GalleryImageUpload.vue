<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiClose, mdiCloseCircleOutline, mdiCloudUploadOutline} from '@mdi/js'
import {useDropzone} from "vue3-dropzone";
import Resumable from 'resumablejs'
import Spinner from "@/Components/Spinner.vue";
import TagsInput from '@/Components/TagsInput.vue';
import GalleryMixin from "@/Components/Merchant/GalleryMixin";
import GsbImage from "@/Builder/Components/GsbImage.vue";

export default defineComponent({
    name: "GalleryImageUpload",
    components: {GsbImage, Spinner, SvgIcon, TagsInput},
    mixins: [GalleryMixin],
    data() {
        const {getRootProps, getInputProps, isDragActive} = useDropzone({
            onDrop: this.onDrop,
            accept: 'image/png,image/jpg,image/jpeg,image/svg+xml'
        })

        const inputProps = getInputProps()
        const rootProps = getRootProps()

        return {
            inputProps,
            rootProps,
            isDragActive,

            images: [],
            tags: [],
            category_id: null,
            tag_ids: [],
            categoryName: '',

            mdiClose,
            mdiCloudUploadOutline,
            mdiCloseCircleOutline
        }
    },
    mounted() {
        this.categoryName = this.category.name
    },
    computed: {
        category() {
            let category = _.get(this.pageData.gallery, this.pageData.activePath)
            if (!category) {
                category = this.pageData.gallery[0]
            }

            return category
        },
        categoryNames() {
            return this.pageData.gallery.reduce((acc, category) => {
                acc.push(category.name)

                if (category.children) {
                    category.children.forEach(child => {
                        acc.push(child.name)
                    })
                }

                return acc
            }, [])
        },
        uploading() {
            return this.images.filter(i => i.status === 'uploading').length !== 0;
        }
    },
    watch: {
        category() {
            this.categoryName = this.category.name
        }
    },
    methods: {
        setImage(imageId, key, value) {
            const index = this.images.findIndex(i => i.id === imageId)
            this.images[index][key] = value
        },
        onDrop(files) {

            files.slice(0, 100).forEach((file, index) => {
                const imageId = new Date().getTime() + index

                const image = {
                    id: imageId,
                    file: file,
                    size: file.size,
                    title: file.name,
                    progress: 0,
                    src: URL.createObjectURL(file),
                    uploader: null,
                    status: 'started'
                }

                this.images.push(image)
            })

            this.createCategoryAndTags().then(() => {
                this.images.forEach(image => {
                    if (image.status === 'started') this.uploadImage(image)
                })
            })
        },
        async createCategoryAndTags() {
            if (this.categoryName.trim() !== this.category.name.trim() || this.tags.length) {
                await axios.post(route('merchant.image.tag.create'), {
                    category_name: this.categoryName,
                    tag_names: this.tags,
                    user_id: this.$page.props.auth.user.id
                }).then(res => {
                    if (res.data.success) {
                        let category = res.data.category
                        if (category.parent_id || this.pageData.gallery.find(c => c.id === category.id)) {
                            category = this.category
                        } else {
                            this.pageData.gallery.push(category)
                            this.pageData.activePath = `[${this.pageData.gallery.length - 1}]`
                        }

                        this.category_id = category.id
                        this.tag_ids = res.data.tag_ids
                    }
                })
            } else {
                this.category_id = this.category.id
            }
        },
        uploadImage(image) {
            this.setImage(image.id, 'status', 'uploading')
            const query = {
                category_id: this.category_id,
                user_id: this.$page.props.auth.user.id,
                tag_ids: this.tag_ids.join(',')
            }

            const r = new Resumable({
                target: route('merchant.image.upload'),
                query: query,
                maxFiles: 1,
                maxFileSize: 1024 * 1024 * 100,
                testChunks: false,
                simultaneousUploads: 3,
                throttleProgressCallbacks: 1,
                chunkSize: 1024 * 1024,
                forceChunkSize: true,
                fileParameterName: 'file',
                maxFileSizeErrorCallback: (file) => {
                    window.Toast.error({
                        message: 'File size is too large'
                    })
                }
            })

            r.addFile(image.file)

            r.on('fileProgress', (file) => {
                this.setImage(image.id, 'progress', file.progress(true) * 100)
            })

            r.on('fileSuccess', (file, message) => {
                const res = JSON.parse(message)
                if (res.success) {
                    if (this.category.images) {
                        this.category.images.push(res.image)
                    } else {
                        this.category.images = [res.image]
                    }

                    if (!this.category.images_count) this.category.images_count = 0

                    this.category.images_count++;

                    this.pageData.tags.forEach(tag => {
                        if (this.tag_ids.includes(tag.id)) {
                            if (tag.images) {
                                tag.images.push(res.image)
                            }
                            tag.images_count++;
                        }
                    })

                    this.removeImage(image)
                } else {
                    this.setImage(image.id, 'status', 'failed')
                }

                if (this.images.length === 0) {
                    this.pageData.uploadImage = false
                    this.reloadTags()
                }
            })

            r.on('fileError', () => {
                this.setImage(image.id, 'status', 'failed')
            })

            this.setImage(image.id, 'uploader', r)

            r.on('fileAdded', () => {
                r.upload()
            })
        },
        imageSizeLabel(size) {
            const kb = size / 1024
            if (kb < 1024) {
                return kb.toFixed(2) + ' KB'
            }

            const mb = kb / 1024

            return mb.toFixed(2) + ' MB'
        },
        handleCategoryChange(e) {
            const categoryName = e.target.value.trim()

            if (categoryName) {
                this.pageData.viewData = 'gallery'

                this.pageData.gallery.forEach((category, index) => {
                    if (category.name === categoryName) {
                        this.pageData.activePath = `[${index}]`
                    }

                    if (category.children) {
                        category.children.forEach((child, index2) => {
                            if (child.name === categoryName) {
                                this.pageData.activePath = `[${index}].children[${index2}]`
                            }
                        })
                    }
                })
            } else {
                this.categoryName = this.category.name
            }
        },
        removeImage(image) {
            const index = this.images.findIndex(i => i.id === image.id)
            this.images.splice(index, 1)
        },
        handleCancelImageUpload(image) {
            if (image.progress > 90 && image.status !== 'failed') return

            if (image.status !== 'failed') {
                image.cancelled = true
                image.uploader.cancel()
            }

            this.removeImage(image)
        },
        handleClose() {
            if (!this.uploading) {
                this.pageData.uploadImage = false
            }
        }
    }
})
</script>

<template>
    <div class="bg-white absolute h-full flex flex-col right-0 w-full max-w-xl top-0 z-[90] shadow">
        <div class="flex items-center justify-between p-4">
            <h5 class="text-base font-semibold">Upload Images</h5>
            <div @click="handleClose">
                <svg-icon type="mdi" :path="mdiClose" size="24" class="cursor-pointer"/>
            </div>
        </div>
        <hr/>
        <div class="w-full space-y-2 p-4 flex-1 overflow-y-auto pb-20">
            <div>
                <label class="text-xs font-semibold">Category <sup class="text-red-500">*</sup></label>
                <div v-selector="categoryNames" class="w-full inp-default py-0 px-1 mt-1">
                    <input v-model="categoryName" :disabled="uploading" type="text" class="w-full text-xs border-0 py-0 px-1 h-8 focus:ring-0" @change="handleCategoryChange"/>
                </div>
                <span class="text-xs text-gray-400">Select a category from the left panel.</span>
            </div>

            <TagsInput v-model="tags" :disabled="uploading"/>

            <div class="w-full ">
                <label
                    v-if="rootProps" v-bind="rootProps"
                    class="rounded-lg flex flex-col justify-center space-y-1 items-center w-full overflow-hidden mt-2 cursor-pointer border border-dashed border-gray-500 h-36"
                    :class="{ 'bg-gray-100': isDragActive }"
                >
                    <input type="file" v-bind="inputProps" hidden multiple>
                    <svg-icon type="mdi" :path="mdiCloudUploadOutline" size="36" class="cursor-pointer"/>
                    <div class="btn-primary rounded-full">
                        Browse Files
                    </div>
                    <span class="text-xs text-gray-400">
                        {{ isDragActive ? 'Drop files here...' : 'Drop Files here' }}
                    </span>
                    <b>Files supported .png, .jpg & .svg</b>
                </label>
            </div>

            <div class="w-full !mt-5 space-y-2">
                <div v-for="image in images" :key="image.id" class="flex space-x-2 rounded border py-1 px-2">
                    <div class="h-16 w-16 overflow-hidden border shrink-0 flex items-center justify-center">
                        <gsb-image :src="image.src" class="w-full h-full " alt="Image"/>
                    </div>
                    <div class="flex-1 flex flex-col justify-between">
                        <div class="flex">
                            <div class="flex-1  mr-4">
                                <div class="text-xs font-semibold text-gray-500">{{ image.title }}</div>
                                <div class="text-xs text-gray-500">{{ imageSizeLabel(image.size) }}</div>
                            </div>
                            <div class="text-red-500 cursor-pointer w-6 h-6" @click="handleCancelImageUpload(image)">
                                <spinner v-if="image.progress > 90 && image.status !== 'failed'"/>
                                <svg-icon v-else type="mdi" :path="mdiClose" size="20" class="cursor-pointer"/>
                            </div>
                        </div>
                        <div v-if="image.status === 'failed'">
                            <div class="text-red-500 text-xs">Failed to upload</div>
                        </div>
                        <div v-else-if="image.cancelled">
                            <div class="text-red-500 text-xs">Uploading cancelled</div>
                        </div>
                        <div v-else class="flex items-center">
                            <div class="flex-1 h-2 bg-gray-200 rounded-full mr-2">
                                <div :style="{width: image.progress + '%'}" class="h-2 bg-primary transition-all rounded-full"/>
                            </div>
                            <span class="text-xs">{{ image.progress.toFixed(2) }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
