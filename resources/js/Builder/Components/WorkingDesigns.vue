<script>
import {defineComponent} from 'vue'

import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import confirmationMixin from '@/Builder/Mixins/confirmationMixin'
import designSubmitMixin from '@/Builder/Mixins/designSubmitMixin'

import {cloneDeep} from 'lodash'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiChevronLeft, mdiInvoicePlusOutline, mdiInvoiceTextPlusOutline, mdiAnimation, mdiSquareEditOutline, mdiHome} from '@mdi/js'
import eventBus from '@/Builder/Utils/eventBus'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import Spinner from '../Components/Spinner.vue'

export default defineComponent({
    name: 'WorkingDesigns',
    components: {Spinner, SvgIcon},
    mixins: [gangSheetMixin, confirmationMixin, designSubmitMixin],
    data() {
        return {
            editNameIndex: null,

            mdiChevronLeft,
            mdiInvoicePlusOutline,
            mdiHome,
            mdiAnimation,
            mdiInvoiceTextPlusOutline,
            mdiSquareEditOutline
        }
    },
    methods: {
        handleDesignNameUpdate(e, designIndex) {
            const name = e.target.textContent.trim().replace('\n', ' ').substring(0, 100) || 'Untitled'
            this.workingDesigns[designIndex].name = name
            if (this.workingDesignIndex === designIndex) {
                window._gangSheetCanvasEditor.name = name
            }
            this.editNameIndex = null
            e.target.innerHTML = name
        },
        handleDesignClick(index) {
            if (this.workingDesignIndex !== index) {
                this.$gsb.updateCanvasData()
                this.workingDesignIndex = index
                this.$nextTick(() => {
                    this.variant = cloneDeep(this.workingDesigns[index].meta.variant)
                    this.images = this.currentDesign.meta.images ?? []
                })
            }
        },
        handleAddNewDesign() {
            this.$gsb.updateCanvasData()
            const designCount = this.workingDesigns.push(this.$gsb.getEmptyDesign())
            this.workingDesignIndex = designCount - 1
            this.workingDesigns = [...this.workingDesigns]
            eventBus.$emit(eventBus.REFRESH_BUILDER, this.currentDesign)
        },
        handleOpenFromPreviousDesigns() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.CUSTOMER_DESIGNS
            })
        },
        handleDesignRemove(index) {
            this.$gsb.updateCanvasData()

            const removeDesign = () => {
                this.workingDesigns.splice(index, 1)
                this.workingDesigns = [...this.workingDesigns]

                if (this.workingDesignIndex === index) {
                    if (index > 0) {
                        this.workingDesignIndex -= 1
                    }
                    eventBus.$emit(eventBus.REFRESH_BUILDER, this.currentDesign)
                }

                if (index < this.workingDesignIndex) {
                    this.workingDesignIndex -= 1
                }
            }

            if (this.workingDesigns[index].objects.length) {
                this.confirmation = {
                    title: 'Remove Design',
                    description: 'Are you really sure you want to remove the design?',
                    onConfirm: () => {
                        removeDesign()
                    }
                }
            } else {
                removeDesign()
            }
        },

        async handleAddToCartDesign() {
            this.$gsb.updateCanvasData()

            const json = this.currentDesign

            if (!Boolean(json.quantity)) {
                window.Toast.error({
                    message: 'Please input quantity.'
                })
            }

            if (json.objects.length > 0) {

                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.AGREE,
                    data: {singleSubmit: this.workingDesigns.length > 1},
                    onChange: async (createNew) => {
                        this.savingDesign = true
                        NProgress.start()

                        const thumbnail = await window._gangSheetCanvasEditor.exportThumbnail()

                        this.saveDesign(json, thumbnail).then(async (res) => {
                            this.clearStorageJson()

                            if (this.isStandalone) {
                                await this.addToCart(res.data.design_id, false)
                            } else {
                                const postData = this.getPostMessageData(json)

                                window.parent.postMessage({
                                    action: 'gs_add_to_cart',
                                    create_new_sheet: this.workingDesigns.length > 1 ? true : createNew,
                                    preview_url: res.data.preview_url,
                                    download_url: res.data.download_url,
                                    edit_url: res.data.edit_url,
                                    design_id: res.data.design_id,
                                    ...postData
                                }, '*')
                            }

                            this.savingDesign = false

                            if (this.workingDesigns.length === 1) {
                                if (createNew) {
                                    this.workingDesigns = []
                                    this.workingDesignIndex = 0
                                    this.hasDesignChange = false
                                    eventBus.$emit(eventBus.OPEN_NEW_DESIGN)
                                }
                            } else {
                                this.workingDesigns.splice(this.workingDesignIndex, 1)
                                this.workingDesigns = [...this.workingDesigns]
                                if (this.workingDesignIndex > 0) {
                                    this.workingDesignIndex -= 1
                                }

                                eventBus.$emit(eventBus.REFRESH_BUILDER, this.currentDesign)
                                window.Toast.success({
                                    message: 'Successfully added to cart.'
                                })
                            }

                        }).finally(() => {
                            NProgress.done()
                        })
                    }
                })
            } else {
                window.Toast.error({
                    message: 'You must add designs.'
                })
            }
        },
        handleEditDesignNameClick(e, index) {
            if (this.workingDesignIndex === index) {
                e.stopImmediatePropagation()
                this.editNameIndex = index
                this.$refs.designNameRef[index]?.focus()
            }
        },
        handleQuantityChange(e, index) {
            if (this.workingDesignIndex === index) {
                window._gangSheetCanvasEditor.quantity = Number(e.target.value)
            }
        },
        canRemove(index) {
            if (index === 0) {
                return false
            }

            if (this.applyDiscountPrice) {
                if (this.workingDesigns[index].meta.variant.visible === 'Hidden') {
                    return true
                }
            }

            return true
        },
        canAddToCart(index) {
            if (index === 0) {
                return false
            }

            let _b = index === this.workingDesignIndex && this.workingDesigns[index].objects.length > 0

            if (this.applyDiscountPrice) {
                if (this.workingDesigns[index].meta.variant.height >= this.discountThreshold) {
                    _b = false
                } else {
                    _b &= this.workingDesigns[index].meta.variant.visible !== 'Hidden'
                }
            }

            return _b
        }
    }
})
</script>

