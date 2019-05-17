<template>
    <div class="btn-builder w-4 h-4 rounded-none p-0 absolute top-0 left-0 flex justify-center items-center z-40 pointer-events-none"></div>

    <div class="absolute bg-builder top-0 left-0 h-4 right-0 z-20 pointer-events-none">
        <div class="w-full flex h-full border-b items-center justify-center relative">
            <div v-if="gridMode" class=" absolute left-0 h-full flex flex-row-reverse"
                 :style="{width: `calc((100% - ${this.width * getPixelSize() * this.zoom}px) / 2 + ${this.translateX}px)`}">
                <template v-for="(_, index) in Array(50)" :key="index">
                    <div class="shrink-0 border-l gs-border-primary top-full h-[100vh] opacity-30 absolute pointer-events-none"
                         :style="{width: delta * zoom * getPixelSize() + 'px', right: delta * zoom * index * getPixelSize() + 'px'}"></div>
                </template>
            </div>
            <div
                class="border-r h-full shrink-0 flex after:content-[var(--width)] after:text-3xs after:bg-builder after:absolute relative after:left-[calc(100%+2px)] after:w-full after:pl-1 after:text-inherit"
                :style="rulerHStyle"
                :class="{'before:content-[\'\'] before:h-[100vw] before:w-px before:gs-bg-primary before:absolute before:opacity-30 before:right-[-1px] before:top-full': gridMode}"
            >
                <template v-for="coordinate in coordinatesX" :key="coordinate">
                    <div
                        class="border-l h-full flex flex-shrink-0 items-end justify-evenly pl-px text-inherit relative"
                        :style="{ width: delta * zoom * getPixelSize() - 0.5 + 'px' }"
                        :class="{'after:content-[\'\'] after:h-[100vh] after:w-px after:gs-bg-primary after:opacity-30 after:absolute after:left-[-1.5px] after:top-full': gridMode}"
                    >
                        <template v-if="delta * zoom * getPixelSize() > 100">
                            <template v-for="index in 9" :key="index">
                                <div class="h-1.5 w-px gs-bg-primary"></div>
                                <div v-if="delta * zoom * getPixelSize() > 200" class="h-0.5 w-px gs-bg-primary"></div>
                            </template>
                        </template>
                        <template v-else>
                            <div class="h-1.5 w-px gs-bg-primary" v-if="delta > this.deltaOptions[0]"></div>
                            <div class="h-1.5 w-px gs-bg-primary" v-if="delta > this.deltaOptions[3]"></div>
                            <div class="h-1.5 w-px gs-bg-primary" v-if="delta > this.deltaOptions[6]"></div>
                        </template>
                        <small class="absolute top-px left-px bg-builder z-50">{{ coordinate }}</small>
                    </div>
                </template>
            </div>
            <div v-if="gridMode" class="absolute right-0 h-full flex "
                 :style="{width: `calc((100% - ${this.width * getPixelSize(this.unit) * this.zoom}px) / 2 - ${this.translateX}px)`}">
                <template v-for="(_, index) in Array(50)" :key="index">
                    <div class="shrink-0 border-r gs-border-primary top-full h-[100vh] opacity-30 absolute"
                         :style="{width: delta * zoom * getPixelSize() + 'px', left: delta * zoom * index * getPixelSize() + 'px'}"></div>
                </template>
            </div>
        </div>
    </div>

    <div class="absolute bg-builder top-0 left-0 w-4 bottom-0 z-20 pointer-events-none">
        <div class="w-full h-full border-r flex items-center relative">
            <div v-if="gridMode" class="absolute top-0 w-full flex flex-col"
                 :style="{height: `calc((100% - ${this.height * getPixelSize() * this.zoom}px) / 2 + ${this.translateY}px)`}">
                <template v-for="(_, index) in Array(50)" :key="index">
                    <div class="shrink-0 border-t gs-border-primary left-full w-[100vw] opacity-30 absolute pointer-events-none"
                         :style="{height: delta * zoom * getPixelSize() + 'px', bottom: delta * zoom * index * getPixelSize() + 'px'}"></div>
                </template>
            </div>
            <div
                class="border-b w-full flex shrink-0 flex-col after:content-[var(--height)] after:text-3xs after:w-full after:absolute relative after:top-[calc(100%+2px)] after:h-full after:pt-1 after:text-inherit after:vertical-lr"
                :style="rulerVStyle"
                :class="{'before:content-[\'\'] before:w-[100vw] before:h-px before:gs-bg-primary before:absolute before:opacity-30 before:bottom-[-1px] before:left-full': gridMode}"
            >
                <template v-for="coordinate in coordinatesY" :key="coordinate">
                    <div
                        class="border-t w-full flex flex-shrink-0 flex-col justify-evenly items-end pt-px text-inherit relative"
                        :style="{ height: delta * zoom * getPixelSize() - 0.5 + 'px' }"
                        :class="{'after:content-[\'\'] after:w-[100vw] after:h-px after:gs-bg-primary after:opacity-30 after:absolute after:top-[-1px] after:left-full': gridMode}"
                    >
                        <template v-if="delta * zoom * getPixelSize() > 100">
                            <template v-for="index in 9" :key="index">
                                <div class="w-1.5 h-px gs-bg-primary"></div>
                                <div v-if="delta * zoom * getPixelSize() > 400" class="w-0.5 h-px gs-bg-primary         "></div>
                            </template>
                        </template>
                        <template v-else>
                            <div class="w-1.5 h-px gs-bg-primary" v-if="delta > this.deltaOptions[0]"></div>
                            <div class="w-1.5 h-px gs-bg-primary" v-if="delta > this.deltaOptions[3]"></div>
                            <div class="w-1.5 h-px gs-bg-primary" v-if="delta > this.deltaOptions[6]"></div>
                        </template>
                        <small class="absolute top-1 bg-builder left-px vertical-lr">{{ coordinate }}</small>
                    </div>
                </template>
            </div>
            <div v-if="gridMode" class="absolute bottom-0 w-full flex flex-col"
                 :style="{height: `calc((100% - ${this.height * getPixelSize() * this.zoom}px) / 2 - ${this.translateY}px)`}">
                <template v-for="(_, index) in Array(50)" :key="index">
                    <div class="shrink-0 border-b gs-border-primary  left-full w-[100vw] opacity-30 absolute"
                         :style="{height: delta * zoom * getPixelSize() + 'px', top: delta * zoom * index * getPixelSize() + 'px'}"></div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import {getPixelSize} from "@/Builder/Utils/helpers";

