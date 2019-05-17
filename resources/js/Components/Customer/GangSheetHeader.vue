<template>
    <div class="h-[60px] shrink-0 w-full z-30 border-b gs-bg-top-bar">
        <div class="flex h-full">
            <div class="flex border-r items-center truncate px-2 max-sm:hidden w-[380px]">
                <img v-if="shop.logo_url" class="h-10 object-contain" :src="shop.logo_url" alt="App Logo"/>
                <h2 v-else class="font-bold text-3xl max-sm:hidden ml-2">
                    {{ shop.company_name }}
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
                        <button v-if="admin || this.order" :disabled="savingDesign" class="btn-builder"
                                @click="handleSaveDesign">
                            <spinner v-if="savingDesign" class="mr-2"/>
                            <span>Save Design</span>
                        </button>

                        <template v-else>
                            <button :disabled="savingDesign" class="btn-builder"
                                    @click="handleSaveAndAddToCart">
                                <spinner v-if="savingDesign" class="mr-2"/>
                                <span>{{ $t('Save & Close') }}</span>
                            </button>
                            <button class="btn-danger py-2 px-4 ml-2" @click="handleClose">
                                Close
                            </button>
                        </template>

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

                        <template v-else-if="workingDesigns.length > 1 && !autoNestMode">
                            <button :disabled="savingDesign" class="btn-builder" @click="handleSaveAll">
                                <spinner v-if="savingDesign" class="mr-2"/>
                                {{ $t('Save All & Add to Cart') }}
                            </button>
                        </template>

                        <template v-else-if="currentDesign">
                            <div class="flex items-center justify-between mr-4 max-sm:mr-2">
                                <label class="mr-2">{{ $t('Quantity') }}: </label>
                                <input type="number" v-model="currentDesign.quantity" min="0" class="w-16 inp-builder">
                            </div>
                            <button :disabled="savingDesign" class="btn-builder"
                                    @click="handleSaveAndAddToCart">
                                <spinner v-if="savingDesign" class="mr-2"/>
                                <span> {{ $t('Save & Add to Cart') }} </span>
                            </button>
                            <button v-if="showCloseButton" class="btn-danger text-sm py-2 px-4 ml-2" @click="handleClose">
                                Close
                            </button>
                        </template>
                    </template>
                </div>

                <div class="flex items-center">
                    <div v-if="!editMode && shopSettings.enableLiveChat" @click="handleOpenLiveChat" class="gs-chat-button cursor-pointer w-max sm:mr-5">
                        <div class="flex">
                            <chat-icon class="w-6"/>
                            <div class="flex ml-1 flex-col relative h-full max-lg:hidden">
                                <b class="text-red-500 pb-2">Need Help ?</b>
                                <div class="text-xs flex items-center text-gray-500 absolute bottom-[-4px] left-0">
                                <span class="relative flex h-1.5 w-1.5 mr-1">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-500 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-teal-500"></span>
                                </span>
                                    Chat Now
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-full max-sm:hidden flex items-center">
                        <div v-if="editMode" class="mr-5 border-r h-full items-center flex px-4">
                            <span>{{ currentDesign?.name }}</span>
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
    </div>
</template>

<script>
import {defineAsyncComponent} from 'vue'
import Spinner from '@/Components/Spinner.vue'
import eventBus from '@/Builder/Utils/eventBus'
import {MODAL_NAMES, ART_BOARD_TYPES, DESIGN_TYPES} from '@/Builder/Utils/constants'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiArrowLeftThin} from '@mdi/js'
import designSubmitMixin from '@/Builder/Mixins/designSubmitMixin'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import ChatIcon from "@/Builder/Icons/ChatIcon.vue";

const ProfileMenu = defineAsyncComponent(() => import('@/Builder/Components/ProfileMenu.vue'))

