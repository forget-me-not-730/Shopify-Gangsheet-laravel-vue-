<script>
import { defineComponent } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import Spinner from '@/Components/Spinner.vue'
import { mdiClose } from '@mdi/js'
import { useForm } from '@inertiajs/vue3'
import GbsSelect from '@/Components/Select.vue'

export default defineComponent({
    name: 'AddMerchantFontModal',
    components: { GbsSelect, Spinner, SvgIcon },
    props: {
        fonts: {
            type: Array,
            required: true
        }
    },
    data () {
        return {
            form: useForm({
                fonts: [],
                files: [],
                type: 'general'
            }),

            selectedFont: null,

            mdiClose
        }
    },
    methods: {
        handleAddClick () {
            if (!this.form.fonts.includes(this.selectedFont.name)) {
                this.form.fonts.push(this.selectedFont.name)
            }

            this.selectedFont = null
        },
        handleFileChange (e) {
            for (const file of e.target.files) {
                if (this.form.files.find(f => f.name === file.name)) {
                    return
                }

                this.form.files.push(file)
            }
        },
        handleSubmit () {
            this.form.post(route('merchant.font.store'), {
                onSuccess: () => {
                    window.Toast.success({
                        message: 'Fonts uploaded successfully'
                    })

                    this.form.files = []
                    this.form.fonts = []
                    this.$emit('close')
                },
                onError: () => {
                    window.Toast.error({
                        message: 'Error uploading fonts'
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
                    <label class="bg-green-500 text-white px-4 py-2 rounded">
                        Upload Font File(s)
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

                    <div class="mt-5 mb-2 text-gray-400">- or choose from -</div>

                    <div class="flex items-center">
                        <div class="w-96">
                            <gbs-select v-model="selectedFont" :options="fonts" search="name">
                                <template #selected="{selected}">
                                    <span :style="{fontFamily: selected?.name}">{{ selected?.name || 'Search Font' }}</span>
                                </template>
                                <template #option="{option}">
                                    <span :style="{fontFamily: option?.name}">{{ option.name }}</span>
                                </template>
                            </gbs-select>

                        </div>
                        <button v-if="selectedFont" type="button" class="bg-green-400 text-white px-4 rounded-full py-1.5 ml-4" @click="handleAddClick">Add</button>
                    </div>

                    <div class="mt-3">
                        <div class="flex flex-col">
                            <div v-for="(fontName, index) in form.fonts" class="flex items-center p-2" :key="fontName" :style="{fontFamily: fontName}">
                                {{ fontName }}

                                <div class="flex items-center text-red-500 ml-10 cursor-pointer" @click="form.fonts.splice(index, 1)">
                                    <svg-icon type="mdi" :path="mdiClose" size="18"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full py-4">
                        <button class="btn-danger" @click="$emit('close')">
                            Cancel
                        </button>
                        <button class="btn-primary relative justify-center ml-4" :disabled="form.processing" @click="handleSubmit">
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
