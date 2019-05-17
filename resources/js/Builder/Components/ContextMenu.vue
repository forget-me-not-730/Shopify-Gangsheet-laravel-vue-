<template>
    <div
        v-show="isVisible && objectSelected"
        class="bg-builder border border-gray-300 rounded min-w-[200px] text-sm"
        :style="menuStyle"
        ref="contextMenu"
    >
        <div class="context-menu-items">
            <div class="context-menu-item" @click="handleCopy" @touchstart="handleCopy">
                <svg-icon type="mdi" size="20" :path="mdiContentCopy" class="mr-1"/>
                {{ $t('Copy') }}
            </div>
            <div class="context-menu-item" :class="{ 'disabled': !copiedObject }" @click="handlePaste" @touchstart="handlePaste">
                <svg-icon type="mdi" size="20" :path="mdiContentPaste" class="mr-1"/>
                {{ $t('Paste') }}
            </div>
            <div class="context-menu-item" @click="handleDelete" @touchstart="handleDelete">
                <svg-icon type="mdi" size="24" :path="mdiDeleteOutline" class="-ml-0.5 mr-0.5 items-center justify-start"/>
                {{ $t('Delete') }}
            </div>
            <div class="context-menu-item" @click="handleDuplicate" @touchstart="handleDuplicate">
                <svg-icon type="mdi" size="20" :path="mdiContentDuplicate" class="mr-1"/>
                {{ $t('Duplicate') }}
            </div>
            <div class="context-menu-item" @click="handleAddQuantity" @touchstart="handleAddQuantity">
                <svg-icon type="mdi" size="20" :path="mdiPlusCircleMultipleOutline" class="mr-1"/>
                {{ $t('Add Quantity') }}
            </div>
            <div class="context-menu-item" :class="{ 'disabled': !showRemoveBackgroundButton() }" @click="openEditImageModal" @touchstart="openEditImageModal">
                <svg-icon type="mdi" size="20" :path="mdiStickerRemoveOutline" class="mr-1"/>
                {{ $t('Remove Color') }}
            </div>
            <div class="context-menu-item" :class="{ 'disabled': !showCropImageButton() }" @click="openCropImageModal" @touchstart="openCropImageModal">
                <svg-icon type="mdi" size="20" :path="mdiCrop" class="mr-1"/>
                {{ $t('Crop') }}
            </div>
            <div class="context-menu-item" :class="{ 'disabled': checkLayerIsTop() }" @click="handleSendForward" @touchstart="handleSendForward">
                <svg-icon type="mdi" size="20" :path="mdiArrangeBringForward" class="mr-1"/>
                {{ $t('Bring to Front') }}
            </div>
            <div class="context-menu-item" :class="{ 'disabled': checkLayerIsBottom() }" @click="handleSendBackward" @touchstart="handleSendBackward">
                <svg-icon type="mdi" size="20" :path="mdiArrangeSendBackward" class="mr-1"/>
                {{ $t('Send to Back') }}
            </div>
        </div>
    </div>
</template>

<script>
import eventBus from '@/Builder/Utils/eventBus'
import AddQuantityModal from '../Modals/AddQuantityModal.vue';
import {MODAL_NAMES} from '../Utils/constants';
import builderMixin from '@/Builder/Mixins/builderMixin';
import SvgIcon from "@jamescoyle/vue-icon";
import {
    mdiContentCopy,
    mdiContentPaste,
    mdiCardRemove,
    mdiContentDuplicate,
    mdiColorHelper,
    mdiCrop,
    mdiImageRemove,
    mdiDelete,
    mdiDeleteOutline,
    mdiPlusCircleMultipleOutline,
    mdiStickerRemoveOutline,
    mdiArrangeBringForward,
    mdiArrangeSendBackward
} from '@mdi/js'

