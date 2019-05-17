<script>
import {defineComponent} from 'vue'
import Modal from '@/Builder/Modals/Modal.vue'
import Spinner from '@/Components/Spinner.vue'
import {Cropper, CircleStencil, RectangleStencil} from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import {uploadBase64Image} from '@/Builder/Apis/builderApi'
import {getSessionId} from '@/Builder/Utils/helpers'
import builderMixin from '@/Builder/Mixins/builderMixin'
import normalizeUrl from 'normalize-url'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiCheck} from '@mdi/js'
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default defineComponent({
    name: 'CropImageModal',
    components: {CloseIcon, Cropper, Spinner, Modal, RectangleStencil, CircleStencil, SvgIcon},
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            required: true
        },
        data: {
            type: [Object, null],
            default: null
        }
    },
    data() {
        return {
            loading: true,
            uploading: false,
            img: null,
            result: null,
            options: {
                stencil: 'RectangleStencil',
            },

            mdiCheck
        }
    },
    watch: {
        options: {
            deep: true,
            handler() {
                this.$refs.cropper.refresh()
            }
        },
        open() {
            if (this.open) {
                if (this.data) {
                    this.img = this.data.url
                    this.result = null
                    this.uploading = false
                }
            }
        }
    },
    computed: {
        classes() {
            return {
                body: '!max-w-5xl'
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
                this.uploading = true

                const ctx = canvas.getContext('2d')
                const cw = canvas.width
                const ch = canvas.height

                if (this.options.stencil === 'CircleStencil') {
                    ctx.globalCompositeOperation = 'destination-in'

                    ctx.fillStyle = '#000'
                    ctx.beginPath()
                    ctx.arc(cw / 2, ch / 2, ch / 2, 0, Math.PI * 2)
                    ctx.fill()
                    ctx.closePath()

                    ctx.globalCompositeOperation = 'source-over'
                }

                const imageData = canvas.toDataURL()
                uploadBase64Image({
                    image: imageData,
                    user_id: this.shop.id,
                    parent_id: this.data.id,
                    customer_id: this.customer?.id || null,
                    session_id: getSessionId(),
                    type: 'crop'
                }).then(res => {
                    if (res.success) {

                        const image = {
                            id: res.image.id,
                            thumb_url: normalizeUrl(res.image.thumb_url),
                            url: normalizeUrl(res.image.url),
                            width: res.image.width,
                            height: res.image.height,
                            resolution: res.image.resolution,
                        }

                        this.$emit('confirm', image)
                        this.$emit('close')
                    }
                }).finally(() => {
                    this.uploading = false
                })
            }
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')" :classes="classes">
        <div class="bg-builder border text-left sm:rounded w-full h-full flex flex-col max-h-full">
            <div class="flex justify-between items-center relative px-4 py-2 flex-shrink-0">
                <h1 class="text-xl font-bold">{{ $t('Crop Image') }}</h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="p-4 overflow-hidden flex-1 h-1 flex max-sm:flex-col max-sm:overflow-y-auto">
                <div class="flex-1 h-full w-full sm:w-1 relative  transparent-pattern flex items-center justify-center">
                    <div class="absolute inset-0 bg-black flex items-center justify-center">
                        <spinner class="!w-6 !h-6 m-auto"/>
                    </div>
                    <cropper
                        v-if="img"
                        ref="cropper"
                        :src="img"
                        :stencil-component="$options.components[options.stencil]"
                        @change="handleChange"
                    />
                </div>
                <div class="w-full sm:w-72 md:pl-4 h-full flex sm:flex-col max-sm:mt-5">

                    <div class="w-40 h-40 sm:w-[250px] sm:h-[250px] bg-black relative border overflow-hidden" :class="{'rounded-full': options.stencil === 'CircleStencil'}">
                        <img v-if="result" :src="result" class="object-contain w-full h-full z-50" alt="Crop Preview"/>
                    </div>

                    <div class="flex-1 text-sm">
                        <div class="sm:mt-5 flex justify-evenly">
                            <div class="flex items-center cursor-pointer" @click="options.stencil = 'RectangleStencil'">
                                <span class="gs-bg-primary w-6 h-6 mr-2 flex items-center justify-center">
                                    <svg-icon v-if="options.stencil === 'RectangleStencil'" type="mdi" :path="mdiCheck" size="20"/>
                                </span>
                                <span>{{ $t('Rectangle') }}</span>
                            </div>
                            <div class="flex items-center cursor-pointer" @click="options.stencil = 'CircleStencil'">
                                <span class="gs-bg-primary w-6 h-6 rounded-full mr-2 flex items-center justify-center">
                                    <svg-icon v-if="options.stencil === 'CircleStencil'" type="mdi" :path="mdiCheck" size="20"/>
                                </span>
                                <span>{{ $t('Circle') }}</span>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end w-full space-x-2">
                            <button class="btn-builder" @click="handleApply" :disabled="uploading">
                                <spinner v-if="uploading" class="mr-2"/>
                                {{ $t('Crop') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
