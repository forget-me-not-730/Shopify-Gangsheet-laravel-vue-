<template>
    <TransitionRoot as="div" :show="open" class="fixed inset-0 z-[999]">
        <div class="flex w-full items-center justify-center h-full max-xs:pt-0 max-xs:px-0 max-xs:pb-0 text-center sm:block sm:p-0">
            <TransitionChild
                v-if="overLayer"
                as="div"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="bg-black bg-opacity-50 w-full h-full absolute inset-0" @click="handleOverLayerClick"></div>
            </TransitionChild>
            <TransitionChild
                as="div"
                class="h-full w-full"
                enter="ease-out duration-300"
                enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to="opacity-100 translate-y-0 sm:scale-100"
                leave="ease-in duration-200"
                leave-from="opacity-100 translate-y-0 sm:scale-100"
                leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    :class="{[classes?.body]: true, 'max-w-[800px]': !fullWidth}"
                    class="inline-block align-bottom transform transition-all h-full max-sm:w-full max-xs:py-0 sm:py-4 lg:py-8 sm:align-middle sm:w-full">
                    <slot/>
                </div>
            </TransitionChild>
        </div>
    </TransitionRoot>
</template>

<script>

import {TransitionChild, TransitionRoot} from '@headlessui/vue';

export default {
    name: "Modal",
    components: {
        TransitionChild, TransitionRoot
    },
    props: {
        open: {
            type: Boolean,
            default: false
        },
        overLayer: {
            type: Boolean,
            default: true
        },
        static: {
            type: Boolean,
            default: false
        },
        fullWidth: {
            type: Boolean,
            default: false
        },
        classes: {
            type: Object,
            default: () => {
                return {
                    body: null
                }
            }
        }
    },
    methods: {
        handleOverLayerClick() {
            if (this.static) return;
            this.$emit('close')
        }
    }
}
</script>

