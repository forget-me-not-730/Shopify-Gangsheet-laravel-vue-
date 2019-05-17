<template>
    <div class="h-[60px] shrink-0 w-full z-30 border-b">
        <div class="flex h-full">
            <div class="max-xs:hidden w-[380px] border-r flex items-center truncate px-2">
                <img v-if="shop.logo_url" class="h-10 object-contain" :src="shop.logo_url" alt="App Logo"/>
                <h2 v-else class="font-bold text-3xl max-sm:hidden ml-2">
                    {{ shop.shop_name || 'Gang Sheet Builder' }}
                </h2>
            </div>
            <div class="flex-1 flex items-center justify-between px-2">
                <div class="flex items-center space-x-2">

                    <button v-if="gangSheetsPreview" :disabled="savingDesign" class="btn-builder-outline"
                            @click="gangSheetsPreview = false">
                        <svg-icon type="mdi" :path="mdiArrowLeftThin" size="20" class="mr-1"/>
                        {{ $t('Back') }}
                    </button>

                    <template v-if="editMode">
                        <button :disabled="savingDesign" class="btn-builder" @click="handleSaveDesign">
                            <spinner v-if="savingDesign" class="mr-2"/>
                            Save Design
                        </button>

                        <button v-if="$page.props.auth.user?.type === 'admin'" class="btn-builder" @click="handleOverride">
                            Direct Save
                        </button>
                    </template>

                    <template v-else>
                        <template v-if="gangSheetsPreview">
                            <button :disabled="savingDesign" class="btn-builder" @click="handleConfirmAndAddToCart">
                                <spinner v-if="savingDesign" class="mr-2"/>
                                {{ $t('Confirm & Add to Cart') }}
                            </button>
                        </template>

                        <template v-else-if="workingDesigns.length > 1">
                            <button :disabled="savingDesign" class="btn-builder" @click="handleSaveAll">
                                <spinner v-if="savingDesign" class="mr-2"/>
                                {{ $t('Save All & Add to Cart') }}
                            </button>
                        </template>

                        <template v-else-if="currentDesign">
                            <div class="flex items-center justify-between mr-4 max-sm:mr-2">
                                <label class="mr-2">{{ $t('Quantity') }}: </label>
                                <input type="number" v-model="currentDesign.quantity"
                                       class="w-24 max-sm:w-16 flex items-center rounded-md border-0 pr-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>

                            <button :disabled="savingDesign" class="btn-builder"
                                    @click="handleSaveAndAddToCart">
                                <spinner v-if="savingDesign" class="mr-2"/>
                                <span> {{ $t('Save & Add to Cart') }} </span>
                            </button>
                        </template>
                    </template>

                    <button v-if="isAdminEdit && editRequest === 'pending'" class="btn-builder  ml-2" @click="openApproveEditRequestModal = true">
                        Approve Request
                    </button>

                    <button :disabled="savingDesign" class="btn-danger text-sm py-2 px-4 ml-2" @click="handleClose">
                        Close
                    </button>
                </div>


                <div class="h-full max-sm:hidden flex items-center">
                    <div v-if="editMode" class="mr-5 border-r h-full items-center flex px-4">
                        <span>{{ currentDesign.name }}</span>
                    </div>
                    <div v-else-if="product.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET" class="mr-5 border-r h-full items-center flex px-4 text-sm">
                        <span class="pr-2">Price:</span> <b class="text-base">${{ totalPrice.toFixed(2) }}</b>
                        <sup v-if="pricingType === 'tiered' && tieredPricing.discount > 0" class="pl-1 text-red-500">{{ tieredPricing.discount }}% off</sup>
                    </div>

                    <profile-menu/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {defineAsyncComponent, defineComponent} from 'vue'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import Spinner from '@/Components/Spinner.vue'
import eventBus from '@/Builder/Utils/eventBus'
import confirmationMixin from '@/Mixins/ConfirmationMixin'
import {MODAL_NAMES, DESIGN_TYPES, ART_BOARD_TYPES} from '@/Builder/Utils/constants'
import designSubmitMixin from '@/Builder/Mixins/designSubmitMixin'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiArrowLeftThin} from '@mdi/js'

const ProfileMenu = defineAsyncComponent(() => import('@/Builder/Components/ProfileMenu.vue'))

