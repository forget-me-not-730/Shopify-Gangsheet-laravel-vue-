<template>
    <svg-icon type="mdi" :size="size" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiRedo} from '@mdi/js';

export default {
    name: "RedoIcon",
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
            path: mdiRedo,
        }
    }
}
</script>
