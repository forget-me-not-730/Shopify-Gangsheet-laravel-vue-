<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiLockOpenOutline, mdiLockOutline} from '@mdi/js'

export default defineComponent({
    name: "StickerSize",
    components: {SvgIcon},
    data() {

        return {
            rockAspectRatio: false,
            stickerSize: {
                width: 10,
                height: 10,
                unit: 'in'
            },

            mdiLockOpenOutline,
            mdiLockOutline
        }
    },
    methods: {
        handleAspectRatioChange() {
            this.rockAspectRatio = !this.rockAspectRatio
        },
        handleWidthChange(e) {
            let minWidth = 1
            let maxWidth = 100

            if (this.stickerSize.unit === 'px') {
                minWidth *= 300
                maxWidth *= 300
            } else if (this.stickerSize.unit === 'cm') {
                minWidth *= 25.4
                maxWidth *= 25.4
            } else if (this.stickerSize.unit === 'mm') {
                minWidth *= 254
                maxWidth *= 254
            }

            let width = parseFloat(e.target.value || '1')

            if (width < minWidth) {
                width = minWidth
            } else if (width > maxWidth) {
                width = maxWidth
            }

            if (this.rockAspectRatio) {
                this.stickerSize.height = this.stickerSize.height / this.stickerSize.width * width
                this.stickerSize.width = width
            } else {
                this.stickerSize.width = width
            }
        },
        handleHeightChange(e) {
            let minHeight = 1
            let maxHeight = 100

            if (this.stickerSize.unit === 'px') {
                minHeight *= 300
                maxHeight *= 300
            } else if (this.stickerSize.unit === 'cm') {
                minHeight *= 25.4
                maxHeight *= 25.4
            } else if (this.stickerSize.unit === 'mm') {
                minHeight *= 254
                maxHeight *= 254
            }

            let height = parseFloat(e.target.value || '1')

            if (height < minHeight) {
                height = minHeight
            } else if (height > maxHeight) {
                height = maxHeight
            }

            if (this.rockAspectRatio) {
                this.stickerSize.width = this.stickerSize.width / this.stickerSize.height * height
                this.stickerSize.height = height
            } else {
                this.stickerSize.height = height
            }
        }
    }
})
</script>

<template>
    <div class="flex items-center p-2 border-b">
        <div class="flex items-center space-x-1">
            <input :value="stickerSize.width" @focusout="handleWidthChange" @change="handleWidthChange" type="number" class="w-20 h-6 inp-builder"/>
            <div class="cursor-pointer" @click="handleAspectRatioChange">
                <svg-icon v-if="rockAspectRatio" type="mdi" :path="mdiLockOpenOutline" size="16"/>
                <svg-icon v-else type="mdi" :path="mdiLockOutline" size="16"/>
            </div>
            <input :value="stickerSize.height" @focusout="handleHeightChange" @change="handleHeightChange" type="number" class="w-20 h-6 inp-builder"/>

            <select v-model="stickerSize.unit" class="h-6 w-14 text-sm select-builder">
                <option>in</option>
                <option>cm</option>
                <option>mm</option>
            </select>
        </div>
    </div>
</template>

<style scoped>

</style>
