<script>
import {defineComponent} from 'vue'
import Modal from "@/Builder/Modals/Modal.vue";
import NameAndNumbers from "@/Builder/Components/NameAndNumbers.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";

export default defineComponent({
    name: "NameAndNumbersModal",
    components: {CloseIcon, NameAndNumbers, Modal},
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
            <name-and-numbers @close="$emit('close')"/>
        </div>
    </modal>
</template>

<style scoped>

</style>
