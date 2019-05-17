<script>
import {defineComponent} from 'vue'
import GsbImage from "@/Builder/Components/GsbImage.vue";
import {mdiSquareEditOutline} from '@mdi/js';
import SvgIcon from '@jamescoyle/vue-icon';
import PromptMixin from "@/Mixins/PromptMixin";
import moment from "moment-timezone";

export default defineComponent({
    name: "GalleryImageItem",
    components: {GsbImage, SvgIcon},
    mixins: [PromptMixin],
    props: {
        image: {
            type: Object,
            required: true
        },
        index: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            editing: false,
            mdiSquareEditOutline,
            isDragging: false,
        }
    },
    methods: {
        moment,
        handleEditable() {
            this.editing = true;
            this.$nextTick(() => {
                const node = this.$refs.editableInput;
                if (node) {
                    node.focus();

                    const range = document.createRange();
                    range.selectNodeContents(node);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            });
        },
        updateImageTitle() {
            this.editing = false;

            if (this.image.title === '') {
                this.image.title = 'Untitled'
            }

            axios.put(route('merchant.image.update-title', {image_id: this.image.id}), {title: this.image.title})
                .then((res) => {
                    // ignore
                })
        },
        handleInput(e) {
            this.image.title = e.target.innerText.substring(0, 80).trim();
            if (e.target.innerText.length > 80) {
                e.target.innerText = this.image.title
            }
        },
        handleKeyDown(e) {
            const key = e.which || e.keyCode

            if (key === 13) {
                this.editing = false;
            }
        },
        handleGenerateWatermark(image) {
            image.processing = true
            axios.post(route('merchant.image.generate-watermark', {
                image_id: image.id
            })).then((res) => {
                if (res.data.success) {
                    image.processing = false
                    image.name = res.data.name
                } else {
                    image.processing = false
                    image.name = ''
                }
            }).catch(() => {
                image.processing = false
                image.name = ''
            })
        },
        handleDragStart(event) {
            event.dataTransfer.setData('imageId', this.image.id);
            event.dataTransfer.effectAllowed = 'move';
            this.isDragging = true;
        },
        handleDragOver(event) {
            event.preventDefault();
            this.isDragging = false;
        }
    }
})
</script>

<template>
    <div :class="['flex gallery-item p-2 shadow relative hover:cursor-pointer select-none', { 'bg-blue-200': isDragging }]" draggable="true" @dragstart="handleDragStart" @dragover="handleDragOver">
        <div class="w-20 h-24 shrink-0 mr-1 flex flex-col">
            <label class="w-full h-20 cursor-pointer" @mousedown.prevent="" @mousemove.prevent="">
                <input type="checkbox" :value="image.id" v-model="pageData.selectedImages" class="absolute top-1 left-1 z-10">
                <gsb-image :src="image.preview_url" class="w-full h-full border border-gray-300"/>
            </label>
            <div v-if="!image.name" class="w-full">
                <div v-if="image.processing" class="bg-teal-200 text-xs text-teal-700 w-full flex items-center justify-center">
                    <span class="relative flex h-1.5 w-1.5 mr-1">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-teal-500"></span>
                    </span>
                    <span>watermark</span>
                </div>
                <div v-else class="bg-yellow-400 text-xs w-full">no watermark</div>
            </div>
        </div>
        <div class="flex-1 w-1">
            <div class="flex" @click.prevent.stop="">
                <div ref="editableInput" :contenteditable="editing" class="break-word py-1 px-2 cursor-text" :class="{'ring-2 ring-gray-600 rounded w-full': editing}"
                     @input="handleInput"
                     @mousedown.stop=""
                     @mousemove.stop=""
                     @keydown.stop="handleKeyDown"
                     @focusout="updateImageTitle">
                    {{ image.title }}
                </div>
                <div v-if="!editing" class="p-1 text-gray-600 mt-1 cursor-pointer" @click.prevent.stop="handleEditable">
                    <svg-icon type="mdi" :path="mdiSquareEditOutline" size="16"/>
                </div>
            </div>
            <div class="flex space-x-2 items-center text-gray-400 text-xs ml-2 my-1">
                <div>Date: {{ moment(image.created_at).format('l') }}</div>
                <div v-if="image.width && image.height">Size: {{ image.width }} x {{ image.height }}</div>
            </div>
            <div class="flex space-x-2 ml-2 text-xs">
                <div>{{ image.used_count || 0 }} used</div>
                <div>
                    <span v-if="image.best_seller" class="text-green-500 bg-green-200 text-xs px-2 rounded-full">
                        Best Seller
                    </span>
                </div>
                <div>
                    <span class="text-green-500 bg-green-200 text-xs px-2 rounded-full" :class="image.status  ? 'text-white bg-green-500' : 'text-white bg-red-500'">
                        {{ image.status ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            <div v-if="image.category" class="mt-1 ml-2">
                <span class="bg-primary text-white rounded px-2 text-xs">{{ image.category.name }}</span>
            </div>
            <div v-if="image.tags?.length" class="mt-1 space-x-1 ml-2">
                <span v-for="tag in image.tags" :key="tag.id" class="text-xs text-gray-500 bg-gray-200 px-1 rounded mr-1">
                    {{ tag.name }}
                </span>
            </div>

            <button v-if="!image.name && !image.processing" class="ml-2 mt-1 text-sm border px-2 rounded" @click="handleGenerateWatermark(image)">
                Generate Watermark
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
