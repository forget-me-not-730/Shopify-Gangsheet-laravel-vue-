<script>
import {defineComponent} from 'vue'
import Modal from '@/Builder/Modals/Modal.vue'
import PromptMixin from '@/Mixins/PromptMixin.js'
import Spinner from "@/Components/Spinner.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiClose, mdiCloudUploadOutline} from '@mdi/js'
import DangerButton from "@/Components/DangerButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ImageCropperModal from '@/Components/Modals/ImageCropperModal.vue';
import Toggle from '@/Components/Toggle.vue';
export default defineComponent({
    name: 'InputPrompt',
    components: { Modal, Spinner, SvgIcon, DangerButton, PrimaryButton, ImageCropperModal, Toggle },
    mixins: [PromptMixin],
    data() {
        return {
            mdiCloudUploadOutline,
            mdiClose,
            hasOldImage: false,
            oldImageUrl: null,
            imageInput: null,
            imageValue: null,
            showCropper: false,
            croppedImage: null,
            enableColorOverlay: false
        };
    },
    watch: {
        prompt(newPrompt) {
            if (newPrompt && newPrompt.image_url) {
                this.hasOldImage = true
                this.oldImageUrl = newPrompt.image_url + "?v=" + Date.now()
            }
        }
    },
    methods: {
        onFileChange(event) {
            const fileInput = this.$refs.fileInput;
            const file = fileInput.files[0];
            if (file) {
                this.imageInput = URL.createObjectURL(file)
                this.showCropper = true;
            }
            this.$refs.fileInput.value = '';
        },
        handleCroppedImage(croppedImage) {
            this.showCropper = false
            this.imageValue = croppedImage
        },
        handleImageError(e) {
            this.hasOldImage = false
            this.oldImageUrl = null
        }
    }
});
</script>

<template>
    <Modal :show="Boolean(prompt)">
        <div v-if="Boolean(prompt)" class="rounded-2xl bg-white w-full max-w-lg mx-auto overflow-hidden border">
            <div class="bg-gray-100 py-3 px-4 flex items-center justify-between">
                <h2 class="text-base text-left font-semibold">
                    {{ prompt.title || 'Enter value' }}
                </h2>
                <div class="flex items-center justify-center cursor-pointer text-gray-700" @click.stop.prevent="handleCancel">
                    <svg-icon type="mdi" :path="mdiClose" size="20"/>
                </div>
            </div>
            <div class="border-t text-sm text-left p-4">
                <input v-model.trim="inputValue" :placeholder="prompt.placeholder" type="text"
                       class="w-full mt-2 py-2 px-4 border rounded" :class="{'border-red-500': error}">
                <span v-if="error" class="text-red-500 text-2xs">{{ error }}</span>
            </div>
            <div class="flex justify-between items-center border-b px-4 pb-2">
                <span class="font-semibold text-sm text-gray-900">Enable Color Overlay</span>
                <toggle v-model="enableColorOverlay" @update:modelValue="handleColorOverlayChange" />
            </div>
            <div class="w-full p-4">
                <div class="text-start text-sm">Upload category image (optional)</div>
                <label class="rounded-lg flex flex-col justify-center space-y-1 items-center w-full overflow-hidden mt-2 border border-dashed border-gray-500 h-30">

                    <div v-if="oldImageUrl || imageValue" class="mt-1 mb-1">
                        <div class="relative rounded-lg h-full w-full overflow-hidden border shrink-0 flex items-center justify-center mx-auto">
                            <div>
                                <svg-icon type="mdi" class="absolute top-1 right-1 cursor-pointer rounded-full bg-gray-400" :path="mdiClose" size="25" @click.stop.prevent="handleCancelImage"/>
                                <img :src="imageValue ? imageValue : oldImageUrl" class="w-full h-full " alt="Image" @error="handleImageError" />
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col space-y-1 items-center  text-xs py-4">
                        <input type="file" @change="onFileChange" ref="fileInput" hidden />
                        <svg-icon type="mdi" :path="mdiCloudUploadOutline" size="36" class="cursor-pointer" />
                        <div class="btn-primary rounded-full">
                            Browse Files
                        </div>
                        <b>Files supported .png & .jpg</b>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3 py-3 px-4 border-t bg-gray-50">
                <DangerButton
                    @click.stop.prevent="handleCancel"
                    :disabled="loading"
                >
                    Cancel
                </DangerButton>
                <PrimaryButton
                    @click.stop="handleConfirm"
                    :disabled="loading"
                >
                    <spinner v-if="loading" class="mr-1"/>
                    {{ prompt.action || 'Ok' }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
    <ImageCropperModal
        :open="showCropper"
        :imageSrc="imageInput"
        @close="showCropper = false"
        @cropped="handleCroppedImage"
    />
</template>

<style scoped>

</style>
