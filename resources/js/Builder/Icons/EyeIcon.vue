<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiEyeOutline} from '@mdi/js';

export default {
    name: "EyeIcon",
    components: {
        SvgIcon
    },
    props: {
        size: {
            type: Number,
            default: 24
        }
    },
    data() {
        return {
            path: mdiEyeOutline,
        }
    }
}
</script>
