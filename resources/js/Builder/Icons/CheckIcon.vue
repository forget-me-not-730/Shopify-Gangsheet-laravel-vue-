<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiCheck} from '@mdi/js';

export default {
    name: "CheckIcon",
    components: {
        SvgIcon
    },
    props: {
        size: {
            type: [String, Number],
            default: 24
        }
    },
    data() {
        return {
            path: mdiCheck,
        }
    }
}
</script>
