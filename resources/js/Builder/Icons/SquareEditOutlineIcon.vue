<template>
    <svg-icon type="mdi" :size="size" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiSquareEditOutline} from '@mdi/js';

export default {
    name: "SquareEditOutlineIcon",
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
            path: mdiSquareEditOutline,
        }
    }
}
</script>
