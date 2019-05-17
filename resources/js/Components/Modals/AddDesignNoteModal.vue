<script>
import {defineComponent} from 'vue'
import Modal from "@/Components/Modals/Modal.vue";
import Spinner from "@/Components/Spinner.vue";

export default defineComponent({
    name: "AddDesignNoteModal",
    components: {Spinner, Modal},
    props: {
        open: {
            type: Boolean,
            default: false
        },
        design: {
            type: [Object, null],
            required: true
        }
    },
    data() {
        return {
            memo: '',
            hasMemo: false,
            hasProblem: false,
            status: 'created',
            loading: false
        }
    },
    watch: {
        design: {
            immediate: true,
            handler() {
                this.loading = false
                if (this.design) {
                    this.status = this.design.status
                    this.memo = this.getDesignMeta(this.design, 'memo')
                    this.hasMemo = !!this.memo
                    this.hasProblem = !!this.getDesignMeta(this.design, 'has_problem')
                }
            }
        }
    },
    methods: {
        getDesignMeta(design, key) {
            return design.meta_data?.find(m => m.key === key)?.value
        },
        handleSubmit() {
            this.loading = true
            axios.post(route('admin.design.add-note'), {
                design_id: this.design.id,
                has_problem: this.hasProblem,
                memo: this.hasMemo ? this.memo : null,
                status: this.status
            }).then((res) => {
                if (res.data.success) {
                    this.design.meta_data = res.data.meta_data
                    this.design.status = this.status
                    this.loading = false
                    this.$emit('close')
                }
            })
        }
    }
})
</script>

<template>
    <Modal :open="open" @close="$emit('close')">
        <div class="w-full bg-white rounded-lg max-w-lg text-left">
            <div class="p-4 space-y-2">
                <select v-model="status" class="py-0 border border-gray-300 rounded text-xs">
                    <option value="failed">Failed</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="created">Created</option>
                </select>

                <label class="text-xs font-semibold flex items-center">
                    <input v-model="hasProblem" type="checkbox" class="checkbox-primary mr-2">
                    <span>Has Problem</span>
                </label>
                <label class="text-xs font-semibold flex items-center">
                    <input v-model="hasMemo" type="checkbox" class="checkbox-primary mr-2">
                    <span>Add Memo</span>
                </label>
                <textarea v-if="hasMemo" v-model="memo" class="w-full rounded mt-2"/>
            </div>
            <hr/>
            <div class="px-4 py-2 flex justify-end space-x-4">
                <button class="btn btn-secondary" @click="$emit('close')">Close</button>
                <button :disabled="loading" @click="handleSubmit" class="btn btn-primary">
                    <spinner v-if="loading" class="mr-1"/>
                    Submit
                </button>
            </div>
        </div>
    </Modal>
</template>

<style scoped>

</style>
