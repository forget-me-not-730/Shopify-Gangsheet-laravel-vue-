<script>
import {defineComponent} from 'vue'
import {getDesignStatus} from '@/Woo/Apis/gsbApi'

export default defineComponent({
    name: 'WooDesignStatus',
    props: {
        design: {
            type: Object,
            required: true
        },
    },
    mounted() {
        this.fetDesignStatus()
    },
    methods: {
        fetDesignStatus() {
            if (this.design.status.toLowerCase() !== 'completed') {
                setTimeout(() => {
                    getDesignStatus(this.design.design_id || this.design.id).then(res => {
                        if (res.data.success) {
                            this.design.status = res.data.status
                            this.$emit('change-status', this.design.status)
                            if (this.design.status.toLowerCase() !== 'completed') {
                                this.fetDesignStatus()
                            }
                        }
                    }).catch(err => {
                        console.error(err)
                        this.fetDesignStatus()
                    })
                }, 15000)
            }
        }
    }
})
</script>

<template>
    <div class="flex justify-center w-44 m-auto">
        <div v-if="design.status === 'created'"
             class="flex border border-gray-500 text-gray-500 rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Created</span>
        </div>
        <div v-else-if="design.status === 'pending'"
             class="flex border border-yellow-500 text-yellow-500 rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Pending</span>
        </div>
        <div v-else-if="design.status === 'processing'"
             class="flex border border-success text-success rounded-full items-center px-3 w-max py-0.5">
            <span class="relative flex h-2 w-2 mr-1">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
            </span>
            <span class="font-thin capitalize">Processing</span>
        </div>
        <div v-else-if="design.status === 'completed'"
             class="flex bg-success text-white rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Completed</span>
        </div>
        <div v-else-if="design.status === 'failed'"
             class="flex bg-red-500 text-white rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Failed</span>
        </div>
        <div v-else-if="design.status === 'damaged'"
             class="flex bg-red-500 text-white rounded-full items-center px-3 w-max py-0.5">
            <span class="font-thin capitalize">Damaged</span>
        </div>
    </div>
</template>

<style scoped>

</style>
