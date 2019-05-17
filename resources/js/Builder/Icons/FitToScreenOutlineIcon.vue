<template>
    <svg-icon type="mdi" :size="size" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiFitToScreenOutline } from '@mdi/js';

export default {
    name: "FitToScreenOutlineIcon",
    components: {
        SvgIcon
    },
    props: {
        size: [String, Number],
        default: 24
    },
    data() {
        return {
            path: mdiFitToScreenOutline,
        }
    }
}
</script>
