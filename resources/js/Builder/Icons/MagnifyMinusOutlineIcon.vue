<template>
    <svg-icon type="mdi" :size="size" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiMagnifyMinusOutline } from '@mdi/js';

export default {
    name: "MagnifyMinusOutlineIcon",
    components: {
        SvgIcon
    },
    props: {
        size: [String, Number],
        default: 24
    },
    data() {
        return {
            path: mdiMagnifyMinusOutline,
        }
    }
}
</script>
