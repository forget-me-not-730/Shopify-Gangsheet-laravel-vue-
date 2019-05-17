<script setup>
import {ref, onMounted} from 'vue'
import {router, usePage} from '@inertiajs/vue3'
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PaymentModal from "@/Pages/Merchant/PaymentModal.vue";
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiCheckCircle} from '@mdi/js';
import axios from 'axios';

const props = defineProps({
    toggleSidebar: {
        type: Function,
        default: () => {
        },
    },
})

const page = usePage()

const userCredits = ref(page.props.auth.user.credits)
const showModal = ref(false)
const creditNotification = ref('')
const autoChargeEnabled = ref('false')

const fetchCreditNotification = async () => {
    try {
        const response = await axios.get(route('merchant.payment.credit-notification'));
        creditNotification.value = response.data.message;
    } catch (error) {
        console.error("Error fetching credit notification:", error);
    }
}

const getCreditsInfo = async () => {
    try {
        const response = await axios.get(route('merchant.setting.credit'));
        if (response.data.success) {
            autoChargeEnabled.value = response.data.auto_charge_enabled;
        }
    } catch (error) {
        console.error("Error fetching credits info:", error);
    }
}

onMounted(() => {
    fetchCreditNotification();
    getCreditsInfo()
});

const logout = () => {
    router.post(route('logout'))
}

const openModal = () => {
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
}
</script>

<template>
    <div class="flex justify-between items-center p-4 bg-white border-b border-gray-200 h-14">
        <div v-if="userCredits > -1" class="flex gap-4 items-center">
            <div class="flex flex-col">
                <div class="flex items-center space-x-1">
                    <span class="text-gray-500 font-bold">
                        Available Credits: {{ userCredits }}
                    </span>
                    <div v-if="autoChargeEnabled" class="text-green-500">
                        <svg-icon type="mdi" :path="mdiCheckCircle" size="16"/>
                    </div>
                </div>
                <span class="text-red-500 font-bold">
                    {{ creditNotification }}
                </span>
            </div>
            <PrimaryButton @click="openModal">
                Add Credits
            </PrimaryButton>
        </div>
        <button type="button" class="lg:hidden -mr-3 inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-500 hover:text-gray-900" v-on:click.prevent="toggleSidebar()">
            <i class="mdi mdi-menu text-2xl"></i>
        </button>
        <button v-if="$page.props.admin_id" @click.prevent="$inertia.post(route('logout'))"
                class="h-8 border rounded px-2 border-primary text-primary hover:bg-primary hover:text-white flex items-center">
            <i class="mdi mdi-arrow-left mr-2"></i> Back to Admin
        </button>
    </div>
    <PaymentModal v-if="showModal" @close="closeModal"/>
</template>
