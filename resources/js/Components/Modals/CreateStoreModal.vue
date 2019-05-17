<script>
import { defineComponent } from 'vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiClose } from '@mdi/js'
import { useForm } from '@inertiajs/vue3'
import Spinner from '@/Components/Spinner.vue'

export default defineComponent({
    name: 'CreateStoreModal',
    components: { Spinner, SvgIcon },
    data () {
        return {
            form: useForm({
                name: '',
                email: ''

            }),
            loading: false,
            mdiClose
        }
    },
    methods: {
        createStore () {
            if (!this.form.name || !this.form.email) {
                return window.Toast.error({
                    message: 'Please fill in name and email fields'
                })
            }

            this.loading = true

            this.form.post(route('admin.merchant.create'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.loading = false
                    this.$emit('success')
                },
                onError: (err) => {
                    console.log(err)
                    window.Toast.error({
                        message: err.response.data.message
                    })
                    this.loading = false
                }
            })
        }
    }
})
</script>

<template>
    <div class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-5 w-full max-w-md">
            <div class="flex justify-between">
                <h4 class="text-lg font-bold">Create a new store</h4>

                <div>
                    <svg-icon type="mdi" :path="mdiClose" class="w-6 h-6 cursor-pointer" @click="$emit('close')"/>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <div>
                    <label class="block">Store Name</label>
                    <input v-model="form.name" type="text" class="inp-default w-full mt-1"/>
                </div>
                <div>
                    <label class="block">Email</label>
                    <input type="email" v-model="form.email" class="inp-default w-full mt-1"/>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary w-24 justify-center" :disabled="loading" @click="createStore">
                    <spinner v-if="loading"/>
                    <span v-else>Create</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
