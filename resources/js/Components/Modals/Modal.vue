<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="fixed z-50 inset-0" @close="$emit('close')">
            <div class="flex w-full items-center justify-center h-full max-xs:pt-0 max-xs:px-0 pt-4 px-4 max-xs:pb-0 pb-20 text-center sm:block sm:p-0">
                <TransitionChild
                    v-if="overLayer"
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <DialogOverlay class="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity"/>
                </TransitionChild>
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to="opacity-100 translate-y-0 sm:scale-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100 translate-y-0 sm:scale-100"
                    leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div class="inline-block align-bottom transform transition-all h-full max-sm:w-full max-xs:py-0 py-4 lg:py-8 sm:align-middle max-w-[700px] sm:w-full">
                        <slot />
                    </div>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>

import {Dialog, DialogOverlay, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue';

export default {
    name: "Modal",
    components: {
        Dialog, DialogOverlay, DialogTitle, TransitionChild, TransitionRoot
    },
    props: {
        open: {
            type: Boolean,
            default: false
        },
        overLayer: {
            type: Boolean,
            default: true
        }
    }
}
</script>