export default {
    name: 'ContextMenu',
    components: {SvgIcon, AddQuantityModal},
    mixins: [builderMixin],

    props: {
        objectSelected: {
            type: Boolean,
            requried: true,
            default: false
        }
    },

    data() {
        return {
            isVisible: false,
            position: {
                x: 0,
                y: 0
            },
            copiedObject: null,
            type: 'image',
            isGalleryImage: false,
            openAddQuantityModal: false,
            mdiContentCopy,
            mdiContentPaste,
            mdiCardRemove,
            mdiContentDuplicate,
            mdiColorHelper,
            mdiCrop,
            mdiImageRemove,
            mdiDelete,
            mdiPlusCircleMultipleOutline,
            mdiDeleteOutline,
            mdiStickerRemoveOutline,
            mdiArrangeBringForward,
            mdiArrangeSendBackward
        }
    },

    computed: {
        menuStyle() {
            return {
                position: 'fixed',
                left: `${this.position?.x + 5}px`,
                top: `${this.position?.y + 5}px`,
                zIndex: 1000
            }
        }
    },

    mounted() {
        eventBus.$on(eventBus.CONTEXT_MENU, this.handleContextMenuEvent)
    },

    beforeUnmount() {
        eventBus.$off(eventBus.CONTEXT_MENU, this.handleContextMenuEvent)
    },

    methods: {
        handleContextMenuEvent({position, visible, type, isGalleryImage}) {
            this.isVisible = visible
            this.position = position
            this.type = type
            this.isGalleryImage = isGalleryImage

            if (!visible) return;
            const windowDimensions = {
                width: window.innerWidth,
                height: window.innerHeight
            }

            this.$nextTick(() => {
                const element = this.$refs.contextMenu;
                if (!element) return;

                const menuDimensions = {
                    width: element.offsetWidth,
                    height: element.offsetHeight
                };
                this.adjustPosition(position, menuDimensions, windowDimensions)
            })
        },

        adjustPosition(position, menuDimensions, windowDimensions) {
            if (position.x + menuDimensions.width > windowDimensions.width) {
                position.x = windowDimensions.width - menuDimensions.width;
            }
            if (position.y + menuDimensions.height > windowDimensions.height) {
                position.y = windowDimensions.height - menuDimensions.height;
            }
        },

        handleCopy() {
            this.copiedObject = window._gangSheetCanvasEditor.copy()
            this.isVisible = false
        },

        handlePaste() {
            if (this.copiedObject) {
                window._gangSheetCanvasEditor.paste()
                this.copiedObject = null;
                this.isVisible = false
            }

        },

        handleDelete() {
            window._gangSheetCanvasEditor.deleteActiveObjects()
            this.isVisible = false
        },
        handleDuplicate() {
            window._gangSheetCanvasEditor.duplicate()
            this.isVisible = false
        },
        handleAddQuantity() {
            this.openAddQuantityModal = true
            eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.ADD_QUANTITY})
            this.isVisible = false
        },
        showRemoveBackgroundButton() {
            if (this.shop.settings?.disableBackgroundRemoveTool) {
                return false
            }
            return this.type === 'image' && !this.isGalleryImage
        },
        showCropImageButton() {
            return this.type === 'image' && !this.isGalleryImage
        },
        checkLayerIsTop() {
            if (window._gangSheetCanvasEditor) {
                return window._gangSheetCanvasEditor.checkLayerIsTop()
            }
            return false
        },
        checkLayerIsBottom() {
            if (window._gangSheetCanvasEditor) {
                return window._gangSheetCanvasEditor.checkLayerIsBottom()
            }
            return false
        },
        openEditImageModal() {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected && selected.type === 'image') {
                const originId = selected.id
                const originUrl = selected.getOriginalUrl()
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.EDIT_IMAGE,
                    data: {
                        url: originUrl,
                        id: originId,
                        parentId: selected.parentId
                    },
                    onChange: async (newImage) => {
                        const objects = _gangSheetCanvasEditor.getObjects('image')

                        for (const object of objects) {
                            if (originUrl === object.getOriginalUrl()) {

                                const newUrl = this.artBoardSettings.visualQuality ? newImage.url : newImage.thumb_url

                                await object.setSrc(newUrl, () => {
                                    object.set({
                                        id: newImage.id,
                                        parentId: originId,
                                        url: newImage.url,
                                        thumb_url: newImage.thumb_url,
                                        realWidth: newImage.width,
                                        realHeight: newImage.height
                                    })
                                    _gangSheetCanvasEditor.renderAll()
                                })
                            }
                        }

                        const index = this.images.findIndex(img => img.url === originUrl)
                        if (index > -1) {
                            this.images[index].parentId = originId
                            this.images[index].id = newImage.id
                            this.images[index].thumb_url = newImage.thumb_url
                            this.images[index].url = newImage.url
                            this.images[index].width = newImage.width
                            this.images[index].height = newImage.height
                            _gangSheetCanvasEditor.setImages(this.images)
                        }
                    }
                })
            }
        },
        openCropImageModal() {
            const selected = _gangSheetCanvasEditor.getActiveObject()
            if (selected && selected.type === 'image') {
                const originId = selected.id
                const originUrl = selected.getOriginalUrl()
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.CROP_IMAGE,
                    data: {
                        url: originUrl,
                        id: originId,
                        parentId: selected.parentId
                    },
                    onChange: async (newImage) => {
                        const objects = _gangSheetCanvasEditor.getObjects('image')

                        for (const object of objects) {
                            if (originUrl === object.getOriginalUrl()) {

                                const newUrl = this.artBoardSettings.visualQuality ? newImage.url : newImage.thumb_url

                                await object.setSrc(newUrl, () => {
                                    object.set({
                                        id: newImage.id,
                                        parentId: originId,
                                        thumb_url: newImage.thumb_url,
                                        url: newImage.url,
                                        realWidth: newImage.width,
                                        realHeight: newImage.height
                                    })
                                    _gangSheetCanvasEditor.renderAll()
                                })
                            }
                        }

                        const index = this.images.findIndex(img => img.url === originUrl)
                        if (index > -1) {
                            this.images[index].parentId = originId
                            this.images[index].id = newImage.id
                            this.images[index].thumb_url = newImage.thumb_url
                            this.images[index].url = newImage.url
                            this.images[index].width = newImage.width
                            this.images[index].height = newImage.height
                            _gangSheetCanvasEditor.setImages(this.images)
                        }
                    }
                })
            }
        },
        handleSendForward() {
            window._gangSheetCanvasEditor.sendForward()
            this.isVisible = false
        },
        handleSendBackward() {
            window._gangSheetCanvasEditor.sendBackward()
            this.isVisible = false
        }
    }
}
</script>

<style scoped>
.context-menu-item {
    @apply px-4 py-2 cursor-pointer flex items-center gap-2 hover:bg-gray-300 hover:bg-opacity-50;
}

.context-menu-item.disabled {
    @apply opacity-50 cursor-not-allowed;
    pointer-events: none;
}

.context-menu-item i {
    @apply w-4;
}

.shortcut {
    @apply ml-auto text-gray-500 text-sm;
}
</style>
