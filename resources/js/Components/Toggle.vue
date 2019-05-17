<script>
import {defineComponent} from 'vue'
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiCheckCircleOutline, mdiCloseCircleOutline} from '@mdi/js';

export default defineComponent({
    name: "Toggle",
    components: {SvgIcon},
    props: {
        modelValue: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            mdiCheckCircleOutline,
            mdiCloseCircleOutline
        }
    },
    computed: {
        value: {
            get() {
                return this.modelValue
            },
            set(value) {
                this.$emit('update:modelValue', value)
            }
        }
    }
})
</script>

<template>
    <div class="flex items-center w-24 shadow bg-gray-100 text-xs relative cursor-pointer rounded-sm">
        <div class="w-1/2 h-full absolute top-0 p-px flex items-center justify-center transition-all" :class="[value ? 'left-[50%]' : 'left-0']">
            <div class="bg-white h-full w-full rounded-sm"></div>
        </div>
        <div class="flex-1 text-center relative z-10 flex items-center justify-center p-0.5" :class="{'text-gray-400': value}" @click="value = false">
            <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="16" class="mr-px" :class="{'text-red-500': !value}"/>
            <span>OFF</span>
        </div>
        <div class="flex-1 text-center relative z-10 flex items-center justify-center p-0.5" :class="{'text-gray-400': !value}" @click="value = true">
            <svg-icon type="mdi" :path="mdiCheckCircleOutline" size="16" class="mr-px" :class="{'text-green-500': value}"/>
            <span>ON</span>
        </div>
    </div>
</template>

<style scoped>

</style>
