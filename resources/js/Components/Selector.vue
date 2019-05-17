<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiCheck} from '@mdi/js';

export default defineComponent({
    name: "Selector",
    components: {SvgIcon},
    props: {
        modelValue: {
            type: [Number, String, null],
            default: ''
        },
        options: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            mdiCheck
        }
    },
    computed: {
        optionValues() {
            return this.options.map(option => {
                return {
                    label: option?.label || option,
                    value: option?.value || option
                }
            })
        },
        value: {
            get() {
                return this.modelValue
            },
            set(value) {
                this.$emit('update:modelValue', value)
            }
        },
        optionStyle() {
            const index = this.optionValues.findIndex(option => option.value === this.value)
            return {
                left: `${Math.max(0, index) * 100 / this.options.length}%`,
                width: `${100 / this.options.length}%`
            }
        }
    }
})
</script>

<template>
    <div class="flex w-min items-center shadow bg-gray-100 text-xs relative cursor-pointer rounded-sm">
        <div class="h-full absolute top-0 p-px flex items-center justify-center transition-all" :style="optionStyle">
            <div class="bg-white h-full w-full rounded-sm"></div>
        </div>
        <div v-for="(option, index) in optionValues" :style="{width: `${100 / this.options.length}%`}" class="text-center uppercase relative z-10 flex items-center justify-center py-0.5 px-4"
             :class="[value === option.value ? 'text-gray-700' : 'text-gray-400', {'border-l': index > 0 }]"
             @click="value = option.value">
            <svg-icon v-if="value === option.value" type="mdi" :path="mdiCheck" size="14" class="w-4 shrink-0 text-green-500"/>
            <span>{{ option.label }}</span>
        </div>
    </div>
</template>

<style scoped>

</style>
