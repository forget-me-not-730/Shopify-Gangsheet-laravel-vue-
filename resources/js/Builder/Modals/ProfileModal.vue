<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import Spinner from "@/Components/Spinner.vue";
import builderMixin from "@/Builder/Mixins/builderMixin";
import {getCustomer} from "@/Builder/Apis/builderApi";
import eventBus from "@/Builder/Utils/eventBus";

export default defineComponent({
    name: "ProfileModal",
    components: {Spinner, Modal},
    mixins: [builderMixin],
    props: {
        open: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        handleLogout() {
            eventBus.$emit(eventBus.CUSTOMER_LOGOUT)
        }
    }
})
</script>

<template>
    <modal :open="open" @close="$emit('close')" :classes="{ body: 'max-w-md !w-full' }">
        <div class="flex flex-col bg-builder border sm:rounded max-h-full">
            <div class="flex justify-between relative px-4 pt-2">
                <h1 class="text-xl font-bold mb-3">{{ $t('My Profile') }}</h1>
                <div class="absolute right-[14px] top-[3px] text-3xl cursor-pointer" @click="$emit('close')">
                    &times;
                </div>
            </div>
            <hr/>
            <div class="flex-1 px-4 py-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-thumb-rounded scrollbar-track-gray-100">
                <div class="w-full space-y-4">
                    <div class="flex items-center">
                        <strong class="w-28 text-right mr-4">{{ $t('Name') }}: </strong>
                        <span>{{ customer.name }}</span>
                    </div>
                    <div class="flex items-center">
                        <strong class="w-28 text-right mr-4">{{ $t('Email') }}: </strong>
                        <span>{{ customer.email }}</span>
                    </div>
                    <div class="flex items-center">
                        <strong class="w-28 text-right mr-4">{{ $t('Designs') }}: </strong>
                        <div v-if="customer.designCount === undefined" class="flex items-center">
                            <spinner class="!w-4 !h-4 mr-2"/>
                            Loading ...
                        </div>
                        <span v-else>{{ customer.designCount }}</span>
                    </div>
                </div>
            </div>
            <hr/>
            <div class="w-full p-2 flex justify-end gap-2">
                <button v-if="isStandalone" class="btn-builder rounded" @click="handleLogout">{{ $t('Log Out') }}</button>
                <button class="btn-builder-outline" @click="$emit('close')">{{ $t('Close') }}</button>
            </div>
        </div>
    </modal>
</template>

<style scoped>

</style>