<template>
    <div class="absolute bg-builder border py-2 w-[290px] top-14 z-30 flex flex-col min-w-[150px] p-2 transition-all font-thin bottom-5"
         :class="[openWorkingDesigns ? 'right-5' : 'right-[-275px]']">
        <div class="absolute w-5 h-12 bg-builder border border-r-0 rounded rounded-r-none right-full top-[-1px] cursor-pointer flex items-center justify-center"
             @click="openWorkingDesigns = !openWorkingDesigns">
            <div class="transition-all" :class="{'rotate-180':openWorkingDesigns}">
                <svg-icon type="mdi" :path="mdiChevronLeft" size="20" class="shrink-0"/>
            </div>
        </div>
        <div class="w-full h-full overflow-y-auto tiny-scroll-bar text-xs space-y-2 pb-20">

            <div class="font-bold">({{ workingDesigns.length }}) &nbsp; {{ $t('Active Gang Sheets') }}</div>

            <div v-for="(design, index) in workingDesigns" class="rounded cursor-pointer border relative p-2"
                 :key="design.id"
                 :class="[index === workingDesignIndex ? 'gs-border-primary' :'border-gray-300']"
                 @click="handleDesignClick(index)">
                <div class="absolute right-2 top-2" v-if="index === workingDesignIndex && savingDesign">
                    <spinner/>
                </div>
                <div class="flex items-center px-1 opacity-80" :class="{'font-bold': index === 0, 'text-yellow-600': design.meta.variant.visible === 'Hidden'}">
                    <svg-icon v-if="index === 0" type="mdi" :path="mdiHome" size="16" class="mr-1"/>
                    <span class="text-2xs pt-0.5">{{ design.meta.variant.title || design.meta.variant.label }} </span>
                </div>
                <div class="flex">
                    <div ref="designNameRef" :contenteditable="editNameIndex === index" :tabindex="index" class="p-1 flex-1"
                         :class="{'cursor-text ring-2 ring-gray-900 rounded': editNameIndex === index}"
                         @focusout="handleDesignNameUpdate($event, index)">
                        {{ design.name }}
                    </div>
                    <div v-if="editNameIndex !== index" class="pt-1.5" @click="handleEditDesignNameClick($event, index)">
                        <svg-icon type="mdi" :path="mdiSquareEditOutline" size="16"/>
                    </div>
                </div>
                <small class="my-1 px-1 font-bold opacity-80"> {{ design.objects.filter(obj => !obj.isPattern).length }} {{ $t('Images') }}</small>
                <div class="flex justify-between items-center px-1 mt-2">
                    <div class="flex items-center space-x-1">
                        <div v-if="builderSettings.enableQuantity" class="flex items-center space-x-1">
                            <span>Qty:</span>
                            <input type="number" v-model="design.quantity" @input="handleQuantityChange($event, index)" step="1" min="1"
                                   class="inp-builder w-16 h-6 block pr-0 py-0">
                        </div>
                        <div v-if="canAddToCart(index)" @click="handleAddToCartDesign">
                            {{ $t('Add to Cart') }}
                        </div>
                    </div>
                    <div v-if="canRemove(index)" class="text-red-500 ml-auto hover:underline" @click.prevent.stop="handleDesignRemove(index)">
                        {{ $t('Remove') }}
                    </div>
                </div>
            </div>

            <div class="!mt-10 space-y-2 text-sm">
                <div v-if="builderSettings.enableAddNewDesign" class="flex items-center cursor-pointer hover:gs-text-primary hover:underline" @click="handleAddNewDesign">
                    <svg-icon type="mdi" :path="mdiInvoicePlusOutline" size="18" class="shrink-0 mr-1"/>
                    {{ $t('Add new design') }}
                </div>
                <div class="flex items-center cursor-pointer hover:gs-text-primary hover:underline" @click="handleOpenFromPreviousDesigns">
                    <svg-icon type="mdi" :path="mdiInvoiceTextPlusOutline" size="18" class="shrink-0 mr-1"/>
                    {{ $t('Open from previous designs') }}
                </div>
                <div v-if="permissions.autoNest" class="flex items-center cursor-pointer hover:gs-text-primary hover:underline" @click="autoNestMode = true">
                    <svg-icon type="mdi" :path="mdiAnimation" size="18" class="shrink-0 mr-1"/>
                    {{ $t('Auto Build') }}
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 w-[286px] p-2 text-xs bg-builder">
            Powered by <a href="http://www.thedripapps.com" target="_blank" class="gs-text-primary">Drip Apps</a>
        </div>
    </div>
</template>
