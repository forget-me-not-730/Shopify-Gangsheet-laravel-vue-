<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "ImageView",
    props: {
        modelValue: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            uploading: false,
            reading: false,
            progress: 30
        }
    },
    computed: {
        src: {
            get() {
                return this.modelValue
            },
            set(value) {
                this.$emit('update:modelValue', value)
                this.$emit('change', value)
            }
        }
    },
    methods: {
        handleFileChange(event) {
            const file = event.target.files[0]
            if (file) {
                this.uploading = true
                this.reading = false

                const reader = new FileReader()
                reader.onload = () => {
                    this.src = reader.result
                }
                reader.readAsDataURL(file);

                const formData = new FormData()

                formData.append('image', file)

                axios.post(route('merchant.upload-image'), formData, {
                    header: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: (e) => {
                        const progress = Math.round(e.loaded / e.total * 100)

                        if (progress > 0) {
                            this.progress = progress - 5;
                            this.reading = false
                        }

                        if (progress === 100) {
                            setTimeout(() => {
                                this.progress = 100
                            }, 500)

                            setTimeout(() => {
                                this.progress = 30
                                this.reading = true
                            }, 1000)
                        }
                    }
                }).then((res) => {
                    if (res.data.success) {
                        this.src = res.data.url
                    } else {
                        window.Toast.error({
                            message: 'Unable to upload your image.'
                        })
                    }
                }).finally(() => {
                    this.uploading = false
                    this.reading = false
                })
            }
            event.target.value = null
        }
    }
})
</script>

<template>
    <label class="flex w-full h-full relative">
        <img v-if="src" :src="src" class="w-full h-full object-contain" alt=""/>
        <div class="absolute w-full h-full inset-0 flex items-center justify-center hover:text-blue-600" :class="{'text-transparent': src}">
            <div v-if="uploading" class="flex items-center justify-center w-full h-full bg-gray-900 bg-opacity-40">
                <svg width="40" height="40" viewBox="0 0 36 36" :class="{'gs-spinner': reading}">
                    <path fill="none" stroke="#ffffff34" stroke-width="2" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"
                          :style="{'--progress': progress}"
                          style="stroke-dasharray: var(--progress, 30) 100; animation: progress 1s ease-out forwards;"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <text v-if="!reading" x="19" y="21" fill="#fff" font-size="0.6em" text-anchor="middle">
                        {{ progress }}%
                    </text>
                </svg>
            </div>
            <div v-else class="cursor-pointer">
                <input type="file" hidden class="w-0 h-0" accept="image/*" @change="handleFileChange">
                <div class="underline">
                    <span v-if="src">Change Image</span>
                    <span v-else>Upload Image</span>
                </div>
            </div>
        </div>
    </label>
</template>

<style scoped>
@keyframes spinner {
    to {
        transform: rotate(360deg);
    }
}

@keyframes progress {
    0% {
        stroke-dasharray: 0 100;
    }
}

.gs-spinner {
    animation: spinner .6s linear infinite;
}
</style>
