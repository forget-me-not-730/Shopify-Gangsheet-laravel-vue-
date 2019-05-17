<script>
import {defineComponent} from 'vue'
import headerMixin from '@/Builder/Mixins/headerMixin'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import Spinner from '@/Components/Spinner.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiArrowLeftThin} from '@mdi/js'
import eventBus from '@/Builder/Utils/eventBus'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import {getSessionId} from '@/Builder/Utils/helpers'
import {cloneDeep} from 'lodash'
import ProfileMenu from "@/Builder/Components/ProfileMenu.vue";

export default defineComponent({
    name: 'GbsHeader',
    components: {ProfileMenu, SvgIcon, Spinner},
    mixins: [gangSheetMixin, headerMixin],
    data() {
        return {
            mdiArrowLeftThin
        }
    },
    mounted() {
        eventBus.$on(eventBus.DESIGNS_ADD_TO_CART, async (data) => {

            const designs = await Promise.all(data.designs.map(async (design) => {
                try {
                    const res = await this.submitDesign(design.json, design.thumbnail)

                    if (res) {
                        return {
                            ...res.design,
                            size: design.json.meta.variant
                        }
                    }

                    return null
                } catch (e) {
                    console.error(e)
                    return null
                }
            }))

            window.parent.postMessage({
                action: 'gsb-event',
                event: 'design:created',
                data: this.getPostMessage({
                    designs: designs,
                    edit_design: false,
                    create_new: data.createNew
                })
            }, '*')

            this.savingDesign = false
            NProgress.done()

            if (typeof data.callback === 'function') {
                data.callback()
            }
        })

    },
    methods: {
        getPostMessage(postData) {
            return cloneDeep({
                ...postData,
                details: {
                    shop: this.shop,
                    product: this.product,
                    customer: this.customer,
                    sizes: this.variants,
                    size: this.variant
                }
            })
        },
        handleClose() {
            if (this.isNinja) {
                // update parent window's route
                window.parent.location.href = 'https://www.ssactivewear.com/p/ninja_transfers/gangsheetuploadstd'
            } else {
                window.parent.postMessage({
                    action: 'gsb-close'
                }, '*')
            }
        },
        submitDesign(json, thumbnail) {
            return new Promise(async (resolve) => {
                this.savingDesign = true
                if (json && json.objects.length > 0) {

                    if (this.shopFonts.length > 0) {
                        json.meta.fonts = this.shopFonts
                    }

                    window.axios.post(route('gs.builder.save'), {
                        json: json,
                        design_id: json.designId,
                        quantity: json.quantity,
                        shop_id: this.shop.id,
                        product_id: this.product?.id,
                        variant_id: json.meta.variant.id,
                        customer_id: this.customer?.id || null,
                        thumbnail: thumbnail,
                        session_id: getSessionId(),
                        token: this.token
                    }).then(res => {
                        if (res.data.success) {
                            resolve(res.data)
                        } else {
                            window.Toast.error({message: res.data.error})
                            resolve(null)
                        }
                    }).catch((e) => {
                        console.log('Error: submitDesign', e)
                        this.savingDesign = false
                        window.Toast.error({
                            message: 'Something went wrong.'
                        })
                        resolve(false)
                    })
                } else {
                    this.savingDesign = false
                    window.Toast.error({
                        message: 'You must add designs.'
                    })
                    resolve(false)
                }
            })
        },
        async handleDesignSave() {

            this.$gsb.updateCanvasData()
            const canvas = _gangSheetCanvasEditor

            if (canvas.getObjects().length > 0) {

                const _submitDesign = async (createNewSheet) => {
                    const json = canvas.exportFinalJson()
                    const thumbnail = await canvas.exportThumbnail()

                    const res = await this.submitDesign(json, thumbnail)

                    if (res) {
                        canvas.designId = res.design.id

                        window.parent.postMessage({
                            action: 'gsb-event',
                            event: this.editMode ? 'design:updated' : 'design:created',
                            data: this.getPostMessage({
                                designs: [
                                    {
                                        ...res.design,
                                        size: json.meta.variant
                                    }
                                ],
                                edit_design: false,
                                create_new: createNewSheet
                            })
                        }, '*')
                    }

                    this.savingDesign = false
                    NProgress.done()
                }

                if (this.editMode) {
                    if (canvas.hasQualityWarning()) {
                        eventBus.$emit(eventBus.OPEN_MODAL, {
                            name: MODAL_NAMES.DESIGN_QUALITY_CONFIRM,
                            onChange: async () => {
                                await _submitDesign(false)
                            }
                        })
                    } else {
                        await _submitDesign(false)
                    }
                } else {
                    eventBus.$emit(eventBus.OPEN_MODAL, {
                        name: MODAL_NAMES.AGREE,
                        onChange: async (createNew) => {
                            if (createNew) {
                                await _submitDesign(true)
                                this.hasDesignChange = false
                                this.savingDesign = false
                                eventBus.$emit(eventBus.OPEN_NEW_DESIGN)
                            } else {
                                await _submitDesign(false)
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
        handleConfirmAndSaveAll() {
            eventBus.$emit(eventBus.DESIGNS_CONFIRM_AND_ADD_TO_CART)
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
    }
})
</script>

<template>
    <div class="h-[60px] shrink-0 w-full z-30 border-b gs-bg-top-bar">
        <div class="flex h-full">
            <div class="flex w-[380px] border-r items-center truncate px-2 max-sm:hidden">
                <div v-if="shop.logo_url" class="flex w-full items-center justify-between h-full">
                    <img class="h-10 object-contain" :src="shop.logo_url" alt="App Logo"/>
                    <div v-if="isNinja" @click="handleClose"
                         class="h-full flex items-end pb-1 pl-2 text-sm cursor-pointer hover:underline max-sm:hidden">
                        <svg-icon type="mdi" :path="mdiArrowLeftThin" size="22"/>
                        Back to S&S Activewear
                    </div>
                </div>
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
                    <template v-if="gangSheetsPreview">
                        <button :disabled="savingDesign" class="btn-builder" @click="handleConfirmAndSaveAll">
                            <spinner v-if="savingDesign" class="mr-2"/>
                            {{ $t('Confirm & Save All') }}
                        </button>
                    </template>

                    <template v-else-if="workingDesigns.length > 1">
                        <button :disabled="savingDesign" class="btn-builder" @click="handleSaveAll">
                            <spinner v-if="savingDesign" class="mr-2"/>
                            {{ $t('Save All') }}
                        </button>
                    </template>

                    <template v-else-if="currentDesign">
                        <div v-if="shopSettings.enableQuantity" class="flex items-center justify-between mr-4 max-sm:mr-2">
                            <label class="mr-2">{{ $t('Quantity') }}: </label>
                            <input type="number" v-model="currentDesign.quantity" :disabled="savingDesign" min="1"
                                   class="w-24 max-sm:w-16 flex items-center rounded-md border-0 pr-2 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <button :disabled="savingDesign" class="btn-builder"
                                @click="handleDesignSave">
                            <spinner v-if="savingDesign" class="mr-2"/>
                            <span> Save Design </span>
                        </button>
                        <button v-if="!this.isNinja" class="btn-danger text-sm py-2 px-4 ml-2" @click="handleClose" :disabled="savingDesign">
                            Close
                        </button>
                    </template>
                </div>

                <div class="h-full max-sm:hidden flex items-center">
                    <div v-if="editMode" class="mr-5 border-r h-full items-center flex px-4">
                        <span>{{ currentDesign?.name }}</span>
                    </div>

                    <profile-menu/>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
