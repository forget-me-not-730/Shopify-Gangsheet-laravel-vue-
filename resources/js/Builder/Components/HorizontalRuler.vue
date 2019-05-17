<template>
    <div class="w-full flex h-full bg-green-50 border-b items-center justify-center">
        <div
            class="border-r h-full flex after:content-[var(--width)] after:flex after:items-center after:h-full after:absolute relative after:left-full after:w-max after:pl-1 after:text-inherit"
            :style="rulerStyle"
            :class="{'before:content-[\'\'] before:h-[100vw] before:w-px before:bg-primary before:absolute before:opacity-30 before:right-[-1px] before:top-full': gridMode}"
        >
            <template v-for="coordinate in coordinates" :key="coordinate">
                <div
                    class="border-l h-full flex items-end justify-evenly pl-px text-inherit relative"
                    :style="{ width: deltaX + 'in' }"
                    :class="{'after:content-[\'\'] after:h-[100vw] after:w-px after:bg-primary after:opacity-30 after:absolute after:left-[-1px] after:top-full': gridMode}"
                >
                    <div class="absolute bottom-px left-px">{{ coordinate }}</div>
                    <div class="h-1.5 w-px bg-gray-400" v-if="deltaX > this.deltaOptions[0]"></div>
                    <div class="h-1.5 w-px bg-gray-400" v-if="deltaX > this.deltaOptions[3]"></div>
                    <div class="h-1.5 w-px bg-gray-400" v-if="deltaX > this.deltaOptions[6]"></div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: "HorizontalRuler",
    props: {
        width: {
            type: Number,
            required: true
        },
        zoom: {
            type: Number,
            required: true
        },
        translateX: {
            type: Number,
            required: true
        },
        gridMode: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            deltaX: 1,
            widthOptions: [80, 120, 150, 200, 250, 300, 400],
            deltaOptions: [1, 2, 4, 5, 10, 15, 20, 25]
        }
    },
    computed: {
        rulerStyle() {
            return {
                width: this.width * this.zoom + 'in',
                '--width': `"${this.width} inch"`,
                fontSize: '11px',
                lineHeight: '10px',
                transform: `translate(${this.translateX}px)`
            }
        },
        coordinates() {
            this.deltaX = this.getDeltaX(this.width / this.zoom)
            const coords = [];
            let cx = 0;

            while (cx < this.width) {
                coords.push(cx)
                cx += this.deltaX;
            }

            return coords
        }
    },
    methods: {
        getDeltaX(width) {
            for (const index in this.widthOptions) {
                if (width < this.widthOptions[index]) {
                    return this.deltaOptions[index]
                }
            }
            return this.deltaOptions[this.deltaOptions.length - 1];
        }
    }
}
</script>

<style scoped>

</style>
