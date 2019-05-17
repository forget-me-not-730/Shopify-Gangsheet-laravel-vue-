export function getterAndMutator(key) {
    return {
        get() {
            return this.$store.state[key]
        },
        set(value) {
            this.$store.commit('setStore', {path: key, value})
        }
    }
}
