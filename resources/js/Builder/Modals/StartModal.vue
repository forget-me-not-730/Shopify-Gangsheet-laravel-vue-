<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import eventBus from "@/Builder/Utils/eventBus.js";
import {MODAL_NAMES, TOOLS} from "@/Builder/Utils/constants.js";
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiArrowRight, mdiClose} from '@mdi/js'
import GsbImage from "@/Builder/Components/GsbImage.vue";

export default defineComponent({
    name: "StartModal",
    components: {GsbImage, Modal, SvgIcon},
    mixins: [gangSheetMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            mdiArrowRight,
            mdiClose
        }
    },
    computed: {
        startModals() {
            return this.shopSettings.startModals.filter(item => {

                if (item.id === 3) {
                    item.enabled = item.enabled && this.permissions.autoNest
                }

                if (item.id === 4) {
                    item.enabled = item.enabled && this.currentDesign?.objects.length
                }

                if (item.id === 5) {
                    item.enabled = item.enabled && this.builderSettings.nameAndNumber?.enabled
                }

                return item.enabled;
            })
        },
    },
    methods: {
        handleOpenNewDesign() {
            this.$emit('close')
            if (this.currentDesign?.objects.length) {
                eventBus.$emit(eventBus.OPEN_NEW_DESIGN)
            }
        },
        handleOpenMyDesigns() {
            this.$emit('close')
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.CUSTOMER_DESIGNS
            })
        },
        handleAutoBuildClick() {
            this.$emit('close')
            this.autoNestMode = true
        },
        handleNameAndNumberClick() {
            this.$emit('close')
            this.tool = TOOLS.nameAndNumbers

            if (window.innerWidth <= 768) {
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.NAME_AND_NUMBERS
                })
            }
        },
        handleClick(id) {
            switch (Number(id)) {
                case 1:
                    this.handleOpenNewDesign()
                    break
                case 2:
                    this.handleOpenMyDesigns()
                    break
                case 3:
                    this.handleAutoBuildClick()
                    break
                case 4:
                    this.$emit('close')
                    break
                case 5:
                    this.handleNameAndNumberClick()
                    break
            }
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')" :full-width="true">
        <div class="flex w-full h-full justify-center items-center">
            <div class="flex flex-col bg-builder border sm:rounded overflow-hidden w-full mx-auto max-sm:h-full"
                 :style="{maxWidth: shopSettings.startModalView === 'list' ? '500px': 225 * startModals.length + 'px' }">
                <div class="flex justify-between relative px-4 pt-2">
                    <h1 class="text-xl font-bold mb-3">{{ $t('Welcome to Build a Gang Sheet') }}</h1>
                    <div class="absolute right-[14px] top-3 text-3xl cursor-pointer" @click="$emit('close')">
                        <svg-icon type="mdi" :path="mdiClose" size="18"/>
                    </div>
                </div>
                <hr/>
                <div class="p-5">
                    <div v-if="shopSettings.startModalView === 'list'" class="text-left space-y-2">
                        <template v-for="item in startModals">
                            <div v-if="item.enabled"
                                 class="btn-builder flex justify-between hover:bg-gray-200 cursor-pointer py-2"
                                 @click="handleClick(item?.id)">
                                <div v-html="item.label"></div>
                                <svg-icon type="mdi" :path="mdiArrowRight" size="18"/>
                            </div>
                        </template>
                    </div>
                    <div v-else class="text-left">
                        <div class="grid grid-cols-2 gap-4" :class=" ['md:grid-cols-' + startModals.length]">
                            <template v-for="item in startModals">
                                <div v-if="item.enabled"
                                     class="flex flex-col cursor-pointer group"
                                     @click="handleClick(item?.id)">
                                    <div class="aspect-square border rounded bg-gray-200 overflow-hidden">
                                        <gsb-image :src="item.image"/>
                                    </div>
                                    <div class="mt-1">
                                        <div class="w-full group-hover:-mt-1 transition-all" v-html="item.label"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div v-if="currentDesign?.objects.length" class="text-left w-full">
                        <small>
                            {{ $t('It appears that there is an unsaved working design, but it has been automatically saved.') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
