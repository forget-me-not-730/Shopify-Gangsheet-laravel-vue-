<script>
import {defineComponent} from 'vue'
import Modal from "./Modal.vue";
import builderMixin from "@/Builder/Mixins/builderMixin";
import {DEFAULT_AGREE_LABEL} from "@/Builder/Utils/constants.js";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiCloseBoxOutline, mdiCheckboxMarkedCircleOutline} from '@mdi/js'

export default defineComponent({
    name: "DesignQualityConfirmModal",
    components: {Modal, SvgIcon},
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            agreeQuality: false,
            qualityError: false,

            hasOverlappingImages: false,
            hasOffBoardImages: false,
            hasLowResolutionImages: false,

            mdiCloseBoxOutline,
            mdiCheckboxMarkedCircleOutline
        }
    },
    watch: {
        open() {
            if (this.open) {
                this.agree = false
                this.error = false
                this.qualityError = false
                this.agreeQuality = false

                if (_gangSheetCanvasEditor) {
                    this.hasOverlappingImages = _gangSheetCanvasEditor.hasOverlappingImages()
                    this.hasLowResolutionImages = _gangSheetCanvasEditor.hasLowResolutionImages()
                    this.hasOffBoardImages = _gangSheetCanvasEditor.hasOffBoardImages()
                }
            }
        }
    },
    computed: {
        agree_label() {
            return this.shop.agree_label || this.shop.settings?.agree_label || DEFAULT_AGREE_LABEL
        },
        hasQualityConfirm() {
            return this.hasOverlappingImages || this.hasLowResolutionImages || this.hasOffBoardImages
        }
    },
    methods: {
        handleConfirm() {

            if (this.hasQualityConfirm && !this.agreeQuality) {
                this.qualityError = true
                return;
            }

            this.$emit('confirm')
            this.$emit('close')
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col max-w-xl bg-builder border sm:rounded mx-auto">
            <h4 class="text-xl py-3 px-6 text-left font-semibold">{{ $t('Quality Confirmation') }}</h4>
            <hr/>
            <div class="p-6 pt-4 text-left">
                <div class="mb-2">
                    <div class="flex items-center space-x-2" :class="{'text-red-500 animate-pulse': qualityError && hasOverlappingImages && !agreeQuality }">
                        <svg-icon v-if="hasOverlappingImages" type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-red-500"/>
                        <svg-icon v-else type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-green-500"/>
                        <span>{{ $t('No overlapping images.') }}</span>
                    </div>
                    <div class="flex items-center space-x-2" :class="{'text-red-500 animate-pulse': qualityError && hasLowResolutionImages && !agreeQuality }">
                        <svg-icon v-if="hasLowResolutionImages" type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-red-500"/>
                        <svg-icon v-else type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-green-500"/>
                        <span>{{ $t('No low-resolution images.') }}</span>
                    </div>
                    <div class="flex items-center space-x-2" :class="{'text-red-500 animate-pulse': qualityError && hasOffBoardImages && !agreeQuality }">
                        <svg-icon v-if="hasOffBoardImages" type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-red-500"/>
                        <svg-icon v-else type="mdi" :path="mdiCheckboxMarkedCircleOutline" size="18" class="text-green-500"/>
                        <span>{{ $t('No items overlapping the artboard.') }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label v-if="hasQualityConfirm" class="w-full text-left cursor-pointer">
                        <input v-model="agreeQuality" type="checkbox" class="mr-2 mb-1"/>
                        <span :class="{'text-red-500': qualityError && !agreeQuality}">{{ $t('Print Anyway') }}</span>
                    </label>
                </div>

                <div class="mt-5 flex justify-end space-x-4">
                    <button @click="$emit('close')" class="btn-builder-outline">
                        {{ $t('Close') }}
                    </button>
                    <button @click="handleConfirm" class="btn-builder">
                        {{ $t('Continue') }}
                    </button>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
