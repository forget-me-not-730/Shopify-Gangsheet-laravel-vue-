<script>
import {defineComponent} from 'vue'
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import ChevronLeftIcon from "@/Builder/Icons/ChevronLeftIcon.vue";
import SquareEditOutlineIcon from "@/Builder/Icons/SquareEditOutlineIcon.vue";
import eventBus from "@/Builder/Utils/eventBus";
import ConfirmationMixin from "@/Builder/Mixins/confirmationMixin";
import AnimationIcon from "@/Builder/Icons/AnimationIcon.vue";
import InvoiceTextPlusOutlineIcon from "@/Builder/Icons/InvoiceTextPlusOutlineIcon.vue";
import {MODAL_NAMES} from "@/Builder/Utils/constants";

export default defineComponent({
    name: "RollingCanvasDesigns",
    components: {InvoiceTextPlusOutlineIcon, AnimationIcon, SquareEditOutlineIcon, ChevronLeftIcon},
    mixins: [gangSheetMixin, ConfirmationMixin],
    data() {
        return {
            editNameIndex: null,
        }
    },
    methods: {
        handleDesignClick(index) {
            if (this.workingDesignIndex !== index) {
                this.$gsb.updateCanvasData()
                this.workingDesignIndex = index
                this.$nextTick(() => {
                    this.images = this.currentDesign.meta.images ?? []
                })
            }
        },
        handleDesignNameUpdate(e, designIndex) {
            const name = e.target.textContent.trim().replace('\n', ' ').substring(0, 100) || 'Untitled'
            this.workingDesigns[designIndex].name = name
            if (this.workingDesignIndex === designIndex) {
                _gangSheetCanvasEditor.name = name
            }
            this.editNameIndex = null
            e.target.innerHTML = name
        },
        handleEditDesignNameClick(e, index) {
            if (this.workingDesignIndex === index) {
                e.stopImmediatePropagation()
                this.editNameIndex = index
                this.$refs.designNameRef[index]?.focus()
            }
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
        canRemove(index) {
            return index !== 0;
        },
        handleOpenFromPreviousDesigns() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.CUSTOMER_DESIGNS
            })
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
                <chevron-left-icon size="20" class="shrink-0"/>
            </div>
        </div>
        <div class="w-full h-full overflow-y-auto tiny-scroll-bar text-xs space-y-2 pb-20">

            <div class="font-bold">({{ workingDesigns.length }}) &nbsp; {{ $t('Active Gang Sheets') }}</div>

            <div v-for="(design, index) in workingDesigns" class="rounded cursor-pointer border relative p-2"
                 :key="design.id"
                 :class="[index === workingDesignIndex ? 'gs-border-primary' :'border-gray-300']"
                 @click="handleDesignClick(index)">

                <div class="flex items-center px-1" :class="{'font-bold': index === 0, 'text-yellow-600': design.meta.variant.visible === 'Hidden'}">
                    <span class="text-2xs pt-0.5">
                        {{ design.meta.variant.width }} x {{ design.meta.variant.height }} {{ design.meta.variant.unit }}
                    </span>
                </div>
                <div class="flex">
                    <div ref="designNameRef" :contenteditable="editNameIndex === index" :tabindex="index" class="p-1 flex-1"
                         :class="{'cursor-text ring-2 ring-gray-900 rounded': editNameIndex === index}"
                         @focusout="handleDesignNameUpdate($event, index)">
                        {{ design.name }}
                    </div>
                    <div v-if="editNameIndex !== index" class="pt-1.5" @click="handleEditDesignNameClick($event, index)">
                        <square-edit-outline-icon size="16"/>
                    </div>
                </div>
                <small class="my-1 px-1 font-bold opacity-80"> {{ design.objects.length }} {{ $t('Images') }}</small>
                <div class="flex justify-between items-center px-1 mt-2 h-7">
                    <div v-if="canRemove(index)" class="text-red-500 ml-auto hover:underline" @click.prevent.stop="handleDesignRemove(index)">
                        {{ $t('Remove') }}
                    </div>
                </div>
            </div>

            <div class="!mt-10 space-y-2 text-sm">
                <div class="flex items-center cursor-pointer hover:gs-text-primary hover:underline" @click="handleOpenFromPreviousDesigns">
                    <invoice-text-plus-outline-icon size="18" class="shrink-0 mr-1"/>
                    {{ $t('Open from previous designs') }}
                </div>
                <div class="flex items-center cursor-pointer hover:gs-text-primary hover:underline" @click="autoNestMode = true">
                    <animation-icon size="18" class="shrink-0 mr-1"/>
                    {{ $t('Auto Build') }}
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 w-[286px] p-2 text-xs bg-builder">
            Powered by <a href="http://www.thedripapps.com" target="_blank" class="text-blue-500">Drip Apps</a>
        </div>
    </div>
</template>
