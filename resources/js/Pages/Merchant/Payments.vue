<template>
    <MerchantLayout title="Products">
        <template #header>
            <div class="flex items-center justify-end w-full">
                <PrimaryButton @click="openModal">
                    Add Credits
                </PrimaryButton>
            </div>
        </template>

        <Table :resource="transactions" :striped="true" :preserve-scroll="true">
            <template #cell(status)="{ item: transaction }">
                <div class="capitalize">{{ transaction.status }}</div>
            </template>
            <template #cell(created_at)="{ item: transaction }">
                {{ $filters.formatDateTime(transaction.created_at) }}
            </template>
        </Table>

        <PaymentModal v-if="modalOpen" @close="closeModal" />
    </MerchantLayout>
</template>

<script>
import { Table } from "@protonemedia/inertiajs-tables-laravel-query-builder";
import { Link } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import MerchantLayout from "@/Layouts/MerchantLayout.vue";
import PaymentModal from "@/Pages/Merchant/PaymentModal.vue";

export default {
    components: {PaymentModal, MerchantLayout, PrimaryButton, Table, Link},
    props: {
        transactions: Object,
    },
    data(){
        return {
            modalOpen: false,
        }
    },
    methods: {
        closeModal(){
            this.modalOpen = false
        },
        openModal(){
            this.modalOpen = true
        }
    }
}
</script>
