<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col bg-builder border sm:rounded max-h-full max-w-sm mx-auto my-[300px]">
            <div class="flex justify-between items-center relative px-4 py-2">
                <h1 class="text-lg font-bold">{{ $t('Auto Duplicate') }}</h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="py-2 px-4">
                <p class="mb-2 text-left">{{ $t('Add quantity you want to add') }}</p>
                <input v-model="imageQuantity" type="number" class="w-full inp-builder"/>
                <div class="flex items-center justify-between mt-2">
                    <label class="items-center flex" :class="[builderSettings.lockMargin ? 'cursor-not-allowed' : 'cursor-pointer']">
                        <input v-model="marginEnabled" type="checkbox" class="focus:ring-0 rounded-sm w-4 h-4" :disabled="builderSettings.lockMargin">
                        <span class="ml-2">{{ $t('Apply Margin') }}</span>
                    </label>
                    <div v-if="marginEnabled" class="w-48 inp-builder">
                        <input type="number"
                               v-model="margin"
                               :step="artBoardUnit === 'mm' ? 1 : 0.1"
                               min="0"
                               @input="handleMarginChange"
                               class="w-full inp-no-style text-sm"
                               :disabled="builderSettings.lockMargin">
                        {{ artBoardUnit }}
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 my-2 px-4 ">
                <button class="btn-builder-outline justify-center w-20" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </button>
                <button class="btn-builder btn-sm cursor-pointer w-20" @click="handleDuplicate">{{ $t('Add') }}</button>
            </div>
        </div>
    </modal>
</template>

<script>

import Modal from "./Modal.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import builderMixin from "@/Builder/Mixins/builderMixin";

export default {
    name: "AddQuantityModal",
    components: {CloseIcon, Modal},
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            imageQuantity: 1,
            marginEnabled: false,
            margin: 0,
        }
    },
    methods: {
        handleDuplicate() {
            const canvas = _gangSheetCanvasEditor
            let quantity = Number(this.imageQuantity || 1)
            if (this.maxAllowedFiles > -1) {
                const maxFiles = Math.max(this.maxAllowedFiles - canvas.size(), 0)
                if (quantity > maxFiles) {
                    quantity = maxFiles
                }
            }
            if (quantity > 0) {
                canvas.autoFill(quantity, this.marginEnabled ? this.margin : 0)
                this.isAutoFillApplied = true
            } else {
                this.$gsb.error('Artboard is fully filled.')
            }
            this.$emit('close')
        },
        handleMarginChange(e) {
            if (Number(e.target.value) < 0) {
                this.margin = 0
            }
        },
    }
}
</script>
