<script setup>
import {reactive} from 'vue'
import {useDropzone} from 'vue3-dropzone'
import eventBus from '@/Builder/Utils/eventBus'

function onDrop(acceptedFiles) {
    acceptedFiles.forEach((file, index) => {
        const size = file.size / 1024 / 1024
        if (size > 100) {
            window.Toast.error({
                message: `${file.name} is too big, should be less than 100 MB.`
            })
            return
        }

        const image = {
            id: new Date().getTime() + index,
            file: file
        }

        eventBus.$emit(eventBus.IMAGE_UPLOADED, image)
    })
}

const options = reactive({
    multiple: true,
    maxFiles: 5,
    accept: 'image/*,.psd,application/pdf,application/postscript',
    noClick: true,
    onDrop,
})

const {
    getRootProps,
    getInputProps,
    isDragActive
} = useDropzone(options)

</script>

<template>
    <div class="w-full h-full relative focus-visible:outline-0"
         :class="isDragActive ? `before:content-[''] before:absolute before:top-0 before:left-0 before:w-full before:h-full before:bg-black before:z-50 before:opacity-50` : ''"
         v-bind="getRootProps()">
        <input v-bind="getInputProps()" type="file" hidden style="width: 0!important;" multiple accept="image/*,.psd,application/pdf,application/postscript">
        <slot/>
    </div>
</template>
<style scoped>
input[type="file"]:focus {
    visibility: hidden !important;
}
</style>
