<script>
import { defineComponent } from 'vue'
import { mdiClose, mdiContentCopy, mdiCheck } from '@mdi/js'
import SvgIcon from '@jamescoyle/vue-icon'
import Spinner from '@/Components/Spinner.vue'

export default defineComponent({
    name: 'CreateApiTokenModal',
    components: { Spinner, SvgIcon },
    data () {
        return {
            token_name: '',
            apiTokenCreated: '',
            loading: false,
            isTokenCopied: false,

            mdiClose,
            mdiContentCopy,
            mdiCheck
        }
    },
    methods: {
        createToken () {
            this.loading = true
            axios.post(route('merchant.create-token'), {
                token_name: this.token_name
            }).then(response => {
                if (response.data.success) {
                    this.apiTokenCreated = response.data.token
                } else {
                    window.Toast.error({
                        message: response.data.error
                    })
                }
            }).catch(error => {
                console.log(error.response.data)
            }).finally(() => {
                this.loading = false
            })
        },
        handleCopyToken () {
            navigator.clipboard.writeText(this.apiTokenCreated).then(() => {
                console.log('Copied')
                this.isTokenCopied = true
                setTimeout(() => {
                    this.isTokenCopied = false
                }, 800)
            })
        }
    }
})
</script>

<template>
    <div>
        <div class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-5 w-full max-w-md">
                <div class="flex justify-between">
                    <h4 class="text-lg font-bold">Create API Token</h4>

                    <div v-if="!apiTokenCreated">
                        <svg-icon type="mdi" :path="mdiClose" class="w-6 h-6 cursor-pointer" @click="$emit('close')"/>
                    </div>
                </div>

                <template v-if="apiTokenCreated">
                    <div class="mt-4 flex items-center justify-center flex-col space-y-4">

                        <p class="text-green-500">A new API token has been successfully created.</p>

                        <div class="inp-default flex justify-between items-center">
                            {{ apiTokenCreated }}

                            <button
                                @click="handleCopyToken"
                                class="w-5 h-5 flex items-center justify-center relative"
                            >
                                <svg-icon v-if="isTokenCopied" type="mdi" :path="mdiCheck" size="16"/>
                                <svg-icon v-else type="mdi" :path="mdiContentCopy" size="16"/>
                            </button>
                        </div>

                        <p class="text-sm text-red-500">Be sure to copy your API token to a safe location as you will not be able to view it again.</p>

                        <button class="btn btn-primary" @click="$emit('success')">Close</button>
                    </div>
                </template>
                <template v-else>
                    <div class="mt-4">
                        <label class="block">Token Name</label>
                        <input v-model="token_name" type="text" class="rounded border-gray-300 focus:border-primary focus:ring-primary shadow-sm px-4 mt-1 w-full"/>
                    </div>
                    <div class="mt-4">
                        <button class="btn btn-primary w-24 justify-center" :disabled="!token_name || loading" @click="createToken">
                            <spinner v-if="loading"/>
                            <span v-else>Create</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
