<template>
    <div class="max-md:h-full w-full flex flex-col">
        <label v-if="rootProps && !builderSettings.disableUploadingImage" v-bind="rootProps"
               class="h-max py-2 border border-dashed rounded flex relative cursor-pointer upload-image hover:bg-gray-300 hover:bg-opacity-50">
            <div class="w-full h-full flex justify-center items-center">
                <input id="image-picker" v-bind="inputProps" hidden multiple/>
                <div class="h-full w-full flex justify-center items-center">
                    <div class="text-center px-4 flex flex-col items-center text-sm">
                        <span v-if="isDragActive">Drop the files here ...</span>
                        <span class="border-0" v-else>{{ $t('Drag & drop a file here, or click to') }}</span>
                        <div class="btn-builder flex mt-2 items-center justify-around px-3 xl:py-1 text-xs xl:text-sm w-max">
                            <svg-icon type="mdi" size="20" :path="mdiCloudUploadOutline" class="mr-1"/>
                            {{ $t('Upload Image(s)') }}
                        </div>
                        <small class="text-gray-400 mt-2">
                            {{ $t('Upload images larger than') }} 300 x 300px. <br/> {{ $t('Supported formats') }}: {{ fileTypes.join(', ') }}
                        </small>
                    </div>
                </div>
            </div>
        </label>
        <div v-if="renderComponent" class="max-md:flex-1 max-md:h-1 overflow-hidden">
            <div class="grid grid-cols-4 mt-2 gap-2 max-h-full md:max-h-[150px] xl:max-h-[220px] overflow-y-auto tiny-scroll-bar">
                <div
                    v-if="!shopSettings.disableTextFeature && shopFonts.length"
                    @click="handleAddText"
                    class="aspect-square flex text-center flex-col items-center justify-center border cursor-pointer add-text"
                >
                    <svg-icon type="mdi" :path="mdiFormatText"></svg-icon>
                    <small>{{ $t('Add Text') }}</small>
                </div>
                <template v-for="(image) in images" :key="`image-${image.id}`">
                    <image-item v-model="uploadingImage" :image="image" @close="$emit('close')"/>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import {useDropzone} from 'vue3-dropzone'
import eventBus from '@/Builder/Utils/eventBus'
import Spinner from '@/Components/Spinner.vue'
import builderMixin from '@/Builder/Mixins/builderMixin'
import rerenderMixin from '@/Builder/Mixins/rerenderMixin'
import ImageItem from '@/Builder/Components/ImageItem.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiFormatText, mdiImage, mdiCloudUploadOutline} from '@mdi/js'
import {getFileTypes} from '@/Builder/Utils/helpers'

export default {
    name: 'ImageUpload',
    components: {SvgIcon, ImageItem, Spinner},
    mixins: [builderMixin, rerenderMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isDragActive: false,
            error: null,
            imageUrl: '',
            rootProps: null,
            inputProps: null,
            uploadingImage: null,
            newImages: [],
            fileTypes: ['png', 'jpeg', 'jpg', 'svg', 'pdf', 'eps', 'ai', 'psd', 'webp'],

            mdiFormatText,
            mdiImage,
            mdiCloudUploadOutline,
        }
    },
    watch: {
        open() {
            this.error = null
            this.imageUrl = null
        }
    },
    mounted() {
        this.fileTypes = getFileTypes(this.shopSettings.file_types)

        const {getRootProps, getInputProps} = useDropzone({
            onDrop: this.onDrop,
            noDrag: true,
            accept: this.shopSettings.file_types.join(',')
        })
        this.inputProps = getInputProps()
        this.rootProps = getRootProps()

        eventBus.$on(eventBus.IMAGE_UPLOADED, (newImage) => {
            this.images.push(newImage)
            this.images = [...this.images]
            this.$nextTick(() => {
                if (!this.uploadingImage) {
                    this.uploadingImage = newImage.id
                }
            })
        })

        eventBus.$on(eventBus.IMAGE_UPLOAD, () => {
            const imagePicker = document.getElementById('image-picker')
            if (imagePicker) {
                imagePicker.click()
            }
        })
    },
    unmounted() {
        eventBus.$off(eventBus.IMAGE_UPLOADED)
        eventBus.$off(eventBus.IMAGE_UPLOAD)
    },
    methods: {
        async onDrop(files) {
            if (this.images.length >= 100) {
                window.Toast.warning({
                    message: 'You can have up to 100 images saved.'
                })
                return
            }

            files.forEach((file, index) => {
                this.imageUrl = ''
                this.error = ''
                const size = file.size / 1024 / 1024

                const fileType = file.name.split('.').pop().toLowerCase();
                const fileTypes = this.fileTypes.reduce((acc, type) => {
                    const items = type.split(', ')
                    return [...acc, ...items]
                }, [])
                if (!fileTypes.includes(fileType)) {
                    this.$gsb.error(`${file.name} is not a supported file type. Supported types: ${this.fileTypes.join(', ')}.`);
                    return;
                }

                if (size > 150) {
                    this.$gsb.error(`${file.name} is too big, should be less than 150 MB.`);
                    return;
                }

                const newImage = {
                    id: new Date().getTime() + index,
                    file: file
                }
                this.images.unshift(newImage)
                if (index === 0) {
                    this.$nextTick(() => {
                        this.uploadingImage = newImage.id
                    })
                }
            })

            const imagePicker = document.getElementById('image-picker')
            if (imagePicker) {
                imagePicker.value = null
            }
        },
        handleAddText() {
            if (window._gangSheetCanvasEditor) {
                if (this.autoNestMode) {
                    eventBus.$emit(eventBus.ADD_TEXT_TO_AUTO_NEST)
                } else {
                    window._gangSheetCanvasEditor.addText('Gang Sheet', this.defaultFont)
                }
                this.$emit('close')
            }
        }
    }
}
</script>
