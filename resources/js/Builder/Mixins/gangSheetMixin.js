import builderMixin from '@/Builder/Mixins/builderMixin'
import {getterAndMutator} from '@/Builder/Mixins/mixin'

export default {
    mixins: [builderMixin],

    computed: {
        workingDesignIndex: getterAndMutator('workingDesignIndex'),
        workingDesigns: getterAndMutator('workingDesigns'),
        openWorkingDesigns: getterAndMutator('openWorkingDesigns'),
        homeVariant() {
            return this.workingDesigns[0]?.meta.variant || this.variant
        },
        currentDesign: {
            get() {
                return this.workingDesigns[this.workingDesignIndex]
            },
            set(value) {
                this.workingDesigns[this.workingDesignIndex] = value
            }
        },
        totalHeight() {
            return this.workingDesigns.reduce((acc, design) => {
                return acc + design.meta.variant.height
            }, 0)
        },
        totalSquare() {
            return this.productSettings.printerWidth * this.totalHeight
        },
        pricingType() {
            return this.productSettings.pricing.type
        },
        tieredPricing() {
            const findPrice = this.productSettings.pricing.prices.find(price => price.height >= this.totalHeight)
            if (findPrice) {
                return findPrice
            }

            return this.productSettings.pricing.prices[this.productSettings.pricing.prices.length - 1]
        },
        perSquarePrice() {
            if (this.pricingType === 'flat') {
                return this.productSettings.pricing.price
            } else {
                return this.tieredPricing.price
            }
        },
        totalPrice() {
            return this.totalSquare * this.perSquarePrice
        }
    },
    methods: {
        calculateDesignPrice(design) {
            return design.meta.variant.height * this.productSettings.printerWidth * this.perSquarePrice
        }
    }
}
