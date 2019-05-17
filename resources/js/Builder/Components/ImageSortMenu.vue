<script setup>
import {ref, watch} from 'vue'
import {Menu, MenuButton, MenuItems, MenuItem} from '@headlessui/vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiSort, mdiSortAlphabeticalAscending, mdiSortAlphabeticalDescending, mdiCalendar, mdiClockOutline, mdiTrendingUp, mdiTrendingDown} from '@mdi/js'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue', 'change'])

const selectedOptions = ref(props.modelValue)

const sortOptions = [
    {value: 'abc', label: 'A to Z', group: 'order', icon: mdiSortAlphabeticalAscending},
    {value: 'z-a', label: 'Z to A', group: 'order', icon: mdiSortAlphabeticalDescending},
    {value: 'newest', label: 'Newest', group: 'date', icon: mdiCalendar},
    {value: 'oldest', label: 'Oldest', group: 'date', icon: mdiClockOutline},
    {value: 'most-used', label: 'Most Used', group: 'usage', icon: mdiTrendingUp},
    {value: 'least-used', label: 'Least Used', group: 'usage', icon: mdiTrendingDown}
]

watch(() => props.modelValue, (newVal) => {
    selectedOptions.value = newVal
})

const handleSelect = (option) => {
    let newSelection = [...selectedOptions.value]

    if (newSelection.includes(option.value)) {
        newSelection = newSelection.filter(val => val !== option.value)
    } else {
        newSelection = newSelection.filter(val => {
            const optionFromSameGroup = sortOptions.find(opt => opt.value === val)
            return optionFromSameGroup.group !== option.group
        })
        newSelection.push(option.value)
    }

    if (newSelection.length === 0) {
        newSelection = ['abc']
    }

    selectedOptions.value = newSelection
    emit('update:modelValue', newSelection)
    emit('change', newSelection)
}
</script>

<template>
    <Menu as="div" class="relative h-full">
        <MenuButton
            class="flex items-center gap-1 rounded-md px-2 h-[34px] border border-gray-300">
            <SvgIcon type="mdi" :path="mdiSort" class="w-4 h-4"/>
            <span>Sort</span>
            <div v-if="selectedOptions.length > 0"
                 class="h-4 w-4 rounded-full gs-bg-primary text-2xs flex items-center justify-center">
                {{ selectedOptions.length }}
            </div>
        </MenuButton>

        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
            <MenuItems
                class="absolute left-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg z-50 border border-gray-200 focus:outline-none">
                <div class="py-1">
                    <div class="px-2 py-1 text-xs text-gray-500">Title</div>
                    <MenuItem v-for="option in sortOptions.filter(opt => opt.group === 'order')" :key="option.value"
                              v-slot="{ active }">
                        <button @click="handleSelect(option)"
                                :class="[
                                    'w-full text-left px-4 py-2 text-sm flex items-center gap-2',
                                    active ? 'bg-black/10' : '',
                                    selectedOptions.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'
                                  ]">
                            <span class="flex-1">{{ option.label }}</span>
                            <span v-if="selectedOptions.includes(option.value)" class="gs-text-primary">✓</span>
                            <SvgIcon type="mdi" :path="option.icon" :size="18"
                                     :class="selectedOptions.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'"/>
                        </button>
                    </MenuItem>

                    <div class="border-t mt-1"></div>
                    <div class="px-2 py-1 text-xs text-gray-500">Date</div>
                    <MenuItem v-for="option in sortOptions.filter(opt => opt.group === 'date')" :key="option.value"
                              v-slot="{ active }">
                        <button @click="handleSelect(option)"
                                :class="[
                                    'w-full text-left px-4 py-2 text-sm flex items-center gap-2',
                                    active ? 'bg-black/10' : '',
                                    selectedOptions.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'
                                  ]">
                            <span class="flex-1">{{ option.label }}</span>
                            <span v-if="selectedOptions.includes(option.value)" class="gs-text-primary">✓</span>
                            <SvgIcon type="mdi" :path="option.icon" :size="18"
                                     :class="selectedOptions.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'"/>
                        </button>
                    </MenuItem>

                    <div class="border-t mt-1"></div>
                    <div class="px-2 py-1 text-xs text-gray-500">Usage</div>
                    <MenuItem v-for="option in sortOptions.filter(opt => opt.group === 'usage')" :key="option.value"
                              v-slot="{ active }">
                        <button @click="handleSelect(option)"
                                :class="[
                                    'w-full text-left px-4 py-2 text-sm flex items-center gap-2',
                                    active ? 'bg-black/10' : '',
                                    selectedOptions.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'
                                  ]">
                            <span class="flex-1">{{ option.label }}</span>
                            <span v-if="selectedOptions.includes(option.value)" class="gs-text-primary">✓</span>
                            <SvgIcon type="mdi" :path="option.icon" :size="18"
                                     :class="selectedOptions.includes(option.value) ? 'gs-text-primary' : 'text-gray-400'"/>
                        </button>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
