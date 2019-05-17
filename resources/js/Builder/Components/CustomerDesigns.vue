<script>
import {defineComponent} from 'vue'

import SvgIcon from '@jamescoyle/vue-icon'
import {mdiChevronLeft, mdiChevronRight} from '@mdi/js'
import Spinner from '../Components/Spinner.vue'
import GsbImage from '@/Builder/Components/GsbImage.vue'
import Variants from '@/Builder/Components/Variants.vue'
import moment from 'moment-timezone'
import eventBus from '@/Builder/Utils/eventBus'
import builderMixin from '@/Builder/Mixins/builderMixin'
import confirmationMixin from '@/Builder/Mixins/confirmationMixin'
import ChevronLeftIcon from "@/Builder/Icons/ChevronLeftIcon.vue";
import ChevronRightIcon from "@/Builder/Icons/ChevronRightIcon.vue";

export default defineComponent({
    name: 'MyDesignsTable',
    components: {ChevronRightIcon, ChevronLeftIcon, GsbImage, Spinner, SvgIcon, Variants},
    mixins: [builderMixin, confirmationMixin],
    props: {
        pages: {
            type: Array,
            default: []
        },
        type: {
            type: String,
            default: 'available' // archived
        }
    },
    emits: ['reload'],
    data() {
        return {
            currentPage: 0,
            deletingDesign: null,
            restoringDesign: null,

            mdiChevronLeft,
            mdiChevronRight
        }
    },
    methods: {
        moment,
        onChangePage(page) {
            this.$emit('page', page)
        },
        getVariantTitle(design) {
            let variant = design.variant

            if (!variant) {
                const variant_id = design.variant_id || design.size_id

                if (variant_id) {
                    variant = this.variants.find(v => v.id.toString() === variant_id.toString())
                }
            }

            if (!variant) {
                variant = design.data?.meta?.variant
            }

            if (variant) {
                return `${variant.width}${variant.unit} x ${variant.height}${variant.unit}`
            }

            return 'Unknown'
        },
        handleDeleteDesign(designId) {
            this.confirmation = {
                title: 'Delete Design',
                description: 'Are you really sure you want to delete selected design?',
                onConfirm: () => {
                    this.deletingDesign = designId
                    NProgress.start()
                    axios.delete(route('builder.delete-design', {design_id: designId})).then(async (res) => {
                        this.$emit('reload')
                        this.deletingDesign = null
                        NProgress.done()
                    })
                }
            }
        },
        handleRestoreDesign(designId) {
            NProgress.start()
            this.restoringDesign = designId
            axios.post(route('builder.restore-design', {design_id: designId})).then(async (res) => {
                this.$emit('reload')
                this.restoringDesign = null
                NProgress.done()
            })
        },
        handleOpenDesign(designId) {
            eventBus.$emit(eventBus.CLOSE_MODAL)
            eventBus.$emit(eventBus.OPEN_DESIGN, designId)
        },
    }
})
</script>

<template>
    <div class="max-sm:min-w-[80vw] flex-1 flex flex-col">
        <div class="flex-1 relative">
            <div v-if="customerDesigns?.length"
                 class="grid sm:grid-cols-2 md:grid-cols-4 gap-2 max-h-[75vh] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                <div v-for="design in customerDesigns" class="rounded border border-gray-300 cursor-pointer relative overflow-hidden flex flex-col">
                    <div class="aspect-square w-full bg-gray-300 bg-opacity-50 relative overflow-y-auto">
                        <div v-if="design.status === 'draft'"
                             class="absolute bg-orange-500 right-0 text-white text-xs rounded py-1 px-2 m-2 z-30">
                            Draft
                        </div>
                        <gsb-image :src="design.thumbnail_url" alt="" class="aspect-square w-full h-full"/>
                    </div>
                    <div class="p-1 flex flex-1 flex-col text-left justify-between">
                        <span class="text-sm">{{ design.name }}</span>
                        <div class="w-full flex justify-between items-end">
                            <div class="flex flex-col text-xs">
                                <small> ({{ getVariantTitle(design) }})</small>
                                <span class="text-gray-400">{{ moment(design.created_at).format('l') }}</span>
                            </div>
                            <div v-if="type === 'available'" class="text-xs space-x-1 flex items-center">
                                <button
                                    :disabled="deletingDesign"
                                    class="btn-builder btn-sm disabled:bg-gray-300"
                                    @click="handleOpenDesign(design.id)">
                                    {{ $t('Open') }}
                                </button>
                                <button :disabled="deletingDesign"
                                        class="btn-danger rounded btn-sm font-thin !text-xs disabled:bg-gray-300"
                                        @click="handleDeleteDesign(design.id)">
                                    <spinner v-if="deletingDesign" class="mr-1 !w-3 !h-3"/>
                                    {{ $t('Delete') }}
                                </button>
                            </div>
                            <div v-else class="text-xs space-x-1 flex items-center">
                                <button
                                    class="btn-builder rounded btn-sm font-thin !text-xs disabled:bg-gray-300"
                                    :disabled="restoringDesign"
                                    @click="handleRestoreDesign(design.id)">
                                    {{ $t('Restore') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="!customerDesigns?.length"
                 class="py-16 text-gray-400 text-lg">
                {{ $t('No Designs') }}
            </div>
        </div>

        <div v-if="customerDesigns?.length" class="bg-builder z-10 mx-auto mt-2 text-xs py-px">
            <div class="flex w-max mx-auto border-l border-t border-b">
                <div
                    v-for="page in pages"
                    @click="onChangePage(page)"
                    class="cursor-pointer flex items-center h-[30px] min-w-[30px] border-r justify-center"
                    :class="page.active ? 'gs-bg-primary': 'hover:text-gray-900 hover:bg-gray-50'"
                >
                    <chevron-left-icon v-if="page.label.includes('Previous')" size="18"/>
                    <chevron-right-icon v-else-if="page.label.includes('Next')" size="18"/>
                    <span v-else>{{ page.label }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
