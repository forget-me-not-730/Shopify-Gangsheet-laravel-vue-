<script>
import { defineComponent } from 'vue'
import { getLoginMagicLink } from '@/Woo/Apis/gsbApi'
import Spinner from '@/Components/Spinner.vue'

export default defineComponent({
    name: 'LoginButton',
    components: { Spinner },
    props: {
        page_url: {
            type: String,
            default: '/merchant/dashboard'
        },
        title: {
            type: String,
            default: 'Log in'
        }
    },
    data () {
        return {
            loading: false
        }
    },
    methods: {
        handleLogin () {
            this.loading = true
            getLoginMagicLink({ page_url: this.page_url }).then(res => {
                if (res.data.success) {
                    window.open(res.data.url, '_blank')
                } else {
                    console.error(res.data.error)
                }
            }).catch((err) => {
                console.error(err)
            }).finally(() => {
                this.loading = false
            })
        }
    }
})
</script>

<template>
    <button class="btn-primary" :disabled="loading" @click="handleLogin">
        <Spinner class="mr-2" v-if="loading"/>
        {{ title }}
    </button>
</template>

<style scoped>

</style>
