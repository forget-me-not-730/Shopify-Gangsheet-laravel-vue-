<template>
    <div class="flex flex-col space-y-1">
      <sketch-picker v-model="currentColor" />
      <button @click="addColor" class="bg-blue-500 hover:bg-blue-700 text-white p-2 rounded-lg">Add Color</button>
    </div>
</template>

<script>
import { Sketch } from "@lk77/vue3-color";

export default {
    components: {
        SketchPicker: Sketch,
    },
    data() {
        return {
        currentColor: "#ffffff", // Initial color
        };
    },
    methods: {
        addColor() {
        this.$emit("colorAdded", this.currentColor);
        },
    },
};
</script>