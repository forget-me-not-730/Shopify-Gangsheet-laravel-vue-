export default {
    data() {
        return {
            renderComponent: true
        }
    },
    methods: {
        async forceRerender() {
            // Remove MyComponent from the DOM
            this.renderComponent = false

            // Wait for the change to get flushed to the DOM
            await this.$nextTick()

            // Add the component back in
            this.renderComponent = true
        }
    }
}
