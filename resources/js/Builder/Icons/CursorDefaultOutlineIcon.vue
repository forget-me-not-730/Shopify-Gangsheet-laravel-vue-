<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiCursorDefaultOutline} from '@mdi/js';

export default {
    name: "CursorDefaultOutlineIcon",
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
            path: mdiCursorDefaultOutline,
        }
    }
}
</script>
