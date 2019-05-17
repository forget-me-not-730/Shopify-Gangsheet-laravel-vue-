<template>
    <svg-icon type="mdi" :size="size" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiChevronLeft} from '@mdi/js';

export default {
    name: "ChevronLeftIcon",
    components: {
        SvgIcon
    },
    props: {
        size: [String, Number],
        default: 24
    },
    data() {
        return {
            path: mdiChevronLeft,
        }
    }
}
</script>
