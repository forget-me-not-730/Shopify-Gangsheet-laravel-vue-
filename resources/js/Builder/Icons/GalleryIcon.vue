<template>
    <svg-icon type="mdi" :path="path"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiImageMultipleOutline } from '@mdi/js';

export default {
    name: "GalleryIcon",
    components: {
        SvgIcon
    },
    data() {
        return {
            path: mdiImageMultipleOutline,
        }
    }
}
</script>
