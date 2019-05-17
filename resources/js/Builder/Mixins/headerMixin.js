import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import confirmationMixin from "@/Mixins/ConfirmationMixin";
import designSubmitMixin from "@/Builder/Mixins/designSubmitMixin";
import eventBus from "@/Builder/Utils/eventBus";
import {MODAL_NAMES, ART_BOARD_TYPES, DESIGN_TYPES} from "@/Builder/Utils/constants";

export default {
    mixins: [gangSheetMixin, confirmationMixin, designSubmitMixin],
    watch: {
        'currentDesign.quantity': {
            handler() {
                if (_gangSheetCanvasEditor) {
                    _gangSheetCanvasEditor.quantity = this.currentDesign?.quantity ?? 1
                }
            }
        }
    },
    computed: {
        showCloseButton() {
            return !this.isNinja
        },
        isAdmin() {
            return Boolean(this.$page.props.admin)
        }
    },
    mounted() {
        window.addEventListener('beforeunload', this.updateCanvasData)

        eventBus.$on(eventBus.DESIGNS_ADD_TO_CART, this.handleAddToCartDesigns)
    },
    methods: {
        async handleAddToCartDesigns(data) {
            const designs = await Promise.all(data.designs.map(async (design) => {
                try {

                    let type = DESIGN_TYPES.GANG_SHEET

                    if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {  
                        if (!design.json.actualHeight || !design.json.actualHeightLabel) {
                            design.json.actualHeight = design.json.actualHeight || design.json.meta.variant.height
                            design.json.actualHeightLabel = design.json.actualHeight + ' ' + design.json.meta.variant.unit
                        }
                        type = DESIGN_TYPES.ROLLING_GANG_SHEET
                    }

                    const res = await this.saveDesign(design.json, design.thumbnail, type)

                    if (res) {
                        const postData = this.getPostMessageData(design.json)

                        return {
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
                            admin_edit_url: res.data.admin_edit_url,
                            design_id: res.data.design_id,
                            ...postData
                        }
                    }

                    return null
                } catch (e) {
                    console.error(e)
                    return null
                }
            }))

            window.parent.postMessage({
                action: 'gs_add_to_cart',
                submit_type: 'bulk',
                create_new_sheet: data.createNew,
                designs: designs
            }, '*')

            if (typeof data.callback === 'function') {
                data.callback()
            }
        },
        handleBackClick() {
            this.gangSheetsPreview = false
            this.autoNestMode = false
        },
        async handleSaveAndAddToCart() {
            const canvas = window._gangSheetCanvasEditor

            if (canvas.hasDefaultTexts()) {
                this.confirmation = {
                    title: 'Confirm',
                    description: 'Seems like there is placeholder text on the design.\n Are you sure you want to save it?',
                    onConfirm: () => {
                        this.handleSave()
                    }
                }
            } else {
                this.handleSave()
            }
        },
        async handleSave() {
            const canvas = window._gangSheetCanvasEditor
            if (canvas.hasOutViewPortObjects()) {
                eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.REMOVE_IMAGE_CONFIRM, data: {canvas: canvas}})
            } else if (!canvas.isEmpty()) {
                const saveDesignExit = async (createNewSheet) => {
                    NProgress.start()

                    let type = DESIGN_TYPES.GANG_SHEET

                    if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                        type = DESIGN_TYPES.ROLLING_GANG_SHEET
                    }

                    const json = canvas.exportFinalJson()
                    const thumbnail = await canvas.exportThumbnail()

                    const res = await this.saveDesign(json, thumbnail, type)

                    if (window.top === window.self || createNewSheet) {
                        this.savingDesign = false
                        NProgress.done()
                    }

                    if (res) {
                        canvas.designId = res.data.design_id

                        const postData = this.getPostMessageData(json)
                        window.parent.postMessage({
                            action: 'gs_add_to_cart',
                            create_new_sheet: createNewSheet,
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
                            admin_edit_url: res.data.admin_edit_url,
                            design_id: res.data.design_id,
                            ...postData
                        }, '*')
                    } else {
                        this.savingDesign = false
                        NProgress.done()
                    }
                }
                if (this.shop.agree_check_flag) {
                    eventBus.$emit(eventBus.OPEN_MODAL, {
                        name: MODAL_NAMES.AGREE,
                        onChange: async (createNew) => {
                            if (createNew) {
                                await saveDesignExit(true)
                                NProgress.done()
                                this.hasDesignChange = false
                                this.workingDesigns = []
                                this.workingDesignIndex = 0
                                eventBus.$emit(eventBus.OPEN_NEW_DESIGN)
                            } else {
                                await saveDesignExit(false)
                            }
                        }
                    })
                } else {
                    await saveDesignExit(false)
                }
            } else {
                return window.Toast.error({
                    message: 'You must add designs.'
                })
            }
        },
        async handleSaveDesign() {

            if (!this.currentDesign) return

            const canvas = window._gangSheetCanvasEditor

            if (canvas.hasOutViewPortObjects()) {
                eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.REMOVE_IMAGE_CONFIRM, data: {canvas: canvas}})
            } else {
                const saveDesign = async () => {
                    NProgress.start()

                    const json = canvas.exportFinalJson()
                    const thumbnail = await canvas.exportThumbnail()

                    const res = await this.saveDesign(json, thumbnail)

                    if (res) {
                        this.$gsb.success('Your workspace was saved successfully.')
                        const postData = this.getPostMessageData(json)
                        window.parent.postMessage({
                            action: 'gs_close_builder',
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
                            admin_edit_url: res.data.admin_edit_url,
                            design_id: res.data.design_id,
                            ...postData
                        }, '*')
                    }

                    this.savingDesign = false
                    NProgress.done()
                }

                if (canvas.hasQualityWarning()) {
                    eventBus.$emit(eventBus.OPEN_MODAL, {
                        name: MODAL_NAMES.DESIGN_QUALITY_CONFIRM,
                        onChange: async () => {
                            await saveDesign(canvas)
                        }
                    })
                } else {
                    await saveDesign(canvas)
                }
            }
        },
        handleClose() {
            const closeBuilder = () => {
                if (window.top === window.self) {
                    window.close()
                } else {
                    window.parent.postMessage({
                        action: 'gs_close_builder'
                    }, '*')
                }
            }

            if (this.editMode) {
                this.clearStorageJson()
                closeBuilder()
            } else {
                if (this.hasDesignChange) {
                    eventBus.$emit(eventBus.OPEN_MODAL, {
                        name: MODAL_NAMES.CONFIRM_DESIGN_SAVE,
                        onChange: async (save) => {
                            if (save) {
                                eventBus.$emit(eventBus.SAVE_DRAFT_DESIGN, {
                                    callback: () => {
                                        closeBuilder()
                                    }
                                })
                            } else {
                                this.$gsb.updateCanvasData()
                                closeBuilder()
                            }
                        }
                    })
                } else {
                    closeBuilder()
                }
            }
        },
        async handleDirectSave() {
            if (confirm('Please make sure the visual aid is transparent.')) {
                this.savingDesign = true
                const canvas = window._gangSheetCanvasEditor
                const json = canvas.exportFinalJson()
                const gangSheet = await canvas.exportGangSheet()

                if (gangSheet.length < 100) {
                    window.Toast.error({
                        message: 'Canvas size is too large to export image.!'
                    })
                    this.savingDesign = false
                    return
                }

                window.axios.post(route('builder.save-direct'), {
                    design_id: json.designId,
                    gang_sheet: gangSheet
                }).then(res => {
                    window.Toast.success({
                        message: 'Successfully saved!'
                    })
                }).finally(() => {
                    this.savingDesign = false
                })
            }
        },
        handleSaveAs() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.SAVE_DESIGN,
                onChange: () => {
                    eventBus.$emit(eventBus.SAVE_DRAFT_DESIGN)
                }
            })
        },
        handleSaveAll() {
            if (window._gangSheetCanvasEditor?.isEmpty() === 0) {
                return window.Toast.error({
                    message: 'You must add designs.'
                })
            }
            this.$gsb.updateCanvasData()
            this.$nextTick(() => {
                this.gangSheetsPreview = true
            })
        },
        handleConfirmAndAddToCart() {
            eventBus.$emit(eventBus.DESIGNS_CONFIRM_AND_ADD_TO_CART)
        },
        handleOpenLiveChat() {
            if (window.tidioChatApi) {
                window.tidioChatApi.show();
                window.tidioChatApi.open();
            }
        }
    }
}
