<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "Avatar",
    props: {
        name: {
            type: String,
            default: 'Unknown'
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
        },
        generateHSL(name) {
            const hRange = [0, 360];
            const sRange = [0, 100];
            const lRange = [0, 100];

            const normalizeHash = (hash, min, max) => {
                return Math.floor((hash % (max - min)) + min);
            };

            const getHashOfString = (str) => {
                let hash = 0;
                for (let i = 0; i < str.length; i++) {
                    hash = str.charCodeAt(i) + ((hash << 3) - hash);
                }
                return Math.abs(hash);
            }

            const HSLtoString = (hsl) => {
                return `hsl(${hsl[0]}, ${hsl[1]}%, ${hsl[2]}%)`;
            };

            const hash = getHashOfString(name);
            const h = normalizeHash(hash, hRange[0], hRange[1]);
            const s = normalizeHash(hash, sRange[0], sRange[1]);
            const l = normalizeHash(hash, lRange[0], lRange[1]);

            return HSLtoString([h, s, l]);
        },
    }
})
</script>

<template>
    <div class="flex justify-center items-center rounded-full h-10 w-10 text-white font-bold" :style="{backgroundColor: generateHSL(name)}">
        <span>{{ getInitials(name) }}</span>
    </div>
</template>

<style scoped>

</style>
