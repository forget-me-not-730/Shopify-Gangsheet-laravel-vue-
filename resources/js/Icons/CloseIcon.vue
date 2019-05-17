<script setup>
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'

const props = defineProps({
    size: {
        type: Number,
        default: 24
    }
})

</script>

<template>
    <svg-icon type="mdi" :path="mdiClose" :size="size"/>
</template>
