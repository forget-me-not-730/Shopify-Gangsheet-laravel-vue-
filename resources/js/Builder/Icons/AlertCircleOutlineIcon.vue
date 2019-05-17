<template>
    <svg-icon type="mdi" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiAlertCircleOutline} from '@mdi/js';

export default {
    name: "AlertCircleOutlineIcon",
    components: {
        SvgIcon
    },
    data() {
        return {
            path: mdiAlertCircleOutline,
        }
    }
}
</script>
