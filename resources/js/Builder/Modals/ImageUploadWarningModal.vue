<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import CloseIcon from '@/Builder/Icons/CloseIcon.vue';
import builderMixin from "@/Builder/Mixins/builderMixin";
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin';
import Spinner from '@/Builder/Sticker/Spinner.vue';
import AlertCircleOutlineIcon from "@/Builder/Icons/AlertCircleOutlineIcon.vue";
import {deleteCustomerImages, removeBgAndUpload} from "@/Builder/Apis/builderApi";
import eventBus from '@/Builder/Utils/eventBus'
import normalizeUrl from 'normalize-url'
import {getSessionId} from '@/Builder/Utils/helpers'

export default defineComponent({
    name: "ImageUploadWarmModal",
    components: {AlertCircleOutlineIcon, Modal, CloseIcon, Spinner},
    mixins: [builderMixin, gangSheetMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        },
        data: {
            type: [Object, null],
            default: null
        }
    },
    data() {
        return {
            hideImageBackgroundWarning: false,
            newImage: null,
            removeBg: false
        }
    },
    watch: {
        hideImageBackgroundWarning: {
            handler() {
                this.$gsb.setArtBoardSettings('enableImageBackgroundWarning', !this.hideImageBackgroundWarning)
            }
        }
    },
    methods: {
        handleDiscard() {
            if (this.removeBg) return;
            if (this.data?.image) {
                deleteCustomerImages([this.data?.image.id]);
            }
            this.$emit('close');
        },
        handleContinue() {
            if (this.removeBg) return;
            if (this.data?.image) {
                eventBus.$emit(eventBus.IMAGE_UPLOADED, this.data?.image);
                window._gangSheetCanvasEditor?._addImage(this.data?.image);
                eventBus.$emit(eventBus.IMAGE_REUPLOADED);
            }
            this.$emit('close');
        },
        async handleRemoveBg() {
            if (this.data?.image) {
                this.removeBg = true;
                const payload = {
                    image_id: this.data?.image.id,
                }
                const res = await removeBgAndUpload(payload);
                if (res?.success) {
                    this.newImage = {
                        id: res.image.id,
                        url: res.image.url,
                        thumb_url: res.image.thumb_url,
                        width: res.image.width,
                        height: res.image.height,
                        resolution: res.image.resolution,
                    }
                    eventBus.$emit(eventBus.IMAGE_UPLOADED, this.newImage);
                    window._gangSheetCanvasEditor?._addImage(this.newImage);
                    eventBus.$emit(eventBus.IMAGE_REUPLOADED);

                    this.$emit('close');
                } else {
                    this.$gsb.error('Failed to Remove Background.')
                    this.handleDiscard();
                }
                this.removeBg = false;
            } else {
                this.handleDiscard();
            }
        }
    }
})
</script>

<template>
    <modal :open="open" :classes="{ body: 'max-w-xl' }">
        <div class="flex flex-col bg-builder border sm:rounded max-h-full max-sm:h-full">
            <div class="flex justify-between items-center relative px-6 pt-2">
                <h1 class="font-bold text-gray-700 mb-3">{{ $t('Background Warning') }}</h1>
                <div class="absolute right-[14px] top-[10px] cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="p-4">
                <div class="bg-orange-400 bg-opacity-50 flex items-start p-1 rounded text-xs text-left">
                    <alert-circle-outline-icon class="w-4 h-4 inline-block mr-1 shrink-0 mt-0.5"/>
                    {{ $t('We detected a background in this image. We highly recommend editing the design with our background removal tool or replacing it with new artwork.') }}
                </div>
                <div class="flex w-full !justify-center mt-4">
                    <div class="relative w-full max-h-[68vh] overflow-y-auto scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                        <img v-if="data?.image?.url" :src="data?.image?.url" class="w-full object-contain">
                    </div>
                </div>
            </div>
            <hr/>
            <div class="w-full px-2 py-3 flex justify-between flex-wrap">
                <label class="space-x-2 flex items-center text-xs">
                    <input type="checkbox" v-model="hideImageBackgroundWarning"
                           :checked="hideImageBackgroundWarning"
                           class="border-gray-300 text-blue-600 focus:border-blue-600 focus:ring focus:ring-offset-0 focus:ring-blue-600 focus:ring-opacity-10">
                    <span class="font-medium text-gray-900">{{ $t(`Don't show again`) }}</span>
                </label>
                <div class="flex gap-2 ml-auto mt-2">
                    <button class="btn-builder-outline btn-sm" @click="handleDiscard()">{{ $t('Discard') }}</button>
                    <button class="btn-builder-outline btn-sm" @click="handleContinue()">{{ $t('Continue') }}</button>
                    <button class="btn-builder btn-sm" @click="handleRemoveBg()">
                        {{ $t('Remove Background') }}
                        <span v-if="removeBg" class="ml-2"><spinner/></span>
                    </button>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
