<template>
    <div class="w-full h-full bg-green-50 border-r flex items-center">
        <div
            class="border-b w-full flex flex-col after:content-[var(--height)] after:flex after:items-center after:w-full after:absolute relative after:top-full after:h-max after:pt-1 after:text-inherit after:vertical-lr"
            :style="rulerStyle"
            :class="{'before:content-[\'\'] before:w-[100vw] before:h-px before:bg-primary before:absolute before:opacity-30 before:bottom-[-1px] before:left-full': gridMode}"
        >
            <template v-for="coordinate in coordinates" :key="coordinate">
                <div
                    class="border-t w-full flex flex-col justify-evenly  pt-px text-inherit relative"
                    :style="{ height: deltaY + 'in' }"
                    :class="{'after:content-[\'\'] after:w-[100vw] after:h-px after:bg-primary after:opacity-30 after:absolute after:top-[-1px] after:left-full': gridMode}"
                >
                    <div class="absolute top-1 left-px vertical-lr">{{ coordinate }}</div>
                    <div class="w-1.5 h-px bg-gray-400" v-if="deltaY > this.deltaOptions[0]"></div>
                    <div class="w-1.5 h-px bg-gray-400" v-if="deltaY > this.deltaOptions[3]"></div>
                    <div class="w-1.5 h-px bg-gray-400" v-if="deltaY > this.deltaOptions[6]"></div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: "VerticalRuler",
    props: {
        height: {
            type: Number,
            required: true
        },
        zoom: {
            type: Number,
            required: true
        },
        translateY: {
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
            deltaY: 1,
            heightOptions: [80, 120, 150, 200, 250, 300, 400],
            deltaOptions: [1, 2, 4, 5, 10, 15, 20, 25]
        }
    },
    computed: {
        rulerStyle() {
            return {
                height: this.height * this.zoom + 'in',
                '--height': `"${this.height} inch"`,
                fontSize: '11px',
                lineHeight: '10px',
                transform: `translate(0px, ${this.translateY}px)`
            }
        },
        coordinates() {
            this.deltaY = this.getDeltaY(this.height / this.zoom)
            const coords = [];
            let cx = 0;

            while (cx < this.height) {
                coords.push(cx);
                cx += this.deltaY;
            }

            return coords
        }
    },
    methods: {
        getDeltaY(height) {
            for (const index in this.heightOptions) {
                if (height < this.heightOptions[index]) {
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
