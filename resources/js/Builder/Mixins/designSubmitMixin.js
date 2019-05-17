import {clearStorageDesignForVariant, getSessionId} from '@/Builder/Utils/helpers'
import {ART_BOARD_TYPES, DESIGN_TYPES} from '@/Builder/Utils/constants'

export default {
    methods: {
        async saveDesign(json, thumbnail, type = DESIGN_TYPES.GANG_SHEET) {
            return new Promise(async (resolve) => {
                if (json.objects.filter(obj => !obj.isPattern).length === 0) {
                    this.$gsb.error('You must add designs.')
                    return resolve(false)
                }

                if (json.objects.some(obj => !obj.isPattern && obj.type.includes('text') && obj.text?.toLowerCase() === 'gang sheet')) {
                    this.$gsb.error('Seems like there is placeholder text on the design. Please remove it.')
                    return resolve(false)
                }

                this.savingDesign = true

                this.clearStorageJson()

                const url = this.isWooCommerce ? route('woo.builder.save-design') : route('builder.save-design')

                let variant_id = json.meta.variant.id

                if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                    variant_id = this.$gsb.getTieredVariant().id
                }

                if (!variant_id) {
                    variant_id = this.variant.id
                }

                window.axios.post(url, {
                    json: json,
                    design_id: json.designId,
                    quantity: json.quantity,
                    shop_id: this.shop.id,
                    product_id: this.product.id,
                    variant_id: variant_id,
                    customer_id: this.customer?.id || null,
                    thumbnail: thumbnail,
                    type: type,
                    session_id: getSessionId(),
                    token: this.token
                }).then(res => {
                    if (res.data.success) {
                        resolve(res)
                    } else {
                        this.$gsb.error(res.data.error)
                        resolve(null)
                    }
                }).catch((e) => {
                    console.log('Error: handleSaveAndAddToCart', e)
                    this.savingDesign = false
                    this.$gsb.error('Something went wrong.')
                    resolve(false)
                })
            })
        },
        clearStorageJson() {
            window.removeEventListener('beforeunload', () => {
                if (!window._gangSheetCanvasEditor?.isEmpty()) {
                    this.$gsb.updateCanvasData()
                }
            })
            clearStorageDesignForVariant()
        },
        getPostMessageData(design) {
            return {
                old_design_id: this.oldDesignId,
                variant_id: design.meta.variant.id,
                variant_title: design.meta.variant.title ?? design.meta.variant.label,
                quantity: design.quantity,
                design_name: design.name,
                product_title: this.product.title,
                hasOverlapping: design.imagesError,
                hasLowResolution: design.resolutionError,
                actualHeight: design.actualHeight,
                actualHeightLabel: design.actualHeightLabel,
                submitActualHeight: design.submitActualHeight,
                submitGangSheetHeight: this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET
            }
        },
        async addToCart(design_id, viewCartPage = true) {
            return new Promise((resolve) => {
                window.axios.post(route('builder.cart.add', {
                    ids: Array.isArray(design_id) ? design_id : [design_id]
                })).then(res => {
                    if (res.data.success) {
                        if (viewCartPage) {
                            this.$inertia.get(route('builder.cart.index', {slug: this.product.slug}))
                        }
                        resolve(true)
                    } else {
                        console.error(res)
                        resolve(false)
                    }
                }).catch((err) => {
                    console.error(err)
                    resolve(false)
                })
            })
        },
    }
}
