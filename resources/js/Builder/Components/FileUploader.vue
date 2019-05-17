<script>
import {useDropzone} from 'vue3-dropzone'
import eventBus from '@/Builder/Utils/eventBus'
import BuilderMixin from '@/Builder/Mixins/builderMixin'

export default {
    name: "FileUploader",
    mixins: [BuilderMixin],
    data() {
        return {
            rootProps: null,
            inputProps: null,
            isDragActive: null,
        }
    },
    mounted() {
        const {
            getRootProps,
            getInputProps,
            isDragActive
        } = useDropzone({
            multiple: true,
            maxFiles: 5,
            accept: this.shopSettings.file_types.join(','),
            noClick: true,
            onDrop: this.onDrop
        })

        this.inputProps = getInputProps()
        this.rootProps = getRootProps()
        this.isDragActive = isDragActive
    },
    methods: {
        async onDrop(files) {
            files.forEach((file, index) => {
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
    }
}
</script>

<template>
    <div class="w-full h-full relative focus-visible:outline-0"
         :class="isDragActive ? `before:content-[''] before:absolute before:top-0 before:left-0 before:w-full before:h-full before:bg-black before:z-50 before:opacity-50` : ''"
         v-bind="rootProps">
        <input v-bind="inputProps" type="file" hidden style="width: 0!important;" multiple accept="image/*,.psd,application/pdf,application/postscript">
        <slot/>
    </div>
</template>
<style scoped>
input[type="file"]:focus {
    visibility: hidden !important;
}
</style>
