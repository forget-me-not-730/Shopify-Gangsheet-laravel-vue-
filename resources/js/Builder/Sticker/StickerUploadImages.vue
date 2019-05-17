<script>
import {defineComponent} from "vue";
import Spinner from "@/Components/Spinner.vue";
import {useDropzone} from "vue3-dropzone";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiCloseCircleOutline, mdiCloudUploadOutline} from "@mdi/js";
import GsbImage from "@/Builder/Components/GsbImage.vue";
import {getSessionId} from "@/Builder/Utils/helpers";
import builderMixin from "@/Builder/Mixins/builderMixin";
import {deleteCustomerImages, getDieCutPath} from "@/Builder/Apis/builderApi";
import axios from 'axios';
import eventBus from "@/Builder/Utils/eventBus";
import Dropdown from "@/Builder/Components/Dropdown.vue";
import DotsHorizontalIcon from "@/Builder/Icons/DotsHorizontalIcon.vue";

export default defineComponent({
    name: "StickerUploadImages",
    components: {GsbImage, SvgIcon, Spinner, Dropdown, DotsHorizontalIcon},
    mixins: [builderMixin],
    data() {
        const allowedFileTypes = ["image/png", "image/jpeg", "image/jpg", "image/webp"];

        const {getRootProps, getInputProps} = useDropzone({
            onDrop: this.onDrop,
            noDrag: true,
            multiple: false,
            accept: allowedFileTypes.join(","),
        });
        const inputProps = getInputProps();
        const rootProps = getRootProps();

        return {
            inputProps,
            rootProps,
            search: "",
            currentPage: 0,
            progress: 0,
            loading: false,
            progressTimer: 0,

            mdiCloudUploadOutline,
            mdiCloseCircleOutline,
            customerImages: [],
            uploadingImages: [],
            allowedFileTypes: allowedFileTypes
        };
    },
    mounted() {
        this.loadUploadImages();
    },
    methods: {
        removeUploadingImage(uid) {
            const findIndex = this.uploadingImages.findIndex((img) => img.id == uid);
            if (findIndex > -1) this.uploadingImages.splice(findIndex, 1);
        },
        loadUploadImages() {
            if (!this.loading) {
                this.loading = true;
                axios
                    .get(
                        route("builder.customer-images", {
                            session_id: getSessionId(),
                            customer_id: this.customer?.id,
                            page: this.currentPage,
                            search: this.search,
                            source: 1,
                            perPage: 50,
                        })
                    )
                    .then((res) => {
                        if (res && res.data) {
                            this.customerImages = res.data.data;
                        }
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        },
        onDrop(files) {
            const maxFileSize = 20 * 1024 * 1024;

            const file = files[0]

            if (!this.allowedFileTypes.includes(file.type)) {
                return this.$gsb.error("Invalid file type. Please upload a valid image file.")
            }

            if (file.size > maxFileSize) {
                return this.$gsb.error("File size exceeds the 20MB limit.")
            }

            const newImage = {
                id: new Date().getTime(),
                progress: 0,
                url: URL.createObjectURL(file),
                loading: true,
            };
            this.uploadingImages.push(newImage);

            const formData = new FormData();
            formData.append("image", file);
            formData.append("session_id", getSessionId());
            formData.append("user_id", this.shop.id);
            formData.append("source", 1);
            if (this.customer) formData.append("customer_id", this.customer.id);

            axios.post(route('builder.upload-image'), formData, {
                onUploadProgress: (e) => {
                    this.progress = (e.loaded * 90) / e.total
                    if (e.loaded === e.total) {
                        this.progressTimer = setInterval(() => {
                            this.progress = Math.min(99, this.progress + (this.progress - 10) / this.progress)
                            newImage.progress = this.progress
                        }, 500)
                    }
                },
            }).then(response => {
                clearInterval(this.progressTimer)
                const {image} = response.data;
                this.customerImages.unshift(image);
                newImage.loading = false;
                this.removeUploadingImage(newImage.id);
            }).catch(() => {
                window.Toast.error({message: "Failed to upload image"});
                this.removeUploadingImage(newImage.id);
            });
        },
        async handleUploadImageClick(image) {

            if (_stickerCanvasEditor) {
                this.loadingDesign = true;

                eventBus.$emit(eventBus.CLOSE_MODAL_ALL)

                _stickerCanvasEditor.clearCanvas();
                _stickerCanvasEditor.addBackground(image.url).then(() => {
                    getDieCutPath({
                        image_url: image.url,
                        outline: _stickerCanvasEditor.getDieCutMargin(),
                    })
                        .then((points) => {
                            _stickerCanvasEditor.setDieCutPath(points);
                        })
                        .finally(() => {
                            this.loadingDesign = false;
                            eventBus.$emit(eventBus.STICKER_EDIT);
                        });
                });
            }
        },
        handleDeleteImage(image) {
            this.customerImages = this.customerImages.filter(img => img.id !== image.id);
            deleteCustomerImages([image.id])
        }
    },
});
</script>

<template>
    <div class="w-full h-full flex flex-col">
        <div class="mb-2">
            <div class="border w-full rounded transition-all px-1 py-0.5 flex items-center relative">
                <div>
                    <svg
                        class="text-gray-400"
                        xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>
                <input v-model="search" class="inp-no-style flex-1 shrink-0 px-1 py-0.5 text-xs" placeholder="Search"/>
                <div v-if="search" @click="search = ''" class="absolute right-1 text-gray-500 hover:text-gray-600 cursor-pointer">
                    <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="14"/>
                </div>
            </div>
        </div>
        <div class="flex-1 h-1 py-2">
            <div class="h-full max-h-[75vh] overflow-y-auto tiny-scroll-bar">
                <div class="grid grid-cols-3 gap-1">
                    <div v-for="img in uploadingImages" :key="img.id" class="aspect-square border cursor-pointer rounded bg-gray-100 relative">
                        <img :src="img.url" class="w-full h-full object-contain" alt=""/>
                        <div v-if="img.loading" class="absolute inset-0 bg-black bg-opacity-20 flex flex-col">
                            <div class="rounded-full h-3 m-auto bg-gray-300 mx-1 overflow-hidden flex items-center justify-center relative">
                                <div class="bg-primary h-full absolute left-0 transition-all animate-pulse" :style="{ width: `${progress}%` }">
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-white"></span>
                                </div>
                                <small class="absolute text-white text-xs text-center whitespace-nowrap">{{ progress.toFixed() }} %</small>
                            </div>
                        </div>
                    </div>
                    <div v-for="image in customerImages" :key="image" class="aspect-square cursor-pointer border bg-gray-300 bg-opacity-20 border-gray-200 rounded relative"
                         @click="handleUploadImageClick(image)">
                        <gsb-image :src="image.url" alt=""/>
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
                    <div v-if="!uploadingImages.length && !customerImages.length && !loading" class="col-span-3 flex items-center justify-center h-20 text-gray-400">
                        {{ $t('No Images') }}
                    </div>
                    <div class="col-span-3 flex items-center justify-center h-20">
                        <spinner v-if="loading"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full mt-auto border border-dashed p-4 rounded-lg" :aria-disabled="uploadingImages.length < 5">
            <label for="image-uploader" v-if="rootProps" v-bind="rootProps" class="w-full flex flex-col justify-center items-center cursor-pointer">
                <span class="btn-builder mx-auto rounded-full w-3/4">
                  <input id="image-uploader" v-bind="inputProps" :accept="allowedFileTypes.join(',')" hidden/>
                  <svg-icon type="mdi" size="20" :path="mdiCloudUploadOutline" class="mr-1"/>
                  {{ $t("Upload Image(s)") }}
                </span>
                <span class="text-gray-400 text-xs text-center w-full py-4">Or Drag drop your files here</span>
            </label>
        </div>
    </div>
</template>

<style scoped>
</style>
