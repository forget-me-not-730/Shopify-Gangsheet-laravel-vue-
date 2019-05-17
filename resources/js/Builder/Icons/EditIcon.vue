<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiSquareEditOutline} from '@mdi/js';

export default {
    name: "EditIcon",
    components: {
        SvgIcon
    },
    props: {
        size: {
            type: [Number, String],
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
