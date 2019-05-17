<script>
import {defineComponent} from 'vue'
import Modal from '@/Builder/Modals/Modal.vue'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import confirmationMixin from '@/Builder/Mixins/confirmationMixin'
import Spinner from '@/Components/Spinner.vue'
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import {getSessionId} from "@/Builder/Utils/helpers";
import {convertDimension} from '@/Builder/Utils/helpers'
import DeleteIcon from "@/Builder/Icons/DeleteIcon.vue";
import AlertCircleOutlineIcon from "@/Builder/Icons/AlertCircleOutlineIcon.vue";

export default defineComponent({
    name: 'CanvaExportDesignModal',
    components: {
        AlertCircleOutlineIcon,
        CloseIcon,
        Spinner,
        Modal,
        DeleteIcon
    },
    mixins: [gangSheetMixin, confirmationMixin],
    props: {
        open: {
            type: Boolean,
            required: true
        },
        data: {
            type: [Object, null],
            required: true
        }
    },
    data() {
        return {
            loading: false,
            width: 0,
            height: 0,
            exportedDesigns: [],
            selectedDesignId: null,
            isExporting: false,
            deletingDesignId: null,
            aspectRatio: 1,
            error: null
        }
    },
    computed: {
        classes() {
            return {
                body: '!max-w-2xl'
            }
        }
    },
    watch: {
        open(newValue) {
            if (newValue) {
                this.isExporting = false
                this.error = null
                this.getExportedCanvaDesigns();
            }
        },
        selectedDesignId(newValue) {
            const selectedDesign = this.exportedDesigns.find(design => design.id === newValue);
            if (selectedDesign) {
                this.width = convertDimension(selectedDesign.width, 'px', this.artBoardUnit);
                this.height = convertDimension(selectedDesign.height / this.data.design.page_count, 'px', this.artBoardUnit);
            }
        }
    },
    methods: {
        checkExistingDesign() {
            if (this.width > 0 && this.height > 0) {
                const widthInPx = convertDimension(this.width, this.artBoardUnit, 'px');
                const heightInPx = convertDimension(this.height, this.artBoardUnit, 'px');

                const existingDesign = this.exportedDesigns.find(design =>
                    Math.abs(design.width - widthInPx) < 3 ||
                    Math.abs(design.height / this.data.design.page_count - heightInPx) < 3
                );

                if (existingDesign) {
                    this.selectedDesignId = existingDesign.id;
                } else {
                    this.selectedDesignId = null;
                }
            }
        },
        getExportedCanvaDesigns() {
            this.loading = true;
            this.exportedDesigns = []
            this.selectedDesignId = null;
            this.aspectRatio = this.data.design.thumbnail.width / this.data.design.thumbnail.height;
            this.width = convertDimension(this.data.design.thumbnail.width, 'px', this.artBoardUnit);
            this.height = convertDimension(this.data.design.thumbnail.height, 'px', this.artBoardUnit);
            window.axios.get(route('builder.exported-canva-image'), {
                params: {
                    canva_id: this.data.design.id,
                    user_id: this.shop.id
                }
            }).then(res => {
                if (res.data.success && res.data.images.length) {
                    this.exportedDesigns = res.data.images;
                    this.checkExistingDesign();
                }
            }).catch(() => {
                this.$gsb.error('Failed to fetch exported designs');
            }).finally(() => {
                this.loading = false;
            });
        },
        preventNegative(event) {
            if (event.key === '-') {
                event.preventDefault();
            }
        },
        handleInputWidth(e) {
            const width = Number(e.target.value)
            if (!isNaN(width) && width > 0) {
                this.width = width;
                this.height = (width / this.aspectRatio).toFixed(2);
                this.checkExistingDesign();
            }
        },
        handleInputHeight(e) {
            const height = Number(e.target.value)
            if (!isNaN(height) && height > 0) {
                this.height = height;
                this.width = (height * this.aspectRatio).toFixed(2);
                this.checkExistingDesign();
            }
        },
        handleExportDesign() {
            if (this.width <= 0 || this.height <= 0) {
                this.$gsb.error('Width and height must be greater than zero');
                return;
            }
            this.isExporting = true;
            if (this.selectedDesignId) {
                const selectedDesign = this.exportedDesigns.find(design => design.id === this.selectedDesignId);
                if (selectedDesign) {
                    const image = {
                        id: selectedDesign.id,
                        url: selectedDesign.url,
                        thumb_url: selectedDesign.thumb_url,
                        width: selectedDesign.width,
                        height: selectedDesign.height,
                        canvaId: selectedDesign.canva_id,
                        type: 'canva'
                    }
                    if (!this.images.find(img => img.id === image.id)) {
                        this.images.push({...image})
                    }
                    window._gangSheetCanvasEditor?.addImage(image)
                    this.$emit('close')
                }
                this.isExporting = false;
            } else {
                window.axios.post(route('builder.upload-canva-image'), {
                    user_id: this.shop.id,
                    customer_id: this.customer?.id ?? '',
                    canva_id: this.data.design.id,
                    session_id: getSessionId(),
                    thumb_url: this.data.design.thumbnail.url,
                    title: this.data.design.title,
                    width: convertDimension(this.width, this.artBoardUnit, 'px'),
                    height: convertDimension(this.height, this.artBoardUnit, 'px'),
                    page_count: this.data.design.page_count
                }).then(res => {
                    if (res.data.success) {
                        const image = {
                            id: res.data.image.id,
                            url: res.data.image.url,
                            thumb_url: res.data.image.thumb_url,
                            width: res.data.image.width,
                            height: res.data.image.height,
                            canvaId: res.data.image.canva_id,
                            type: 'canva'
                        }
                        if (!this.images.find(img => img.id === image.id)) {
                            this.images.push({...image})
                        }
                        window._gangSheetCanvasEditor?.addImage(image)
                        this.$emit('close')
                    } else {
                        this.error = res.data.error || 'Failed to upload image';
                        if (!this.open) {
                            this.$gsb.error(this.error);
                        }
                    }
                }).catch(() => {
                    this.$gsb.error('Failed to upload image');
                }).finally(() => {
                    this.isExporting = false;
                })
            }
        },
        close() {
            this.$emit('close')
        },
        deleteDesign(designId) {
            this.confirmation = {
                title: 'Delete Design',
                description: 'Are you really sure you want to delete selected design?',
                onConfirm: () => {
                    this.deletingDesignId = designId;
                    window.axios.delete(route('builder.delete-canva-image'), {
                        params: {
                            id: designId,
                            user_id: this.shop.id,
                            canva_id: this.data.design.id
                        }
                    }).then(res => {
                        if (res.data.success && res.data.deletedId) {
                            this.exportedDesigns = this.exportedDesigns.filter(design => design.id !== res.data.deletedId);
                            if (this.selectedDesignId === res.data.deletedId) {
                                this.selectedDesignId = null;
                            }
                        } else {
                            this.$gsb.error(res.data.error || 'Failed to delete design');
                        }
                    }).finally(() => {
                        this.deletingDesignId = null;
                    })
                }
            }
        },
        formatDimensions(width, height) {
            const widthInUnit = convertDimension(width, 'px', this.artBoardUnit)
            const heightInUnit = convertDimension(height, 'px', this.artBoardUnit)
            return `${widthInUnit}${this.artBoardUnit} x ${heightInUnit}${this.artBoardUnit}`
        }
    }
})
</script>

