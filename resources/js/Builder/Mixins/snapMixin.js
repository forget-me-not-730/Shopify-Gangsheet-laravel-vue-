export default {

    data() {
        return {
            snapEnabled: false,
            snapXPositions: [],
            snapYPositions: [],
        }
    },

    methods: {
        handleSnap(canvas) {
            const p = canvas.snapPositions
            const snapPositions = [...p.object, ...p.edge, ...p.alignment]
            this.snapXPositions = []
            this.snapYPositions = []

            for (const position of snapPositions) {
                if (typeof position.x === 'number' && this.snapXPositions.every(_x => Math.abs(Math.round(position.x - _x)) > 10)) {
                    this.snapXPositions.push(Math.round(position.x))
                }
                if (typeof position.y === 'number' && this.snapYPositions.every(_y => Math.abs(Math.round(position.y - _y)) > 10)) {
                    this.snapYPositions.push(Math.round(position.y))
                }
            }
        },

        enableSnapLines() {
            this.snapEnabled = true
        },

        disableSnapLines() {
            this.snapXPositions = []
            this.snapYPositions = []
            this.snapEnabled = false
        }
    }
}
