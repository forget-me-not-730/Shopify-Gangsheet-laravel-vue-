<script>
import {defineComponent} from "vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import ChevronDownIcon from "@/Builder/Icons/ChevronDownIcon.vue";
import CheckIcon from "@/Builder/Icons/CheckIcon.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import SearchInput from "@/Builder/Components/SearchInput.vue";

export default defineComponent({
    name: "GsSelect",
    components: {SearchInput, CloseIcon, SvgIcon, CheckIcon, ChevronDownIcon, ListboxOption, ListboxOptions, ListboxButton, Listbox},
    props: {
        modelValue: {
            type: [String, Number, Object],
            required: true,
        },
        options: {
            type: Array,
            required: true,
        },
        search: {
            type: Boolean,
            default: false
        },
    },
    emits: ['update:modelValue', 'input'],
    data() {
        return {
            keyWord: '',
        }
    },
    computed: {
        value: {
            get() {
                return this.modelValue
            },
            set(val) {
                this.$emit('update:modelValue', val)
                this.$emit('input', val)
            }
        },
        filteredOptions() {
            if (this.keyWord) {
                return this.options.filter(option => option.label.toLowerCase().includes(this.keyWord.toLowerCase()))
            }
            return this.options
        }
    },
    mounted() {
        if (!this.modelValue) {
            this.$emit('update:modelValue', this.options[0])
        }
    }
})
</script>

<template>
    <Listbox v-model="value" v-slot="" as="div" class="relative rounded h-8 border border-gray-300">
        <ListboxButton as="div" class="relative truncate w-full h-full cursor-default flex items-center pl-3 text-left">
            <span :style="{fontFamily: value?.value}">{{ typeof value === 'object' ? value.label : value }}</span>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <chevron-down-icon size="20"/>
            </span>
        </ListboxButton>
        <transition
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <ListboxOptions class="z-50 absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-builder border pb-1 border-gray-300">
                <ListboxOption as="template" v-if="search && options.length > 6" :disabled="true">
                    <div class="sticky py-1 px-2 -top-0 z-50 bg-builder">
                        <search-input v-model="keyWord"/>
                    </div>
                </ListboxOption>
                <ListboxOption
                    v-slot="{ active, selected }"
                    v-for="option in filteredOptions"
                    :key="option.value"
                    :value="option"
                    as="template"
                >
                    <li
                        class="relative leading-9 cursor-default select-none pl-8 pr-4"
                        :class="[active ? 'gs-bg-primary' : '', ]">
                            <span
                                :class="[
                                    option.value === value?.value ? 'font-medium' : 'font-normal',
                                    'block truncate',
                                ]"
                                :style="{fontFamily: option.value}"
                            >
                                {{ option.label }}
                            </span>
                        <span v-if="option.value === value?.value"
                              class="absolute inset-y-0 left-0 flex items-center pl-2">
                            <check-icon size="18"/>
                        </span>
                    </li>
                </ListboxOption>
            </ListboxOptions>
        </transition>
    </Listbox>
</template>

<style scoped>

</style>
