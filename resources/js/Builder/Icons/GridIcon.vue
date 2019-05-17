<template>
    <svg-icon type="mdi" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiGrid } from '@mdi/js';

export default {
    name: "GridIcon",
    components: {
        SvgIcon
    },
    props: {
        size: {
            type: [Number, String],
            default: 18
        }
    },
    data() {
        return {
            path: mdiGrid,
        }
    }
}
</script>
