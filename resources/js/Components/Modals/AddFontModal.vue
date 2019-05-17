<script>
import {defineComponent} from 'vue'
import TextInput from "@/Components/TextInput.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import Spinner from "@/Components/Spinner.vue";
import {mdiClose} from '@mdi/js'
import {useForm} from "@inertiajs/vue3";

export default defineComponent({
    name: "AddFontModal",
    components: {Spinner, SvgIcon, TextInput},
    data() {
        return {
            form: useForm({
                files: [],
                type: 'general'
            }),

            mdiClose
        }
    },
    methods: {
        handleFileChange(e) {
            for (const file of e.target.files) {
                if (this.form.files.find(f => f.name === file.name)) {
                    return
                }

                this.form.files.push(file)
            }
        },
        handleSubmit() {
            this.form.post(route('admin.font.store'), {
                onSuccess: () => {

                    this.form.files = [];

                    window.Toast.success({
                        message: 'Fonts uploaded successfully'
                    })

                    this.$emit('close')
                },
                onError: (error) => {
                    window.Toast.error({
                        message: error.error
                    })
                }
            })
        }
    }
})
</script>

<template>
    <div class="pointer-events-none fixed top-[57px] bottom-0 right-0 z-20 flex max-w-full pl-10">
        <div class="pointer-events-auto w-screen max-w-2xl">
            <div class="flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl">
                <div class="px-4 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 class="text-base font-semibold text-gray-900" id="slide-over-title">Add Fonts</h2>
                        <div class="ml-3 flex h-7 items-center cursor-pointer" @click="$emit('close')">
                            <svg-icon type="mdi" :path="mdiClose"/>
                        </div>
                    </div>
                </div>
                <div class="relative mt-6 border-t border-gray-200 p-5">
                    <div class="flex justify-start items-center gap-2 mb-5 w-full p-5">
                        <span>Type: </span>
                        <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" v-model="form.type">
                            <option value="general">General</option>
                            <option value="name_and_number">Name and Number</option>
                        </select>
                    </div>

                    <label class="bg-green-500 text-white px-4 py-2 rounded">
                        Upload Fonts
                        <input type="file" hidden multiple @change="handleFileChange" accept=".ttf,.otf,.woff,.woff2"/>
                    </label>

                    <div class="flex flex-col mt-5">
                        <div v-for="(file, index) in form.files" class="flex items-center p-2" :key="file.name">
                            {{ file.name }}

                            <div class="flex items-center text-red-500 ml-10 cursor-pointer" @click="form.files.splice(index, 1)">
                                <svg-icon type="mdi" :path="mdiClose" size="18"/>
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full py-4">
                        <button class="btn-danger" @click="$emit('close')">
                            Cancel
                        </button>
                        <button class="btn-primary relative justify-center ml-4" :disabled="form.files.length === 0 || form.processing" @click="handleSubmit">
                            <spinner v-if="form.processing" class="absolute"/>
                            <span :class="{'invisible': form.processing}">Submit</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
