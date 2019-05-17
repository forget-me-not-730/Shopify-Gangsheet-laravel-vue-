<template>
    <modal :open="open" @close="$emit('close')">
        <div class="bg-builder border text-left md:rounded w-full max-md:h-full">
            <div class="flex justify-between items-center relative p-2">
                <h1 class="text-xl font-bold">{{ $t('Selector') }}</h1>
                <div class="cursor-pointer" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>
            <hr/>
            <div class="p-2">
                <setting-panel/>
            </div>
        </div>
    </modal>
</template>

<script>

import Modal from "./Modal.vue";
import {defineAsyncComponent} from "vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

const SettingPanel = defineAsyncComponent(() => import("@/Builder/Components/SettingPanel.vue"))

export default {
    name: "SelectorModal",
    components: {CloseIcon, SettingPanel, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    computed: {}
}
</script>
