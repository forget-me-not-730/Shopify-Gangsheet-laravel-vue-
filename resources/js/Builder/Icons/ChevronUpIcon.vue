<template>
    <svg-icon type="mdi" :size="size" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiChevronUp } from '@mdi/js';

export default {
    name: "ChevronUpIcon",
    components: {
        SvgIcon
    },
    props: {
        size: [String, Number],
        default: 24
    },
    data() {
        return {
            path: mdiChevronUp,
        }
    }
}
</script>
