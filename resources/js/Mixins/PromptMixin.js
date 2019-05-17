export default {
    data() {
        return {
            loading: false,
            inputValue: '',
            imageValue: null,
            hasOldImage: false,
            oldImageUrl: null,
            enableColorOverlay: false,
            error: false,
        }
    },
    computed: {
        prompt: {
            get() {
                return this.$store.state.prompt
            },
            set(value) {
                this.$store.commit('setStore', {path: 'prompt', value})
            },
        }
    },
    watch: {
        prompt(value) {
            if (value) {
                this.inputValue = value.value
                this.enableColorOverlay = value.enableColorOverlay
            }
        },
        inputValue() {
            this.error = false
        }
    },
    methods: {
        async handleConfirm() {
            if (!this.inputValue) {
                return this.error = 'This field is required.'
            }

            if (typeof this.prompt?.validator === 'function') {
                let valid = await this.prompt.validator(this.inputValue)
                if (valid !== true) {
                    return this.error = valid
                }
            }

            if (typeof this.prompt?.onConfirm === 'function') {
                this.loading = true
                await this.prompt.onConfirm(this.inputValue, this.imageValue, this.enableColorOverlay, this.hasOldImage, this.oldImageUrl)
                this.loading = false
            }
            this.prompt = null
            this.imageValue = null
            this.hasOldImage = false
            this.oldImageUrl = null
            this.error = null
            this.enableColorOverlay = false
        },
        async handleCancel() {
            if (typeof this.prompt?.onCancel === 'function') {
                await this.prompt.onCancel()
            }
            this.prompt = null
            this.imageValue = null
            this.hasOldImage = false
            this.oldImageUrl = null
            this.error = null
        },
        async handleCancelImage() {
            if (typeof this.prompt?.onCancelImage === 'function') {
                await this.prompt.onCancelImage()
            }
            this.imageValue = null
            this.oldImageUrl = null
        },
        handleColorOverlayChange(value) {
            this.enableColorOverlay = value
        }
    },
}