export default {
    name: "Ruler",
    props: {
        height: {
            type: [Number, String],
            required: true
        },
        width: {
            type: [Number, String],
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
        translateY: {
            type: Number,
            required: true
        },
        gridMode: {
            type: Boolean,
            default: false
        },
        unit: {
            type: String,
            default: 'inch'
        }
    },
    data() {
        return {
            delta: 1,
            deltaOptions: [1, 2, 4, 5, 10, 15, 20, 25, 50, 100, 200, 400, 500, 1000, 1500, 2000, 2500, 5000, 10000, 15000, 20000, 25000, 50000, 100000],
        }
    },
    computed: {
        rulerHStyle() {
            return {
                width: this.width * this.zoom * this.getPixelSize() + 2 + 'px',
                '--width': `"${this.width} ${this.unit}"`,
                fontSize: '11px',
                lineHeight: '10px',
                transform: `translate(${this.translateX}px)`
            }
        },
        rulerVStyle() {
            return {
                height: this.height * this.zoom * this.getPixelSize() + 2   + 'px',
                '--height': `"${this.height} ${this.unit}"`,
                fontSize: '11px',
                lineHeight: '10px',
                transform: `translate(0px , ${this.translateY}px)`
            }
        },
        coordinatesX() {
            this.delta = this.getDelta()
            const coords = [];
            let cx = 0;

            while (cx < this.width) {
                coords.push(cx);
                cx += this.delta;
            }

            if (window._gangSheetCanvasEditor && this.gridMode) {
                window._gangSheetCanvasEditor.setCoordinatesX(coords)
            }

            return coords
        },
        coordinatesY() {
            this.delta = this.getDelta()
            const coords = [];
            let cx = 0;

            while (cx < this.height) {
                coords.push(cx);
                cx += this.delta;
            }

            if (window._gangSheetCanvasEditor && this.gridMode) {
                window._gangSheetCanvasEditor.setCoordinatesY(coords)
            }

            return coords
        }
    },
    methods: {
        getPixelSize() {
            return getPixelSize(this.unit)
        },
        getDelta() {
            let length = 0
            if (this.width >= this.height) {
                length = this.width
            } else {
                length = this.height
            }

            const lengthPx = length * this.zoom * this.getPixelSize()
            let count = Math.round(lengthPx / 100)

            const delta = length / count;
            const index = this.deltaOptions.findIndex(item => item > delta)
            return this.deltaOptions[Math.min(this.deltaOptions.length - 1, index)];
        },
    }
}
</script>

<style lang="scss" scoped>
</style>
