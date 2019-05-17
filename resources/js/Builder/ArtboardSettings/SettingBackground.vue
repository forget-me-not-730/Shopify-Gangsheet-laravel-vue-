<script>
import {defineComponent} from 'vue'
import {Sketch} from '@lk77/vue3-color'
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";

export default defineComponent({
    name: 'SettingBackground',
    mixins: [gangSheetSettingMixin],
    components: {Sketch},
    data() {
        return {
            backgroundColors: [
                '',
                'grey',
                '#000000',
            ],
            backgroundColor: '',
            openColorPicker: false,
            useCustomColor: false
        }
    },
    methods: {
        initializeSettings(canvas) {
            this.backgroundColor = canvas.backgroundColor
        },
        handleBackgroundColorClick(bgColor) {
            this.backgroundColor = bgColor
            _gangSheetCanvasEditor.setBackgroundColor(bgColor)
            _gangSheetCanvasEditor.renderAll()
        },
        handleUpdateBackgroundColor(color) {
            this.backgroundColor = color.hex8
            _gangSheetCanvasEditor.setBackgroundColor(color.hex8)
            _gangSheetCanvasEditor.renderAll()
        },
        handleCloseColorPicker() {
            this.openColorPicker = false
        },
        handleClickCustomColor() {
            this.openColorPicker = true
            this.useCustomColor = true
        }
    }
})
</script>

<template>
    <div class="grid grid-cols-3 justify-between">
        <div class="text-sm whitespace-nowrap max-sm:text-xs">{{ $t('Visual Aid') }}:</div>

        <div class="col-span-2 flex flex-col items-end">
            <div class="flex items-center space-x-1">
                <template v-for="bgColor in backgroundColors">
                    <div
                        class="h-6 w-6 border cursor-pointer rounded"
                        :class="{'transparent-pattern': !bgColor, 'ring-2': !useCustomColor && backgroundColor === bgColor}"
                        :style="{backgroundColor: bgColor}"
                        style="--cell-size: 8px;"
                        @click="handleBackgroundColorClick(bgColor)"
                    ></div>
                </template>
                <div @click="handleClickCustomColor" v-click-outside="handleCloseColorPicker" class="h-6 w-6 border cursor-pointer rounded relative" :class="{'ring-2': useCustomColor}"
                     :style="{backgroundColor}">
                </div>
            </div>
            <div class="mt-2 border w-full p-1 rounded-lg">
                <Sketch model-value="backgroundColor" class="custom-color-picker" @update:modelValue="handleUpdateBackgroundColor"/>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
