<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiNavigation} from '@mdi/js';

export default {
    name: "NavigationIcon",
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
            path: mdiNavigation,
        }
    }
}
</script>
