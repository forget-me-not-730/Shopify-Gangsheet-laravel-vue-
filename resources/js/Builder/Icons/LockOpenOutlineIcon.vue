<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiLockOpenOutline} from '@mdi/js';

export default {
    name: "LockOpenOutlineIcon",
    components: {
        SvgIcon
    },
    props: {
        size: {
            type: [String, Number],
            default: '16'
        }
    },
    data() {
        return {
            path: mdiLockOpenOutline,
        }
    }
}
</script>
