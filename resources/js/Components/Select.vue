<script>
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {
    mdiClose,
    mdiUnfoldMoreHorizontal,
    mdiCheck
} from '@mdi/js'

export default {
    name: 'GbsSelect',
    components: {SvgIcon, ListboxOption, ListboxOptions, ListboxButton, ListboxLabel, Listbox},
    props: {
        label: {
            type: [String, null],
            default: null
        },
        modelValue: {
            required: true
        },
        options: {
            type: Array,
            required: true
        },
        autoSelect: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },
        inline: {
            type: Boolean,
            default: false
        },
        search: {
            type: [Array, String, Boolean],
            default: false
        },
        clear: {
            type: Boolean,
            default: false
        },
        placeholder: {
            type: String,
            default: 'Select'
        }
    },
    data() {
        return {
            keyWord: '',

            mdiClose,
            mdiUnfoldMoreHorizontal,
            mdiCheck
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
            if (this.search && this.keyWord) {
                if (this.search === true) {
                    return this.options.filter(item => JSON.stringify(item).toLowerCase().includes(this.keyWord.toLowerCase()))
                } else if (typeof this.search === 'string') {
                    return this.options.filter(item => item[this.search]?.includes(this.keyWord))
                } else if (typeof this.search === 'object') {
                    return this.options.filter(item => {
                        let filtered = false
                        for (const key of this.search) {
                            filtered ||= item[key]?.includes(this.keyWord)
                        }
                        return filtered
                    })
                }
            }

            return this.options
        }
    },
    mounted() {
        if (this.autoSelect && !this.modelValue) {
            this.$emit('update:modelValue', this.options[0])
        }
    }
}
</script>

<template>
    <Listbox as="div" v-model="value" :disabled="disabled" class="relative" :class="{'flex items-center': inline}" :required="true">
        <slot v-if="label" name="label" :selected="value" :label="label">
            <ListboxLabel class="block text-sm leading-6 text-gray-900" :class="[inline ? 'mr-2' : 'mb-1']">
                {{ label }}
            </ListboxLabel>
        </slot>
        <div class="relative cursor-pointer h-full">
            <ListboxButton
                class="relative w-full h-full cursor-default bg-white min-w-[50px] py-1.5 pl-3 pr-10 text-left text-gray-900 border border-gray-500 rounded focus:outline-none  sm:text-sm sm:leading-6"
                :class="{'text-gray-300': disabled}"
            >
                <span class="flex items-center">
                    <slot v-if="$slots.selected" name="selected" :selected="value" :placeholder="placeholder"/>
                    <span v-else :class="{'text-gray-400': !value}">{{ value || placeholder }}</span>
                </span>
                <span v-if="value && clear" @click="value = null" class="cursor-pointer absolute inset-y-0 right-5 ml-3 flex items-center pr-2">
                    <svg-icon type="mdi" :path="mdiClose" size="16"/>
                </span>
                <span class="pointer-events-none absolute inset-y-0 right-0 ml-3 flex items-center pr-2">
                    <svg-icon type="mdi" :path="mdiUnfoldMoreHorizontal" size="16"/>
                </span>
            </ListboxButton>
            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <ListboxOptions
                    class="absolute z-10 mt-1 max-h-56 w-full overflow-auto rounded bg-white shadow ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <ListboxOption as="template" v-if="search && options.length > 0" :disabled="true">
                        <div class="sticky bg-white select-none py-2 px-2 top-0 z-50">
                            <div class="border rounded bg-white">
                                <input v-model="keyWord" @keydown.stop="" class="w-full focus:outline-0 h-8 rounded border-0 px-1 focus:ring-0 text-sm"  placeholder="Search"/>
                                <span class="text-indigo-600 absolute cursor-pointer inset-y-0 right-0 flex items-center pr-3" @click="keyWord = ''">
                                    <svg-icon type="mdi" :path="mdiClose" size="16"/>
                                </span>
                            </div>
                        </div>
                    </ListboxOption>
                    <ListboxOption as="template" v-for="(option, index) in filteredOptions" :key="index" :value="option" v-slot="{ active, selected }">
                        <li :class="[active ? 'bg-blue-500 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9', {'bg-blue-400 text-white': selected}]"
                            @click="$emit('select', option)">
                            <div class="flex items-center">
                                <slot v-if="$slots.option" name="option" :option="option"/>
                                <span v-else >{{ option }}</span>
                            </div>
                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                <svg-icon type="mdi" :path="mdiCheck" size="16"/>
                            </span>
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </transition>
        </div>
    </Listbox>
</template>

<style scoped lang="scss">

</style>
