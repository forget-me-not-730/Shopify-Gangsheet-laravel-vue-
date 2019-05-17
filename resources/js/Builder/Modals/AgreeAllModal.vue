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
            hasQualityConfirm: false,
            qualityError: false,

            mdiCheckboxMarkedCircleOutline,
            mdiCloseBoxOutline,
            mdiClose
        }
    },
    watch: {
        open() {
            this.agree = false
            this.error = false

            if (this.open && this.data) {
                this.hasQualityConfirm = this.data.hasQualityConfirm
            }
        }
    },
    computed: {
        agree_label() {
            return this.shop.agree_label || this.shop.settings?.agree_label || DEFAULT_AGREE_LABEL
        },
        agree_check_flag() {
            return this.shop.agree_check_flag || this.shop.settings?.agree_check_flag
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

            if (this.agree) {
                this.$emit('confirm', false)
                this.$emit('close')
            } else {
                this.error = true
            }
        },
        addToCartAndCreateNewDesign() {
            if (this.hasQualityConfirm && !this.agreeQuality) {
                this.qualityError = true
                return
            }

            if (this.agree) {
                this.$emit('confirm', true)
                this.$emit('close')
            } else {
                this.error = true
            }
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col max-w-2xl bg-builder border sm:rounded mx-auto max-sm:h-full max-h-full">
            <div class="relative">
                <h4 class="text-2xl py-3 px-6 text-left font-semibold">Confirmation</h4>
                <button @click="$emit('close')" class="absolute top-0 right-0 p-3">
                    <svg-icon type="mdi" :path="mdiClose" size="24"/>
                </button>
            </div>
            <hr/>
            <div class="p-6 pt-4 text-left overflow-y-auto">
                <div v-if="hasQualityConfirm" class="mb-4">
                    <div class="text-sm mb-2 bg-orange-400 bg-opacity-50 border-l-4 border-orange-400 px-2 py-1 rounded">
                        The quality of some of your designs could potentially be impacted. Please confirm if you would like to proceed despite this.
                    </div>
                    <label class="w-full text-left cursor-pointer">
                        <input v-model="agreeQuality" type="checkbox" class="mr-2 mb-0.5"/>
                        <span :class="{'text-red-500': qualityError && !agreeQuality}">{{ $t('Print all designs anyway') }}</span>
                    </label>
                </div>

                <label class="w-full text-left cursor-pointer">
                    <input v-model="agree" type="checkbox" class="mr-2 mb-0.5"/>
                    <span :class="{'text-red-500': error}">{{ agree_label }}</span>
                </label>

                <div class="mt-5 flex justify-end space-y-3 flex-col">
                    <button @click="addToCartAndExit" class="btn-builder py-2 px-4 max-sm:text-sm max-sm:ml-1 max-sm:px-2">
                        {{ $t('Add to Cart') }} & {{ $t('Exit') }}
                    </button>
                    <button v-if="showNewDesignButton" :disabled="!currentDesign.name.trim()" @click="addToCartAndCreateNewDesign" class="btn-builder-outline justify-center py-2 px-4 max-sm:text-sm">
                        {{ $t('Add to Cart') }} & {{ $t('Create New Design') }}
                    </button>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
