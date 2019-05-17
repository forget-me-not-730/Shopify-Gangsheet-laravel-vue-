<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiTrashCanOutline} from '@mdi/js';

export default {
    name: "DeleteIcon",
    components: {
        SvgIcon
    },
    props: {
        size: [String, Number],
        default: 24
    },
    data() {
        return {
            path: mdiTrashCanOutline,
        }
    }
}
</script>
