<script>
import {defineComponent} from 'vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import builderMixin from "@/Builder/Mixins/builderMixin";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiChevronDown} from '@mdi/js'

export default defineComponent({
    name: "Variants",
    components: {MenuItem, MenuItems, MenuButton, Menu, SvgIcon},
    mixins: [builderMixin],
    props: {
        hasAll: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },
        modelValue: {
            type: [String, Number],
            default: undefined
        }
    },
    data() {
        return {
            isAll: false,
            mdiChevronDown
        }
    },
    computed: {
        variant_id() {
            return this.modelValue || this.variant_id;
        },
        selectedVariant() {
            return this.variants.find(v => v.id.toString() === this.variant_id?.toString())
        }
    },
    methods: {
        handleSizeChange(variantId) {
            this.isAll = false
            this.$emit('change', variantId)
            this.$emit('update:modelValue', variantId)
        },
        handleSelectAll() {
            this.isAll = true
            this.$emit('change', 'all')
            this.$emit('update:modelValue', 'all')
        }
    }
})
</script>

<template>
    <Menu as="div" class="relative inline-block text-left w-full h-full">
        <div class="h-full w-full" :class="{'pointer-events-none': order}">
            <menu-button
                class="w-full min-w-[100px] justify-between gap-x-1.5 h-full flex items-center rounded-md px-3 py-1 text-sm shadow-sm ring-1 ring-inset ring-gray-300">
                {{ (isAll || !selectedVariant) ? 'All' : `${selectedVariant.width}${selectedVariant.unit} X ${selectedVariant.height}${selectedVariant.unit}` }}
                <svg-icon type="mdi" :path="mdiChevronDown" size="16"/>
            </menu-button>
        </div>

        <transition v-if="!disabled" enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95">
            <menu-items class="absolute left-0 z-50 mt-1 w-56 origin-top-right rounded-md bg-builder shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="py-1 overflow-y-auto tiny-scroll-bar z-50" style="max-height: calc(100vh - 150px)">
                    <menu-item v-if="hasAll">
                        <div class="cursor-pointer" @click="handleSelectAll" :class="[isAll ? 'gs-bg-primary' : 'hover:bg-info hover:bg-opacity-20', 'block px-4 py-2 text-sm']">
                            {{ $t('All') }}
                        </div>
                    </menu-item>
                    <template v-for="(item, index) in variants" :key="index">
                        <menu-item v-if="item.visible !== 'Hidden'">
                            <div @click="handleSizeChange(item.id)" class=" cursor-pointer"
                                 :class="[!isAll && item.id === variant_id ? 'gs-bg-primary' : 'text-inherit hover:bg-info hover:bg-opacity-20', 'block px-4 py-2 text-sm']">
                                    <span v-if="item.title">
                                        {{ item.title }}
                                    </span>
                                <span v-else>
                                    {{ `${item.width}${item.unit} X ${item.height}${item.unit}` }}
                                </span>
                            </div>
                        </menu-item>
                    </template>
                </div>
            </menu-items>
        </transition>
    </Menu>
</template>

<style scoped>

</style>
