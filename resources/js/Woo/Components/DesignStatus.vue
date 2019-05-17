<script>
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'DesignStatus',
    props: {
        design: {
            type: Object,
            required: true
        },
        isAdmin: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            status: null
        }
    },
    watch: {
        design: {
            immediate: true,
            deep: true,
            handler () {
                if (this.design) {
                    this.status = this.design.status
                    if (this.status !== 'completed') {
                        setTimeout(() => {
                            this.fetDesignStatus()
                        }, 20000)
                    }
                }
            }
        }
    },
    methods: {
        fetDesignStatus () {
            const routeName = this.isAdmin ? 'admin.order.design-status' : 'shopify.order.design-status'
            axios.get(route(routeName, { design: this.design.id })).then(res => {
                if (res.data.success) {
                    this.status = res.data.status
                    this.design.status = res.data.status
                    if (this.status.toLowerCase() !== 'completed') {
                        setTimeout(() => {
                            this.fetDesignStatus()
                        }, 20000)
                    } else {
                        this.design.start_time = res.data.start_time
                        this.design.end_time = res.data.end_time
                    }
                }
            }).catch(err => {
                console.error(err)
                setTimeout(() => {
                    this.fetDesignStatus()
                }, 20000)
            })
        }
    }
})
</script>

<template>
    <div class="flex w-full justify-center">
        <div v-if="status === 'created'" class="flex border border-gray-500 text-gray-500 rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Created</span>
        </div>
        <div v-else-if="status === 'pending'" class="flex border border-yellow-500 text-yellow-500 rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Pending</span>
        </div>
        <div v-else-if="status === 'processing'" class="flex border border-success text-success rounded-full items-center px-3 w-max py-0.5">
            <span class="relative flex h-2 w-2 mr-1">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
            </span>
            <span class="font-thin capitalize">Processing</span>
        </div>
        <div v-else-if="status === 'completed'"
             class="flex bg-success text-white rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Completed</span>
        </div>
        <div v-else-if="status === 'failed'"
             class="flex bg-red-500 text-white rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Failed</span>
        </div>
        <div v-else-if="status === 'damaged'"
             class="flex bg-red-500 text-white rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Damaged</span>
        </div>
    </div>
</template>

<style scoped>

</style>
