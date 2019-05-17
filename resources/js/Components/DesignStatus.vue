<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: 'DesignStatus',
    props: {
        design: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            status: this.$props.design.status,
            timer: 0,
            mounted: false,
            generationTime: 0,
            timeInterval: 0
        }
    },
    watch: {
        design: {
            immediate: true,
            deep: true,
            handler() {
                if (this.design) {
                    this.status = this.design.status

                    if (this.status.toLowerCase() === 'deleted') {
                        return
                    }

                    if (this.design.status !== 'completed' && this.design.status !== 'created') {
                        setTimeout(() => {
                            this.fetDesignStatus()
                        }, 10000)
                    }

                    if (this.design.meta_data) {
                        const generationStartMeta = this.design.meta_data.find(meta => meta.key === 'generation_start')
                        if (generationStartMeta) {
                            this.generationTime = Math.floor((new Date().getTime() - new Date(generationStartMeta.value * 1000).getTime()) / 1000)
                            clearInterval(this.timeInterval)
                            this.timeInterval = setInterval(() => {
                                this.generationTime++
                            }, 1000)
                        }
                    }
                }
            }
        }
    },
    mounted() {
        this.mounted = true
    },
    beforeUnmount() {
        this.mounted = false,
            clearTimeout(this.timer)
    },
    methods: {
        fetDesignStatus() {
            this.timer = setTimeout(() => {
                axios.get(route('merchant.design.status', {design_id: this.design.id})).then(res => {
                    if (res.data.success) {
                        this.status = res.data.status
                        this.design.status = res.data.status
                        if (res.data.meta_data) {
                            this.design.meta_data = res.data.meta_data
                        }
                        if (this.status.toLowerCase() !== 'completed' && this.mounted) {
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
})
</script>

<template>
    <div class="flex w-24 justify-center">
        <div v-if="(status || 'created') === 'created'" class="flex border rounded-full items-center px-3 w-max py-0.5">
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
            <span class="capitalize">Processing <span v-if="generationTime">({{ generationTime }}s)</span></span>
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
