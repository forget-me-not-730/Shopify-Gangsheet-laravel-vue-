<script>
import {defineComponent, handleError} from 'vue'
import Modal from "@/Components/Modals/Modal.vue";
import Spinner from "@/Components/Spinner.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default defineComponent({
    name: "ViewDesignLogModal",
    components: {CloseIcon, Spinner, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        },
        design_id: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            log_file_path: '',
            log_content: '',
            loading: true,
            clearing: false,
            processing: false
        }
    },
    watch: {
        open: {
            handler() {
                this.log_file_path = ''
                this.log_content = ''
                this.loading = true
                this.clearing = false
                this.getLog()
            }
        },
    },
    methods: {
        getLog() {
            if (this.open && this.design_id) {
                axios.post(route('admin.design.get-log'), {
                    design_id: this.design_id,
                }).then((res) => {
                    if (res.data.success) {
                        this.loading = false
                        this.log_file_path = res.data.log_file_path
                        this.log_content = res.data.log_content
                        this.processing = res.data.status === 'processing'
                        if (this.processing) {
                            setTimeout(() => {
                                this.getLog()
                            }, 3000)
                        }
                    }
                })
            }
        },
        handleOpenLogFile() {
            window.open(this.log_file_path + '?t=' + new Date().getTime(), '_blank')
        },
        handleClearLog() {
            this.clearing = true
            axios.post(route('admin.design.clear-log'), {
                design_id: this.design_id,
            }).then((res) => {
                if (res.data.success) {
                    window.Toast.success({
                        message: 'Log cleared successfully'
                    })
                    this.clearing = false
                    this.$emit('close')
                }
            })
        }
    }
})
</script>

<template>
    <Modal :open="open" @close="$emit('close')" width="6xl">
        <div class="w-full bg-white rounded-lg h-full text-left flex flex-col">
            <div class="w-full flex items-center justify-between p-4">
                <div></div>
                <div @click="$emit('close')" class="cursor-pointer">
                    <close-icon/>
                </div>
            </div>
            <div class="px-4 flex-1 h-px">
                <div class="relative h-full w-full">
                    <div v-if="loading" class="absolute inset-0 w-full flex items-center justify-center">
                        <spinner/>
                    </div>
                    <div v-else class="w-full h-full text-white bg-gray-900 overflow-y-auto default-scrollbar">
                        <spinner v-if="processing" class="ml-1 my-1"/>
                        <pre v-text="log_content" class="w-full text-xs"/>
                    </div>
                </div>
            </div>
            <div class="px-4 py-2 flex justify-end space-x-4">
                <button v-show="!loading && log_content" @click="handleOpenLogFile" class="btn btn-primary">
                    View File
                </button>
                <button :disabled="clearing" @click="handleClearLog" class="btn btn-primary">
                    <spinner v-if="clearing" class="mr-1"/>
                    Clear
                </button>
            </div>
        </div>
    </Modal>
</template>

<style scoped>

</style>
