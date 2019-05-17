<template>
    <svg-icon type="mdi" :path="path" :size="size"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiInvoiceTextPlusOutline} from '@mdi/js';

export default {
    name: "InvoiceTextPlusOutlineIcon",
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
            path: mdiInvoiceTextPlusOutline,
        }
    }
}
</script>
