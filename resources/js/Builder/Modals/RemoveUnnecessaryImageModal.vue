<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiAlertCircleOutline, mdiClose} from '@mdi/js'
import gangSheetMixin from '../Mixins/gangSheetMixin';

export default defineComponent({
    name: "RemoveUnnecessaryImageModal",
    components: {SvgIcon, Modal},
    mixins: [gangSheetMixin],
    props: {
        open: {
            type: Boolean,
            required: true
        }
    },
    data() {
        return {
            mdiAlertCircleOutline,
            mdiClose
        }
    },
    methods: {
        async handleRemoveImages() {
            _gangSheetCanvasEditor.removeOutViewPortObjects()

            this.$gsb.updateCanvasData()
            this.$emit('close')
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')">
        <div class="flex flex-col bg-builder border sm:rounded max-h-full max-w-md mx-auto">
            <div class="flex-1 p-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                <div class="flex flex-col w-full text-center justify-center items-center my-5 text-yellow-300">
                    <svg-icon type="mdi" :path="mdiAlertCircleOutline" size="96"/>
                    <p class="mt-4 text-black">We've detected some unnecessary images outside the artboard.<br/>Please confirm that we remove those images.</p>
                </div>
            </div>
            <hr class="mt-4"/>
            <div class="flex justify-end relative px-4 py-2">
                <button class="btn-builder-outline mr-4 border rounded px-5" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </button>
                <button class="btn-builder px-6 border mr-2" @click="handleRemoveImages">
                    {{ $t('Remove') }}
                </button>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
