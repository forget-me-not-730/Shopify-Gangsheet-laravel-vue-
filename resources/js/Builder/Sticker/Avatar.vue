<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "Avatar",
    props: {
        name: {
            type: String,
            default: ''
        }
    },
    methods: {
        getInitials() {
            if (this.name) {
                let chr;
                const d = this.name.toUpperCase();
                chr = d.split(" ");
                if (chr.length >= 2) {
                    return chr[0][0] + chr[1][0];
                } else {
                    return d[0] + d[1];
                }
            }
            return "UN";
        }
    }
})
</script>

<template>
    <div class="flex justify-center items-center bg-gray-100 rounded-full h-10 w-10 font-bold">
        <span>{{ getInitials(name) }}</span>
    </div>
</template>

<style scoped>

</style>
