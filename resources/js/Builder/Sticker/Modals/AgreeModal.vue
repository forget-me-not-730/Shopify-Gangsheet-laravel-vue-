<script>
import {defineComponent} from "vue";
import Modal from "./Modal.vue";
import {DEFAULT_AGREE_LABEL} from "@/Builder/Utils/constants.js";
import stickerMixin from "@/Builder/Mixins/stickerMixin";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default defineComponent({
    name: "AgreeModal",
    components: {CloseIcon, Modal},
    mixins: [stickerMixin],
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        data: {
            type: [Object, null],
            default: null,
        },
    },
    data() {
        return {
            agree: false,
            error: false,
            designName: null,
        };
    },
    watch: {
        open: {
            immediate: true,
            handler() {
                this.agree = false;
                this.error = false;
                this.designName = this.currentSticker?.name ?? "New Sticker";
            },
        },
    },
    computed: {
        agree_label() {
            return (
                this.shop.agree_label ||
                this.shop.settings?.agree_label ||
                DEFAULT_AGREE_LABEL
            );
        },
        agree_check_flag() {
            return this.shop.agree_check_flag || this.shop.settings?.agree_check_flag;
        },
    },
    methods: {
        addToCart() {
            if (this.agree_check_flag) {
                if (this.agree) {
                    this.$emit("confirm", true);
                    this.$emit("close");
                } else {
                    this.error = true;
                }
            } else {
                this.$emit("confirm", true);
                this.$emit("close");
            }
        },
        handleDesignNameFocusOut() {
            if (_stickerCanvasEditor) {
                _stickerCanvasEditor.name = this.designName;
            }
        },
    },
});
</script>

<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col max-w-2xl bg-builder border sm:rounded mx-auto max-sm:h-full max-h-full">
            <div class="flex justify-between items-center relative px-4 py-2">
                <h1 class="text-lg font-bold">
                    {{ $t('Confirmation') }}
                </h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="p-6 pt-4 text-left overflow-y-auto">
                <div class="mb-5">
                    <div class="text-left w-full">
                        {{ $t("Sticker Name") }}
                        <small class="text-red-500 text-xl">*</small>
                    </div>
                    <input
                        type="text"
                        v-model="designName"
                        required
                        @focusout="handleDesignNameFocusOut"
                        class="block w-full mt-2 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20"
                    />
                </div>

                <template v-if="this.agree_check_flag">
                    <label class="w-full text-left cursor-pointer">
                        <input v-model="agree" type="checkbox" class="mr-2"/>
                        <span :class="{ 'text-red-500': error }">{{ agree_label }}</span>
                    </label>
                </template>
            </div>
            <hr/>
            <div class="mt-5 flex justify-end max-sm:space-y-4 pb-3 px-6 max-sm:flex-col-reverse">
                <button
                    @click="$emit('close')"
                    class="btn-builder-outline max-sm:mt-4 flex justify-center rounded py-2 px-4 max-sm:text-sm max-sm:ml-1 max-sm:px-2"
                >
                    {{ $t("Close") }}
                </button>
                <button
                    :disabled="!designName"
                    @click="addToCart"
                    class="btn-builder py-2 px-4 max-sm:text-sm max-sm:ml-1 max-sm:px-2 ml-2"
                >
                    {{ $t("Add to Cart") }}
                </button>
            </div>
        </div>
    </modal>
</template>

<style scoped>
</style>
