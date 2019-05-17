<template>
    <save-design-modal :open="isOpen(modalNames.SAVE_DESIGN)" @confirm="onChange" @close="handleClose"/>
    <agree-modal :open="isOpen(modalNames.AGREE)" :data="data" @confirm="onChange" @close="handleClose"/>
    <image-modal :open="isOpen(modalNames.SELECT_IMAGE)" @confirm="onChange" @close="handleClose" />
    <image-edit-modal :open="isOpen(modalNames.IMAGE_EDIT)" @confirm="onChange" @close="handleClose" />
    <text-setting-modal :open="isOpen(modalNames.TEXT_EDIT)" @confirm="onChange" @close="handleClose" />
    <background-setting-modal :open="isOpen(modalNames.BACKGROUND_SETTINGS)" @confirm="onChange" @close="handleClose" />
</template>

<script>
import {defineAsyncComponent} from 'vue'
import eventBus from '@/Builder/Utils/eventBus'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import builderMixin from '@/Builder/Mixins/builderMixin'
const SaveDesignModal = defineAsyncComponent(() => import('@/Builder/Sticker/Modals/SaveDesignModal.vue'))
const AgreeModal = defineAsyncComponent(() => import("@/Builder/Sticker/Modals/AgreeModal.vue"))
const ImageModal = defineAsyncComponent(() => import("@/Builder/Sticker/Modals/ImageModal.vue"))
const ImageEditModal = defineAsyncComponent(() => import("@/Builder/Sticker/Modals/ImageEditModal.vue"))
const TextSettingModal = defineAsyncComponent(() => import("@/Builder/Sticker/Modals/TextSettingModal.vue"))
const BackgroundSettingModal = defineAsyncComponent(() => import("@/Builder/Sticker/Modals/BackgroundSettingModal.vue"))

export default {
    name: 'StickerBuilderModals',
    mixins: [builderMixin],
    components: {ImageEditModal, ImageModal, SaveDesignModal, AgreeModal, TextSettingModal, BackgroundSettingModal},
    data() {
        return {
            openModals: [],
            onChange: null,
            data: null,
            modalNames: MODAL_NAMES
        }
    },
    created() {
        eventBus.$on(eventBus.OPEN_MODAL, this.handleOpenModal.bind(this))
        eventBus.$on(eventBus.CLOSE_MODAL, this.handleCloseModal.bind(this))
        eventBus.$on(eventBus.CLOSE_MODAL_ALL, this.handleCloseModalAll.bind(this))
    },
    unmounted() {
        eventBus.$off(eventBus.OPEN_MODAL, this.handleOpenModal)
        eventBus.$off(eventBus.CLOSE_MODAL, this.handleCloseModal)
        eventBus.$off(eventBus.CLOSE_MODAL_ALL, this.handleCloseModalAll)
    },
    methods: {
        handleOpenModal(data) {
            if (!this.openModals.includes(data.name)) {
                this.data = data.data
                this.openModals.push(data.name)
                this.onChange = data.onChange
            }
        },
        handleCloseModal() {
            this.handleClose()
        },
        handleCloseModalAll() {
            this.openModals = []
        },
        isOpen(modalName) {
            return this.openModals.includes(modalName)
        },
        handleClose() {
            this.openModals.pop()
        }
    }
}
</script>
