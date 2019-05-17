<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import GangSheetArtBoardSettings from "@/Builder/GangSheet/GangSheetArtBoardSettings.vue";

export default defineComponent({
    name: "SettingsModal",
    components: {GangSheetArtBoardSettings, CloseIcon, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        classes() {
            return {
                body: '!w-full h-full'
            }
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')" :classes="classes">
        <div class="bg-builder border sm:rounded h-full flex flex-col">
            <div class="bg-builder border sm:rounded h-full flex flex-col">
                <div class="flex items-center justify-between relative p-2">
                    <h1 class="text-xl font-bold">{{ $t('Settings') }}</h1>
                    <div class="cursor-pointer" @click="$emit('close')">
                        <close-icon/>
                    </div>
                </div>
                <hr/>
                <div class="p-2 h-1 flex-1">
                    <gang-sheet-art-board-settings/>
                </div>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
