<script setup>
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiDrag } from '@mdi/js'

const props = defineProps({
    size: {
        type: Number,
        default: 24
    }
})

</script>

<template>
    <svg-icon type="mdi" :path="mdiDrag" :size="size"/>
</template>
