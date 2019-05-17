<script>
import { defineComponent } from 'vue'
import { getDesignDownloadUrl, getShopDesigns, generateDesign } from '@/Woo/Apis/gsbApi'
import Spinner from '@/Components/Spinner.vue'
import WooDesignStatus from '@/Woo/Components/WooDesignStatus.vue'
import rerenderMixin from '@/Mixins/rerenderMixin'

export default defineComponent({
    name: 'DesignPage',
    components: { WooDesignStatus, Spinner },
    mixins: [rerenderMixin],
    data () {
        return {
            designs: null,
            search: {
                design_id: '',
                order_id: ''
            },
            loading: false
        }
    },
    methods: {
        handleSearchDesign () {
            if (!this.search.design_id && !this.search.order_id) {
                return window.Toast.error({
                    message: 'Please enter a design ID or order ID'
                })
            }

            this.loading = true
            getShopDesigns(this.search).then(res => {
                if (res.data.success) {
                    this.designs = res.data.designs

                    if (!this.designs.length) {
                        window.Toast.info({
                            message: 'No designs found'
                        })
                    }
                } else {
                    window.Toast.error({
                        message: res.data.error
                    })
                }
            }).finally(() => {
                this.loading = false
            })
        },
        downloadDesign (design) {
            getDesignDownloadUrl(design.id).then(res => {
                if (res.data.success) {
                    window.open(res.data.url, '_blank')
                } else {
                    alert(res.data.error)
                }
            })
        },
        generateDesign (design) {
            design.status = 'processing'
            generateDesign(design.id).then(res => {
                if (res.data.success) {
                    window.Toast.success({
                        message: 'Gang sheet generation started'
                    })
                }
            })
        }
    }
})
</script>

<template>
    <div class="p-5">
        <div class="flex flex-wrap w-full">
            <div class="w-full md:w-1/2 p-2">
                <div class="bg-white p-5 rounded-lg shadow">
                    <h2 class="text-xl font-semibold">Search Design</h2>
                    <div class="mt-5 space-y-2">
                        <input v-model="search.design_id" type="text" class="w-full p-2 border border-gray-300 rounded-lg" placeholder="Design ID">
                        <input v-model="search.order_id" type="text" class="w-full p-2 border border-gray-300 rounded-lg" placeholder="Woocommerce Order ID">
                    </div>
                    <div class="mt-5">
                        <button @click="handleSearchDesign" :disabled="loading" class="w-full p-2 bg-blue-500 text-white rounded-lg flex items-center justify-center disabled:bg-gray-300">
                            <Spinner v-if="loading" class="mr-1"/>
                            Search
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="designs" class="w-full md:w-1/2 p-2">
                <div v-if="designs.length && renderComponent" class="bg-white p-5 rounded-lg shadow">
                    <h2 class="text-xl font-semibold">Designs Found</h2>
                    <div class="space-y-2 mt-3">
                        <div v-for="design in designs" :key="design.id" class="bg-gray-100 p-2 rounded-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold">Design: {{ design.id }}</h3>
                                </div>
                                <div>
                                    <woo-design-status :design="design" @change-status="forceRerender"/>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button v-if="design.status === 'completed'" @click="downloadDesign(design)" class="bg-blue-500 text-white p-2 rounded-lg">Download</button>
                                <button v-else :disabled="design.status === 'processing'" class="bg-blue-500 p-2 text-white rounded-lg disabled:bg-gray-300  disabled:cursor-not-allowed"
                                        @click="generateDesign(design)">
                                    Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="bg-white p-5 rounded-lg shadow">
                        <h2 class="text-lg text-gray-400">No Designs Found</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
