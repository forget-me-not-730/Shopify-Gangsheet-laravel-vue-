<template>
    <div class="h-[60px] shrink-0 w-full z-30 border-b gs-bg-top-bar">
        <div class="flex h-full">
            <div class="flex border-r items-center truncate px-2 max-sm:hidden w-[380px]">
                <img v-if="shop.logo_url" class="h-10 object-contain" :src="shop.logo_url" alt="App Logo"/>
                <h2 v-else class="font-bold text-3xl max-sm:hidden ml-2">
                    {{ shop.company_name }}
                </h2>
            </div>
            <div class="flex-1 flex items-center justify-between px-2">
                <div class="flex items-center space-x-2">
                    <button :disabled="savingDesign" class="btn-builder" @click="handleSavePatterns">
                        <spinner v-if="savingDesign" class="mr-2"/>
                        <span>{{ $t('Save Patterns') }}</span>
                    </button>

                    <button class="btn-danger text-sm py-2 px-4 ml-2" @click="handleClose">
                        {{ $t('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Spinner from '@/Components/Spinner.vue'
import gangSheetMixin from '@/Builder/Mixins/gangSheetMixin'
import {cloneDeep} from "lodash";

export default {
    name: 'PatternHeader',
    components: {Spinner},
    mixins: [gangSheetMixin],
    methods: {
        async handleSavePatterns() {
            this.savingDesign = true

            this.$gsb.updateCanvasData()

            const patterns = this.variants.map(variant => {
                return {
                    variant_id: variant.id,
                    pattern: cloneDeep(variant.pattern),
                }
            })

            axios.post(route('merchant.product.pattern.save', {product_id: this.product.id}), {
                patterns: patterns,
            }).then(response => {
                this.$gsb.success('Patterns saved successfully')
            }).catch(error => {
                console.error(error)
                this.$gsb.error('Failed to save patterns')
            }).finally(() => {
                this.savingDesign = false
            })
        },
        handleClose() {
            window.close()
        }
    }
}
</script>

<style scoped>

</style>
