<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiCogOutline} from '@mdi/js';

export default {
    name: "CogOutlineIcon",
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
            path: mdiCogOutline,
        }
    }
}
</script>
