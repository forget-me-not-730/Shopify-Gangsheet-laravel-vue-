<script>
import {defineComponent} from 'vue'
import Modal from '@/Builder/Modals/Modal.vue'
import Spinner from '@/Components/Spinner.vue'
import {Cropper, CircleStencil, RectangleStencil} from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import builderMixin from '@/Builder/Mixins/builderMixin'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiCheck} from '@mdi/js'

export default defineComponent({
    name: 'ImageCropperModal',
    components: {Cropper, Spinner, Modal, RectangleStencil, CircleStencil, SvgIcon},
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            required: true
        },
        data: {
            type: [Object, null],
            default: null
        },
        imageSrc: {
            type: String,
            default: null
        }
    },
    data() {
        return {
            loading: true,
            cropping: false,
            result: null,
            options: {
                stencil: 'RectangleStencil',
                aspectRatio: 1
            },

            mdiCheck
        }
    },
    watch: {
        options: {
            deep: true,
            handler() {
                this.$nextTick(() => {
                    if (this.$refs.cropper) {
                        this.$refs.cropper.refresh();
                    }
                });
            }
        },
        open() {
            if (this.open) {
                if (this.data) {
                    this.result = null
                    this.cropping = false
                }
            }
        }
    },
    computed: {
        classes() {
            return {
                body: '!max-w-2xl'
            }
        }
    },
    methods: {
        handleChange({canvas}) {
            this.result = canvas.toDataURL()
        },
        handleApply() {
            const {canvas} = this.$refs.cropper.getResult()
            if (canvas) {
                const imageData = canvas.toDataURL()
                this.$emit('cropped', imageData)
            }
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')" :classes="classes">
        <div class="bg-white border text-left sm:rounded w-full h-full flex flex-col max-h-full">
            <div class="flex justify-between relative px-4 pt-2 flex-shrink-0">
                <h1 class="text-xl font-bold mb-3">Crop Image</h1>
                <div class="absolute right-[14px] top-[3px] text-3xl cursor-pointer" @click="$emit('close')">
                    &times;
                </div>
            </div>
            <hr/>
            <div class="p-4 overflow-hidden flex-1 h-1 flex max-sm:flex-col max-sm:overflow-y-auto">
                <div class="flex-1 h-full w-full sm:w-1 relative  transparent-pattern flex items-center justify-center">
                    <div class="absolute inset-0 bg-black flex items-center justify-center">
                        <spinner class="!w-6 !h-6 m-auto"/>
                    </div>
                    <cropper
                        v-if="this.imageSrc"
                        ref="cropper"
                        :src="this.imageSrc"
                        :canvas="{
                            maxWidth: 400,
                            maxHeight: 400
                        }"
                        :stencil-component="$options.components[options.stencil]"
                        :stencil-props="{
                            aspectRatio: options.aspectRatio
                        }"                        
                        @change="handleChange"
                    />
                </div>
            </div>
            <div class="pr-4 pb-4">
                <button class="btn-primary w-[200px] float-right" @click="handleApply" :disabled="cropping">
                    <spinner v-if="cropping" class="mr-2"/>
                    Crop
                </button>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
