import eventBus from "@/Builder/Utils/eventBus";

export default {
    beforeMount() {
        if (window._gangSheetCanvasEditor) {
            if (typeof this.initializeSettings === 'function') {
                this.initializeSettings(window._gangSheetCanvasEditor)
            }
        }

        if (typeof this.initializeSettings === 'function') {
            eventBus.$off(eventBus.CANVAS_INITIALIZED, this.initializeSettings)
        }

        eventBus.$on(eventBus.CANVAS_INITIALIZED, (canvas) => {
            if (canvas) {
                if (typeof this.initializeSettings === 'function') {
                    this.initializeSettings(canvas)
                }
            }
        })
    },
    beforeUnmount() {
        if (typeof this.initializeSettings === 'function') {
            eventBus.$off(eventBus.CANVAS_INITIALIZED, this.initializeSettings)
        }
    }
}
