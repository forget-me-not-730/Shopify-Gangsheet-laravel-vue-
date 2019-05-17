<template>
    <div class="border aspect-square flex items-center relative cursor-pointer transparent-pattern" style="--cell-size: 8px;" @click="importImage">
        <img v-if="!reading" :src="imageUrl" class="w-full h-full object-contain" :draggable="true" @error="handleImageError" @load="handleImageLoaded" @dragstart="handleDragStart($event,image)"
             alt=""/>
        <div v-if="!loading" class="rounded-full bg-red-500 h-4 w-4 absolute right-px top-px flex items-center justify-center cursor-pointer z-50" @click.prevent.stop="removeImage(image)">
            <svg-icon class="text-white text-xs" type="mdi" :path="mdiClose"></svg-icon>
        </div>
        <div v-if="loading || uploading" class="absolute w-full h-full bg-black bg-opacity-50 flex flex-col items-center justify-center">
            <spinner :class="{'w-8 h-8': uploading}"/>
            <small v-if="uploading" class="absolute text-white text-xs text-center whitespace-nowrap">{{ progress.toFixed() }}%</small>
        </div>
        <div v-if="error" class="absolute w-full h-full  bg-black bg-opacity-50 text-red-500 font-thin flex items-center justify-center z-0">
            Failed
        </div>
        <div v-if="countImageUrl" class="btn-builder px-0 rounded-sm text-white h-4 min-w-[16px] absolute bottom-0 right-0 justify-center items-center flex text-xs">
            <image-counter :url="countImageUrl"/>
        </div>
    </div>
</template>

<script>
import builderMixin from '@/Builder/Mixins/builderMixin'
import Spinner from '@/Components/Spinner.vue'
import {getSessionId} from '@/Builder/Utils/helpers'
import ImageCounter from '@/Builder/Components/ImageCounter.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiClose} from '@mdi/js'
import eventBus from '@/Builder/Utils/eventBus'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import normalizeUrl from 'normalize-url'

export default {
    name: 'ImageItem',
    components: {ImageCounter, Spinner, SvgIcon},
    mixins: [builderMixin],
    props: {
        image: {
            type: [Object, String],
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
            loading: false,
            uploading: false,
            imageUrl: null,
            progress: 0,
            error: false,
            mdiClose
        }
    },
    computed: {
        countImageUrl() {
            if (typeof this.image === 'string') {
                return this.image
            } else {
                return this.image.url
            }
        }
    },
    watch: {
        'image.url': {
            handler() {
                this.imageUrl = this.image.thumb_url || this.image.url
            }
        },
        imageUrl() {
            this.loading = true
        }
    },
    created() {
        this.loading = true
        if (typeof this.image === 'string') {
            this.imageUrl = this.image
        } else {
            this.imageUrl = this.image.thumb_url || this.image.url

            if (!this.imageUrl) {
                this.imageUrl = '/assets/images/placeholder.webp'
                if (this.image.file?.type?.startsWith('image')) {
                    this.imageUrl = URL.createObjectURL(this.image.file)
                }
            }

            if (this.image?.file) {
                this.handleImageUpload()
            }
        }
    },
    methods: {
        handleImageError() {
            this.error = true
            this.imageUrl = '/assets/images/placeholder.webp'
        },
        handleImageLoaded(e) {
            if (typeof this.image === 'object') {
                if (!this.image.width) {
                    this.image.width = e.target.naturalWidth
                }
                if (!this.image.height) {
                    this.image.height = e.target.naturalHeight
                }
            }
            this.loading = false
            eventBus.$emit(eventBus.IMAGE_ADD, this.image)
        },
        handleDragStart(e, image) {
            e.dataTransfer.setData('imageUrl', image)
        },
        importImage() {
            if (!this.loading && this.image.url) {
                if (this.autoNestMode) {
                    eventBus.$emit(eventBus.IMAGE_ADD, this.image)
                } else {
                    window._gangSheetCanvasEditor?.addImage(this.image)
                }
                this.$emit('close')
            }
        },
        removeImage(image) {
            const index = this.images.findIndex(im => im.id === image.id)
            if (index > -1) {
                this.images.splice(index, 1)
                window._gangSheetCanvasEditor?._removeImage(image)
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
        showErrorMessage(message) {
            window.Toast.show({
                class: 'upload-error-toast',
                color: 'dark',
                icon: 'ico-error',
                title: this.image?.file?.name,
                message: message,
                position: 'bottomLeft',
                progressBarColor: 'rgb(255, 50, 50)',
                timeout: 200000,
                resetOnHover: true,
            });
        },
        async handleImageUpload() {
            this.uploading = true

            const formData = new FormData()

            formData.append('image', this.image.file)
            formData.append('user_id', this.shop.id)
            formData.append('session_id', getSessionId())

            if (this.customer) {
                formData.append('customer_id', this.customer.id)
            }

            let uploadUrl = route('builder.upload-image');

            if (this.isShopify && !this.isDev) {
                uploadUrl = 'https://admin.dripappsserver.com/api/builder/upload/image'
            }

            window.axios.post(uploadUrl, formData, {
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
                        url: res.data.image.url,
                        thumb_url: res.data.image.thumb_url,
                        width: res.data.image.width,
                        height: res.data.image.height,
                        resolution: res.data.image.resolution,
                    }

                    if (this.artBoardSettings.enableImageBackgroundWarning && res.data.has_background) {
                        eventBus.$emit(eventBus.OPEN_MODAL, {
                            name: MODAL_NAMES.IMAGE_UPLOAD_WARNING,
                            data: {
                                image: image
                            }
                        })

                        this.removeImage(this.image)
                    } else {
                        this.$nextTick(() => {
                            this.image.id = image.id
                            this.image.thumb_url = image.thumb_url
                            this.image.width = image.width
                            this.image.height = image.height
                            this.image.url = image.url
                            this.image.resolution = image.resolution

                            delete this.image.file
                            delete this.image.progress

                            _gangSheetCanvasEditor._addImage(image)
                        })
                    }

                    this.uploading = false
                    this.loading = true
                } else {
                    this.showErrorMessage(res.data?.error ?? 'Failed to upload image')
                    this.removeImage(this.image)
                }
            }).catch((e) => {
                console.error(e)
                this.uploadNextImage()
                this.showErrorMessage(e?.message ?? 'Failed to upload image')
                this.removeImage(this.image)
            })
        }
    }
}
</script>

<style scoped>
</style>
