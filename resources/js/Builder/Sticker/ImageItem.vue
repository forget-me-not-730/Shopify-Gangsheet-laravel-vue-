<template>
    <div class="border aspect-square flex items-center relative cursor-pointer transparent-pattern" style="--cell-size: 8px;" @click="importImage">
        <img v-if="!reading" :src="imageUrl" class="w-full h-full object-contain" :draggable="true" @error="handleImageError" @load="handleImageLoaded" @dragstart="handleDragStart($event,image)"
             alt=""/>
        <div class="rounded-full bg-red-500 h-4 w-4 absolute right-px top-px flex items-center justify-center cursor-pointer" @click.prevent.stop="removeImage(image)">
            <svg-icon class="text-white text-xs" type="mdi" :path="mdiClose"></svg-icon>
        </div>
        <div v-if="uploading" class="absolute w-full h-full bg-black bg-opacity-20 flex flex-col">
            <div class="rounded-full h-3 m-auto bg-gray-300 mx-1 overflow-hidden flex items-center justify-center relative">
                <div class="bg-primary h-full absolute left-0 transition-all animate-pulse" :style="{width: `${progress}%`}">
                    <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-white"></span>
                </div>
                <small class="absolute text-white text-xs text-center whitespace-nowrap">{{ progress.toFixed() }} %</small>
            </div>
        </div>
        <div v-if="loading" class="absolute w-full h-full bg-black bg-opacity-25 flex items-center justify-center">
            <spinner/>
        </div>
        <div v-if="error" class="absolute w-full h-full text-red-500 font-thin flex items-center justify-center">
            failed
        </div>
        <div class="btn-builder px-0 rounded-sm text-white h-4 min-w-[16px] absolute bottom-0 right-0 justify-center items-center flex text-xs">
            <image-counter :url="countImageUrl"/>
        </div>
    </div>
</template>

<script>
import normalizeUrl from 'normalize-url'
import builderMixin from '@/Builder/Mixins/builderMixin'
import Spinner from '@/Components/Spinner.vue'
import {getSessionId} from '@/Builder/Utils/helpers'
import ImageCounter from '@/Builder/Components/ImageCounter.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiClose} from '@mdi/js'
import eventBus from '@/Builder/Utils/eventBus'

export default {
    name: 'ImageItem',
    components: {ImageCounter, Spinner, SvgIcon},
    mixins: [builderMixin],
    props: {
        image: {
            type: Object,
            required: true
        },
        modelValue: {
            type: [Number, null],
            default: null
        }
    },
    data() {
        return {
            reading: false,
            uploading: false,
            imageUrl: null,
            progress: 0,
            loading: false,
            progressTimer: 0,
            error: false,
            mdiClose
        }
    },
    computed: {
        countImageUrl() {
            if (this.image.isGalleryImage && this.image.originUrl) {
                return this.image.originUrl
            }
            return this.imageUrl
        }
    },
    watch: {
        modelValue(value) {
            if (value === this.image.id) {
                this.handleImageUpload()
            }
        },
        'image.url': {
            handler() {
                this.imageUrl = this.image.url
            }
        },
        imageUrl() {
            this.loading = true
        }
    },
    created() {
        if (this.image.url) {
            this.imageUrl = this.image.url
        } else {
            this.uploading = true
            this.imageUrl = '/assets/images/placeholder.webp'
            if (this.image.file?.type?.startsWith('image')) {
                this.reading = true
                const reader = new FileReader()
                reader.onload = () => {
                    this.reading = false
                    this.imageUrl = reader.result
                }
                reader.readAsDataURL(this.image.file)
            }
        }
    },
    methods: {
        handleImageError() {
            this.error = true
            this.imageUrl = '/assets/images/placeholder.webp'
        },
        handleImageLoaded(e) {
            this.image.width = e.target.naturalWidth
            this.image.height = e.target.naturalHeight
            this.loading = false
            eventBus.$emit(eventBus.IMAGE_ADD, this.image)
        },
        handleDragStart(e, image) {
            e.dataTransfer.setData('imageUrl', image)
        },
        importImage() {
            if (!this.uploading && this.image.url) {
                _gangSheetCanvasEditor.addImage(this.image)
                this.$emit('close')
            }
        },
        removeImage(image) {
            const index = this.images.findIndex(im => im.id === image.id)
            if (index > -1) {
                this.images.splice(index, 1)
                _gangSheetCanvasEditor._removeImage(image)
            }
        },
        uploadNextImage() {
            const nextUploadImage = this.images.find(img => !img.url && img.id !== this.image.id)
            if (nextUploadImage) {
                this.$emit('update:modelValue', nextUploadImage.id)
            } else {
                this.$emit('update:modelValue', null)
            }
        },
        async handleImageUpload() {
            return new Promise((resolve) => {
                const formData = new FormData()

                formData.append('image', this.image.file)
                formData.append('user_id', this.shop.id)
                formData.append('session_id', getSessionId())

                if (this.customer) {
                    formData.append('customer_id', this.customer.id)
                }

                window.axios.post(route('builder.upload-image'), formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: (e) => {
                            this.progress = (e.loaded * 90) / e.total
                            if (e.loaded === e.total) {
                                this.progressTimer = setInterval(() => {
                                    this.progress = Math.min(99, this.progress + (this.progress - 10) / this.progress)
                                    this.image.progress = this.progress
                                }, 500)
                            }
                        },
                    }
                ).then((res) => {
                    this.uploadNextImage()
                    clearInterval(this.progressTimer)
                    if (res.data.success) {
                        const image = {
                            id: res.data.image.id,
                            url: normalizeUrl(res.data.image.url),
                            originUrl: normalizeUrl(res.data.image.origin_url)
                        }

                        this.uploading = false
                        this.loading = true

                        this.$nextTick(() => {
                            this.image.id = image.id
                            this.image.url = image.url

                            this.imageUrl = this.image.url

                            delete this.image.file
                            delete this.image.progress

                            _gangSheetCanvasEditor._addImage(image)
                            resolve(image.url)
                        })
                    } else {
                        if (res.data?.error) {
                            window.Toast.error({
                                message: res.data.error
                            })
                        } else {
                            window.Toast.error({
                                message: 'Failed to upload image'
                            })
                        }
                        this.removeImage(this.image)
                        resolve(null)
                    }
                }).catch((e) => {
                    this.uploadNextImage()
                    console.error(e)
                    window.Toast.error({
                        message: 'Failed to upload image'
                    })
                    this.removeImage(this.image)
                    resolve(null)
                })
            })
        },
    }
}
</script>

<style scoped>

</style>
