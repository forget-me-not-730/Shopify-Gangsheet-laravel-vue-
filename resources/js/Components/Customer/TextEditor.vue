<script>
import {defineComponent} from 'vue'
import {Sketch} from '@lk77/vue3-color'

export default defineComponent({
    name: "TextEditor",
    components: {
        Sketch
    },
    data() {
        return {
            color: '#555555'
        }
    },
    methods: {
        updateColor(color) {
            this.color = color.hex6
        }
    }
})
</script>

<template>
    <div class="v">
        Text Editor
        <div class="w-full flex items-center justify-center mt-2">
            <sketch :value="color" @input="updateColor" :disable-alpha="true"/>
        </div>
    </div>
</template>

<style>
    .vc-sketch {
        width: 300px;
        box-shadow: none!important;
        border: solid 1px #00000012;
    }
</style>