export default defineComponent({
    name: 'BuilderHeader',
    components: {SvgIcon, Spinner, ProfileMenu},
    mixins: [gangSheetMixin, confirmationMixin, designSubmitMixin],
    data() {
        return {
            mdiArrowLeftThin
        }
    },
    watch: {
        'currentDesign.quantity': {
            handler() {
                if (_gangSheetCanvasEditor) {
                    _gangSheetCanvasEditor.quantity = this.currentDesign?.quantity ?? 1
                }
            }
        }
    },
    mounted() {
        window.addEventListener('beforeunload', this.$gsb.updateCanvasData)

        eventBus.$on(eventBus.DESIGNS_ADD_TO_CART, async (data) => {

            let designs = await Promise.all(data.designs.map(async (design) => {
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

                        if (!postData.variant_title) {
                            postData.variant_title = this.$gsb.getTieredVariant().title || this.$gsb.getTieredVariant().label
                        }

                        if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                            postData.custom_price = (this.perSquarePrice * this.productSettings.printerWidth * Math.max(postData.actualHeightLabel.match(/^[\d.]+/), this.productSettings.startHeight) * postData.quantity).toFixed(2)
                        }

                        return {
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
                            design_id: res.data.design_id,
                            product_type: type,
                            ...postData
                        }
                    }

                    return null
                } catch (e) {
                    console.error(e)
                    return null
                }
            }))

            if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                designs = designs.filter(design => design != null)
            }

            window.parent.postMessage({
                action: 'gs_add_to_cart',
                submit_type: 'bulk',
                create_new_sheet: data.createNew,
                designs: designs
            }, '*')

            if (typeof data.callback === 'function') {
                data.callback()
            }
        })
    },
    computed: {
        ART_BOARD_TYPES() {
            return ART_BOARD_TYPES
        }
    },
    methods: {
        async handleSaveAndAddToCart() {

            const canvas = _gangSheetCanvasEditor

            if (canvas.getObjects().some(obj => !obj.isPattern && obj.type.includes('text') && obj.text?.toLowerCase() === 'gang sheet')) {
                return window.Toast.error({
                    message: 'Seems like there is placeholder text on the design. Please remove it.'
                })
            }

            if (!canvas.isEmpty()) {
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

                        if (!postData.variant_title) {
                            postData.variant_title = this.$gsb.getTieredVariant().title || this.$gsb.getTieredVariant().label
                        }

                        if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                            postData.custom_price = (this.perSquarePrice * this.productSettings.printerWidth * Math.max(postData.actualHeightLabel.match(/^[\d.]+/), this.productSettings.startHeight) * postData.quantity).toFixed(2)
                        }

                        window.parent.postMessage({
                            action: 'gs_add_to_cart',
                            create_new_sheet: createNewSheet,
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
                            design_id: res.data.design_id,
                            product_type: type,
                            ...postData
                        }, '*')
                    }
                }

                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.AGREE,
                    onChange: async (createNew) => {
                        if (createNew) {
                            await saveDesignExit(true)
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
                window.Toast.error({
                    message: 'You must add designs.'
                })
            }
        },
        async handleSaveDesign() {

            if (!this.currentDesign) return

            const canvas = _gangSheetCanvasEditor

            const saveDesign = async () => {

                const json = canvas.exportFinalJson()
                const thumbnail = await canvas.exportThumbnail()

                const res = await this.saveDesign(json, thumbnail)

                if (res) {
                    window.Toast.success({
                        message: 'Your workspace was saved successfully.'
                    })
                    const postData = this.getPostMessageData(json)
                    window.parent.postMessage({
                        action: 'gs_close_builder',
                        preview_url: res.data.preview_url,
                        download_url: res.data.download_url,
                        edit_url: res.data.edit_url,
                        design_id: res.data.design_id,
                        ...postData
                    }, '*')
                }

                this.savingDesign = false
                NProgress.done()
            }

            if (this.editRequest === 'approved' && !this.token) {
                this.confirmation = {
                    title: 'Confirmation',
                    description: `
                        Please make sure you have completed the design.
                        You will not be able to update the design once you submit it.
                    `,
                    onConfirm: () => {
                        saveDesign()
                    }
                }
            } else if (_gangSheetCanvasEditor.hasQualityWarning()) {
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.DESIGN_QUALITY_CONFIRM,
                    onChange: async () => {
                        await saveDesign()
                    }
                })
            } else {
                await saveDesign()
            }
        },
        handleClose() {
            if (this.editMode) {
                this.clearStorageJson()
            } else {
                this.$gsb.updateCanvasData()
            }
            if (window.top === window.self) {
                window.close()
            } else {
                window.parent.postMessage({
                    action: 'gs_close_builder'
                }, '*')
            }
        },
        async handleOverride() {
            const canvas = _gangSheetCanvasEditor
            const json = canvas.exportJson()
            const gangSheet = await canvas.exportGangSheet()
            window.axios.post(route('builder.save-gang-sheet'), {
                design_id: json.designId,
                gang_sheet: gangSheet
            }).then(res => {
                console.log(res)
                window.Toast.error({
                    message: 'Success!'
                })
            })
        },
        handleConfirmAndAddToCart() {
            eventBus.$emit(eventBus.DESIGNS_CONFIRM_AND_ADD_TO_CART)
        },
        handleSaveAll() {
            if (_gangSheetCanvasEditor && _gangSheetCanvasEditor.getObjects().length === 0) {
                return window.Toast.error({
                    message: 'You must add designs.'
                })
            }
            this.$gsb.updateCanvasData()
            this.$nextTick(() => {
                this.gangSheetsPreview = true
            })
        }
    }
})
</script>

<style scoped>

</style>
