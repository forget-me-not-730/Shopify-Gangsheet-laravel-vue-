<template>
    <MerchantLayout title="customers">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Customers
                </h2>
                <div class="space-x-3">
                    <PrimaryButton @click="openModal(null)">
                        Add Customer
                    </PrimaryButton>
                </div>
            </div>
        </template>
        <Table :resource="customers" :striped="true" :preserve-scroll="true">
            <template #cell(id)="{ item: customer }">
                <label class="flex items-center cursor-pointer w-14">
                    <span class="pl-2">{{ customer.id }}</span>
                </label>
            </template>
            <template #cell(customer)="{ item: customer }">
                <img :src="customer.original_url" class="h-20" alt="">
            </template>
            <template #cell(created_at)="{ item: customer }">
                {{ $filters.formatDateTime(customer.created_at) }}
            </template>
            <template #cell(actions)="{ item: customer }" v-if="$page.props.auth.user.type !== 'woo'">
                <div class="flex gap-2">
                    <button @click="openModal(customer)" class="h-8 w-8 rounded-full border border-primary text-primary hover:bg-primary hover:text-white">
                        <i class="mdi mdi-pencil"></i>
                    </button>
                </div>
            </template>
        </Table>
        <CustomerModal v-if="modalOpen" :customer="customer" :close="closeModal" />
    </MerchantLayout>
</template>

<script>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { Table } from '@protonemedia/inertiajs-tables-laravel-query-builder'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import CustomerModal from '@/Pages/Merchant/CustomerModal.vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import SvgIcon from '@jamescoyle/vue-icon'
import { mdiChevronDown, mdiDelete } from '@mdi/js'
import ConfirmationMixin from '@/Mixins/ConfirmationMixin'

export default {
    name: 'Customers',
    components: { MenuItem, MenuItems, MenuButton, Menu, CustomerModal, SecondaryButton, PrimaryButton, MerchantLayout, Table, SvgIcon },
    mixins: [ConfirmationMixin],
    props: {
        customers: Object,
        user:Object,
    },
    data () {
        return {
            modalOpen: false,
            customer: null,
            selectedCustomers: [],
            status_filter: '',
            user: null,

            mdiChevronDown,
            mdiDelete
        }
    },
    mounted () {
        const hash = location.hash
        if (hash === '#create') {
            this.modalOpen = true
        }

        this.status_filter = this.status
    },
    methods: {
        closeModal () {
            this.modalOpen = false
        },
        openModal (customer, user) {
            this.customer = customer
            this.modalOpen = true

            this.user = user
        },
    }
}
</script>

<style scoped>

</style>
