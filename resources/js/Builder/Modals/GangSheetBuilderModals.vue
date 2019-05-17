<template>
    <image-upload-modal :open="isOpen(modalNames.IMAGE_UPLOAD)" :data="data" @close="handleClose"/>
    <selector-modal :open="isOpen(modalNames.SELECTOR)" @close="handleClose"/>
    <agree-modal :open="isOpen(modalNames.AGREE)" :data="data" @confirm="onChange" @close="handleClose"/>
    <agree-all-modal :open="isOpen(modalNames.AGREE_ALL)" :data="data" @confirm="onChange" @close="handleClose"/>
    <design-quality-confirm-modal :open="isOpen(modalNames.DESIGN_QUALITY_CONFIRM)" @confirm="onChange" @close="handleClose"/>
    <edit-image-modal :open="isOpen(modalNames.EDIT_IMAGE)" :data="data" @confirm="onChange" @close="handleClose"/>
    <crop-image-modal :open="isOpen(modalNames.CROP_IMAGE)" :data="data" @confirm="onChange" @close="handleClose"/>
    <save-design-modal :open="isOpen(modalNames.SAVE_DESIGN)" @confirm="onChange" @close="handleClose"/>
    <confirm-design-save-modal :open="isOpen(modalNames.CONFIRM_DESIGN_SAVE)" @confirm="onChange" @close="handleClose"/>
    <customer-designs-modal :open="isOpen(modalNames.CUSTOMER_DESIGNS)" @close="handleClose"/>
    <profile-modal :open="isOpen(modalNames.CUSTOMER_PROFILE)" @close="handleClose"/>
    <start-modal :open="isOpen(modalNames.START)" @close="handleStartModalClose"/>
    <remove-unnecessary-image-modal :open="isOpen(modalNames.REMOVE_IMAGE_CONFIRM)" @close="handleClose"/>
    <name-and-numbers-modal :open="isOpen(modalNames.NAME_AND_NUMBERS)" @close="handleClose"/>
    <settings-modal :open="isOpen(modalNames.GANG_SHEET_SETTINGS)" @close="handleClose"/>
    <image-upload-warning-modal :open="isOpen(modalNames.IMAGE_UPLOAD_WARNING)" :data="data" @close="handleClose"/>
    <add-quantity-modal :open="isOpen(modalNames.ADD_QUANTITY)" :data="data" @close="handleClose"/>
    <canva-export-design-modal :open="isOpen(modalNames.CANVA_EXPORT_DESIGN)" :data="data" @close="handleClose"/>
</template>

<script>
import {defineAsyncComponent} from 'vue'
import {getSearchParams} from '@/Builder/Utils/helpers'
import eventBus from '@/Builder/Utils/eventBus'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import builderMixin from '@/Builder/Mixins/builderMixin'

import StartModal from '@/Builder/Modals/StartModal.vue'
import RemoveUnnecessaryImageModal from "@/Builder/Modals/RemoveUnnecessaryImageModal.vue";

const ImageUploadModal = defineAsyncComponent(() => import('@/Builder/Modals/ImageUploadModal.vue'))
const SelectorModal = defineAsyncComponent(() => import('@/Builder/Modals/SelectorModal.vue'))
const AgreeModal = defineAsyncComponent(() => import('@/Builder/Modals/AgreeModal.vue'))
const AgreeAllModal = defineAsyncComponent(() => import('@/Builder/Modals/AgreeAllModal.vue'))
const EditImageModal = defineAsyncComponent(() => import('@/Builder/Modals/EditImageModal.vue'))
const CropImageModal = defineAsyncComponent(() => import('@/Builder/Modals/CropImageModal.vue'))
const SaveDesignModal = defineAsyncComponent(() => import('@/Builder/Modals/SaveDesignModal.vue'))
const ConfirmDesignSaveModal = defineAsyncComponent(() => import('@/Builder/Modals/ConfirmDesignSaveModal.vue'))
const CustomerDesignsModal = defineAsyncComponent(() => import('@/Builder/Modals/CustomerDesignsModal.vue'))
const ProfileModal = defineAsyncComponent(() => import('@/Builder/Modals/ProfileModal.vue'))
const DesignQualityConfirmModal = defineAsyncComponent(() => import('@/Builder/Modals/DesignQualityConfirmModal.vue'))
const NameAndNumbersModal = defineAsyncComponent(() => import('@/Builder/Modals/NameAndNumbersModal.vue'))
const SettingsModal = defineAsyncComponent(() => import('@/Builder/Modals/SettingsModal.vue'))
const ImageUploadWarningModal = defineAsyncComponent(() => import('@/Builder/Modals/ImageUploadWarningModal.vue'))
const AddQuantityModal = defineAsyncComponent(() => import('@/Builder/Modals/AddQuantityModal.vue'))
const CanvaExportDesignModal = defineAsyncComponent(() => import('@/Builder/Modals/CanvaExportDesignModal.vue'))

export default {
    name: 'GangSheetBuilderModals',
    mixins: [builderMixin],
    components: {
        SettingsModal,
        NameAndNumbersModal,
        RemoveUnnecessaryImageModal,
        StartModal,
        DesignQualityConfirmModal,
        ProfileModal,
        CustomerDesignsModal,
        ConfirmDesignSaveModal,
        SaveDesignModal,
        CropImageModal,
        EditImageModal,
        AgreeModal,
        AgreeAllModal,
        SelectorModal,
        ImageUploadModal,
        ImageUploadWarningModal,
        AddQuantityModal,
        CanvaExportDesignModal
    },
    data() {
        return {
            openModals: [],
            onChange: null,
            data: null,
            modalNames: MODAL_NAMES
        }
    },
    mounted() {
        const params = getSearchParams()

        if (!this.editMode && !this.patternMode) {
            let autoBuildMode = this.builderSettings.allowedAutoBuild && this.builderSettings.openAutoBuildAsDefault

            if (autoBuildMode) {
                eventBus.$once(eventBus.CANVAS_INITIALIZED, () => {
                    this.autoNestMode = true
                })
            }

            let showStartModal = !autoBuildMode &&
                this.permissions.canReorder &&
                !params.direct_open &&
                this.builderSettings.showStartModal &&
                !this.builderSettings.nameAndNumber?.default

            if (showStartModal) {
                this.openModals.push(MODAL_NAMES.START)
            }

            if (params.open_designs) {
                this.openModals.push(MODAL_NAMES.CUSTOMER_DESIGNS)
            }
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
        },
        handleStartModalClose() {
            this.openModals = []
            if (this.permissions.tour) {
                this.$tours['gsbTour'].start()
            }
        }
    }
}
</script>
