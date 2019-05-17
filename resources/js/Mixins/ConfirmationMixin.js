export default {
    data() {
        return {
            loading: false
        }
    },
    computed: {
        confirmation: {
            get() {
                return this.$store.state.confirmation
            },
            set(value) {
                this.$store.commit('setStore', {path: 'confirmation', value})
            }
        }
    },
    methods: {
        async handleConfirm() {
            if(typeof this.confirmation?.onConfirm === 'function') {
                this.loading = true
                await this.confirmation.onConfirm()
                this.loading = false
            }
            this.confirmation = null
        },
        async handleCancel() {
            if(typeof this.confirmation?.onCancel === 'function') {
                await this.confirmation.onCancel()
            }
            this.confirmation = null
        }
    }
}