export default {
    name: 'BuilderHeader',
    components: {ChatIcon, ProfileMenu, Spinner, SvgIcon},
    mixins: [gangSheetMixin, designSubmitMixin],
    data() {
        return {
            mdiArrowLeftThin
        }
    },
    computed: {
        showCloseButton() {
            return window.top !== window.self
        },
        ART_BOARD_TYPES() {
            return ART_BOARD_TYPES
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
        this.savingDesign = false

        eventBus.$on(eventBus.DESIGNS_ADD_TO_CART, async (data) => {

            const designs = await Promise.all(data.designs.map(async (design) => {
                try {
                    const res = await this.saveDesign(design.json, design.thumbnail)

                    if (res) {
                        const postData = this.getPostMessageData(design.json)

                        return {
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
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

            const successDesigns = designs.filter(d => Boolean(d))

            const designIds = successDesigns.map(d => d.design_id)

            await this.addToCart(designIds, !data.createNew)

            if (typeof data.callback === 'function') {
                data.callback()
            }
        })
    },
    methods: {
        async handleSaveDesign() {

            const canvas = _gangSheetCanvasEditor

            if (canvas.hasOutViewPortObjects()) {
                eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.REMOVE_IMAGE_CONFIRM, data: {canvas: canvas}})
            } else {
                NProgress.start()
                this.savingDesign = true

                const json = canvas.exportFinalJson()
                const thumbnail = await canvas.exportThumbnail()

                const result = await this.saveDesign(json, thumbnail)

                if (result) {
                    window.Toast.success({
                        message: 'Successfully Saved!'
                    })
                }

                NProgress.done()
                this.savingDesign = false
            }
        },
        async handleSaveAndAddToCart() {
            this.$gsb.updateCanvasData()
            const canvas = _gangSheetCanvasEditor

            if (canvas.getObjects().some(obj => !obj.isPattern && obj.type.includes('text') && obj.text?.toLowerCase() === 'gang sheet')) {
                return window.Toast.error({
                    message: 'Seems like there is placeholder text on the design. Please remove it.'
                })
            }

            if (canvas.hasOutViewPortObjects()) {
                eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.REMOVE_IMAGE_CONFIRM, data: {canvas: canvas}})
            } else if (!canvas.isEmpty()) {
                let type = DESIGN_TYPES.GANG_SHEET

                if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                    type = DESIGN_TYPES.ROLLING_GANG_SHEET
                }

                if (this.editMode) {
                    const saveDesignAndViewCartPage = async () => {
                        NProgress.start()
                        this.savingDesign = true

                        const json = canvas.exportFinalJson()
                        const thumbnail = await canvas.exportThumbnail()

                        const res = await this.saveDesign(json, thumbnail, type)

                        if (res) {
                            this.$inertia.get(route('builder.cart.index', {slug: this.product.slug}))
                        } else {
                            NProgress.done()
                            this.savingDesign = false
                        }
                    }

                    if (canvas.hasQualityWarning()) {
                        eventBus.$emit(eventBus.OPEN_MODAL, {
                            name: MODAL_NAMES.DESIGN_QUALITY_CONFIRM,
                            onChange: async () => {
                                await saveDesignAndViewCartPage()
                            }
                        })
                    } else {
                        await saveDesignAndViewCartPage()
                    }
                } else {
                    const saveDesignExit = async (createNewSheet) => {

                        const json = canvas.exportFinalJson()
                        const thumbnail = await canvas.exportThumbnail()

                        const res = await this.saveDesign(json, thumbnail, type)

                        if (createNewSheet) {
                            NProgress.done()
                        }

                        if (res) {
                            await this.addToCart(res.data.design_id, !createNewSheet)
                        } else {
                            NProgress.done()
                            this.savingDesign = false
                        }
                    }

                    eventBus.$emit(eventBus.OPEN_MODAL, {
                        name: MODAL_NAMES.AGREE,
                        onChange: async (createNew) => {
                            if (createNew) {
                                await saveDesignExit(true)
                                this.hasDesignChange = false
                                this.savingDesign = false
                                eventBus.$emit(eventBus.OPEN_NEW_DESIGN)
                            } else {
                                await saveDesignExit(false)
                            }
                        }
                    })
                }
            } else {
                window.Toast.error({
                    message: 'You must add designs.'
                })
            }
        },
        handleClose() {
            if (this.isStandalone) {
                this.$inertia.get(route('builder.cart.index', {slug: this.product.slug}))
            } else {
                if (window.top === window.self) {
                    window.close()
                } else {
                    window.parent.postMessage({
                        action: 'gs_close_builder'
                    }, '*')
                }
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
                window.Toast.success({
                    message: 'Success!'
                })
            })
        },
        handleSaveAll() {
            if (_gangSheetCanvasEditor && _gangSheetCanvasEditor.isEmpty()) {
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
</script>

<style scoped>

</style>
