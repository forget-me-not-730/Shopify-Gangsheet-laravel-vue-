export default {
    computed: {
        shop: {
            get () {
                return this.$store.state.shop
            },
            set (value) {
                this.$store.commit('setStore', { path: 'shop', value })
            }
        },
        gs_version () {
            return this.$store.state.gs_version
        },
        gs_latest_version () {
            return this.$store.state.gs_latest_version
        }
    },
}
