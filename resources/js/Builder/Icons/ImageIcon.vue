<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiImagePlusOutline} from '@mdi/js';

export default {
    name: "ImageIcon",
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
            path: mdiImagePlusOutline,
        }
    }
}
</script>
