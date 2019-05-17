<script>
import {defineComponent} from 'vue'
import Modal from './Modal.vue'
import {ART_BOARD_TYPES, DEFAULT_AGREE_LABEL} from '@/Builder/Utils/constants.js'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiCheckboxMarkedCircleOutline, mdiCloseBoxOutline, mdiClose} from '@mdi/js'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'

export default defineComponent({
    name: 'AgreeModal',
    components: {Modal, SvgIcon},
    mixins: [gangSheetMixin],
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
            agree: false,
            error: false,

            agreeQuality: false,
            qualityError: false,

            hasOverlappingImages: false,
            hasOffBoardImages: false,
            hasLowResolutionImages: false,

            mdiCheckboxMarkedCircleOutline,
            mdiCloseBoxOutline,
            mdiClose
        }
    },
    watch: {
        open() {
            if (this.open) {
                this.agree = false
                this.error = false

                this.qualityError = false
                this.agreeQuality = false

                if (window._gangSheetCanvasEditor) {
                    this.hasOverlappingImages = window._gangSheetCanvasEditor.hasOverlappingImages()
                    this.hasLowResolutionImages = window._gangSheetCanvasEditor.hasLowResolutionImages()
                    this.hasOffBoardImages = window._gangSheetCanvasEditor.hasOffBoardImages()
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
        },
        agree_check_flag() {
            return this.shop.agree_check_flag || this.shop.settings?.agree_check_flag
        },
        singleSubmit() {
            return this.data?.singleSubmit ?? false
        },
        confirmationLabel() {
            if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                return 'Checkout'
            }

            return this.builderSettings.confirmationButtonLabel;
        },
        showNewDesignButton() {
            if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                return false
            }

            return this.shopSettings.enableAddNewDesign
        }
    },
    methods: {
        addToCartAndExit() {
            if (this.hasQualityConfirm && !this.agreeQuality) {
                this.qualityError = true
                return
            }

            if (this.agree_check_flag) {
                if (this.agree) {
                    this.$emit('confirm', false)
                    this.$emit('close')
                } else {
                    this.error = true
                }
            } else {
                this.$emit('confirm', false)
                this.$emit('close')
            }
        },
        addToCartAndCreateNewDesign() {
            if (this.hasQualityConfirm && !this.agreeQuality) {
                this.qualityError = true
                return
            }

            if (this.agree_check_flag) {
                if (this.agree) {
                    this.$emit('confirm', true)
                    this.$emit('close')
                } else {
                    this.error = true
                }
            } else {
                this.$emit('confirm', true)
                this.$emit('close')
            }
        },
        handleDesignNameFocusOut() {
            if (window._gangSheetCanvasEditor) {
                window._gangSheetCanvasEditor.name = this.currentDesign.name
            }
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col max-w-2xl bg-builder border sm:rounded mx-auto max-sm:h-full max-h-full">
            <div class="relative">
                <h4 class="text-2xl py-3 px-6 text-left font-semibold">
                    {{ $t('Confirmation') }}
                </h4>
                <button @click="$emit('close')" class="absolute top-0 right-0 p-3">
                    <svg-icon type="mdi" :path="mdiClose" size="24"/>
                </button>
            </div>
            <hr/>
            <div class="p-6 pt-4 text-left overflow-y-auto">

                <div class="mb-5">
                    <div class="text-left w-full">{{ $t('Design Name') }} <small class="text-red-500 text-xl">*</small></div>
                    <input type="text" v-model="currentDesign.name" required
                           @focusout="handleDesignNameFocusOut"
                           :class="[Boolean(currentDesign.name) ? '' : 'border-red-500 focus:border-red-500 focus:ring focus:ring-red-500']"
                           class="inp-builder w-full mt-1"/>
                </div>

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
                        <input v-model="agreeQuality" type="checkbox" class="mr-2 mb-0.5 "/>
                        <span :class="{'text-red-500': qualityError && !agreeQuality}">{{ $t('Print Anyway') }}</span>
                    </label>
                </div>

                <template v-if="this.agree_check_flag">
                    <label class="w-full text-left cursor-pointer">
                        <input v-model="agree" type="checkbox" class="mr-2 b-0.5"/>
                        <span :class="{'text-red-500': error}">{{ agree_label }}</span>
                    </label>
                </template>

                <div class="mt-5 flex flex-col space-y-3 max-sm:flex-col-reverse">
                    <template v-if="singleSubmit">
                        <button :disabled="!currentDesign.name.trim()" @click="addToCartAndCreateNewDesign" class="btn-builder py-2 px-4 max-sm:text-sm">
                            {{ $t('Add to Cart') }}
                        </button>
                    </template>
                    <template v-else>
                        <button :disabled="!currentDesign.name.trim()" @click="addToCartAndExit" class="btn-builder py-2 px-4 max-sm:text-sm ">
                            {{ $t(confirmationLabel) }}
                        </button>
                        <button v-if="showNewDesignButton" :disabled="!currentDesign.name.trim()" @click="addToCartAndCreateNewDesign"
                                class="btn-builder-outline justify-center py-2 px-4 max-sm:text-sm ">
                            {{ $t('Add to Cart') }} & {{ $t('Create New Design') }}
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