<template>
    <modal :open="open" :classes="classes">
        <div class="flex flex-col bg-builder border sm:rounded max-sm:h-full sm:max-h-full min-h-[800px]" :class="{ 'pointer-events-none': isExporting }">
            <div class="flex justify-between items-center relative px-4 py-3">
                <h5 class="text-xl font-bold">{{ $t('Export Canva Design') }}</h5>
                <div class="cursor-pointer" @click="$emit('close')" :class="{ 'pointer-events-none': isExporting }">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="flex-1 flex flex-col px-6 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-500 scrollbar-thumb-rounded scrollbar-track-gray-300">
                <div class="flex flex-col mt-5">
                    <p>
                        {{ $t('Select a previously exported design or export a new design') }}
                    </p>
                    <div class="flex flex-row space-x-6 py-5">
                        <div class="flex flex-col w-1/2">
                            <label for="width">{{ $t('Width') }}</label>
                            <div class="mt-1 w-full inp-builder">
                                <input type="number" id="width" v-model="width" step="0.01" @input="handleInputWidth" @keypress="preventNegative" class="inp-no-style p-3 flex-1"
                                       :placeholder="`Enter width in ${artBoardUnit}`" :disabled="isExporting"/>
                                <span class="text-sm px-2">{{ artBoardUnit }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col w-1/2">
                            <label for="height">{{ $t('Height') }}</label>
                            <div class="mt-1 w-full inp-builder">
                                <input type="number" id="height" v-model="height" step="0.01" @input="handleInputHeight" @keypress="preventNegative" class="inp-no-style p-3 flex-1"
                                       :placeholder="`Enter height in ${artBoardUnit}`" :disabled="isExporting"/>
                                <span class="text-sm px-2">{{ artBoardUnit }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="error" class="bg-red-500 flex text-white items-start p-1 rounded text-sm">
                        <alert-circle-outline-icon class="w-4 h-4 inline-block mr-1 shrink-0 mt-0.5"/>
                        {{ $t(error) }}
                    </div>
                </div>

                <div v-if="loading" class="flex items-center justify-center py-10">
                    <spinner class="w-8 h-8"/>
                </div>

                <template v-else>
                    <div v-if="exportedDesigns.length" class="mt-5">
                        <h2 class="font-semibold text-gray-800">{{ $t('Previously Exported Designs') }}</h2>
                        <ul class="mt-2">
                            <li v-for="design in exportedDesigns" :key="design.id"
                                class="border p-3 rounded mb-3 flex items-center justify-between transition-all duration-200"
                                :class="[selectedDesignId === design.id ? 'gs-border-primary': 'border-gray-300']">
                                <div class="flex items-center">
                                    <input type="radio" :id="`design-${design.id}`" :value="design.id" v-model="selectedDesignId" class="mr-3" :disabled="isExporting">
                                    <label :for="`design-${design.id}`" class="flex items-center cursor-pointer" :class="{ 'pointer-events-none': isExporting }">
                                        <div class="relative">
                                            <img :src="design.thumb_url" alt="Thumbnail"
                                                 class="w-16 h-16 mr-3 rounded transition-transform duration-200 object-contain">
                                        </div>
                                        <div>
                                            <div>
                                                {{ design.title }}
                                            </div>
                                            <div class="text-xs opacity-75">
                                                {{ formatDimensions(design.width, design.height) }}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <button
                                    @click="deleteDesign(design.id)"
                                    class="btn-danger btn-sm px-1 font-thin disabled:bg-gray-300"
                                    :disabled="isExporting || deletingDesignId === design.id"
                                >
                                    <delete-icon size="20"/>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-10">
                        <h2 class="text-center opacity-50">{{ $t('No previously exported designs') }}</h2>
                    </div>
                </template>
            </div>

            <div class="flex justify-end py-5 px-6 space-x-5">
                <button @click="$emit('close')" class="btn-builder-outline" :disabled="isExporting">
                    {{ $t('Cancel') }}
                </button>
                <button @click="handleExportDesign" class="btn-builder" :disabled="isExporting">
                    <spinner v-if="isExporting" class="mr-1"/>
                    <span>{{ selectedDesignId ? $t('Use Selected Design') : $t('Export New Design') }}</span>
                </button>
            </div>
        </div>

    </modal>
</template>

<style scoped>

</style>
