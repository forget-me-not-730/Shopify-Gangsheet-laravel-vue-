import {getterAndMutator} from './mixin'

export default {
    computed: {
        tool: getterAndMutator('tool'),
        shop: getterAndMutator('shop'),
        nameAndNumberFonts: getterAndMutator('nameAndNumberFonts'),
        shopFonts: getterAndMutator('shopFonts'),
        defaultFont: getterAndMutator('defaultFont'),
        product: getterAndMutator('product'),
        order: getterAndMutator('order'),
        variant: getterAndMutator('variant'),
        quantity: getterAndMutator('quantity'),
        variants: getterAndMutator('variants'),
        oldDesignId: getterAndMutator('oldDesignId'),
        images: getterAndMutator('images'),
        editMode: getterAndMutator('editMode'),
        patternMode: getterAndMutator('patternMode'),
        variantUpdated: getterAndMutator('variantUpdated'),
        customer: getterAndMutator('customer'),
        loadingDesign: getterAndMutator('loadingDesign'),
        savingDesign: getterAndMutator('savingDesign'),
        hasDesignChange: getterAndMutator('hasDesignChange'),
        admin: getterAndMutator('admin'),
        customerDesigns: getterAndMutator('customerDesigns'),
        recentDesigns: getterAndMutator('recentDesigns'),
        autoNestMode: getterAndMutator('autoNestMode'),
        gangSheetsPreview: getterAndMutator('gangSheetsPreview'),
        isFullScreen: getterAndMutator('isFullScreen'),
        editRequest: getterAndMutator('editRequest'),
        canva: getterAndMutator('canva'),
        googleDrive: getterAndMutator('googleDrive'),
        openApproveEditRequestModal: getterAndMutator('openApproveEditRequestModal'),
        autoSize: getterAndMutator('autoSize'),
        cutLineType: getterAndMutator('cutLineType'),
        artBoardUnit() {
            return this.variant?.unit ?? this.product.art_board_unit ?? 'in'
        },
        visibleVariants() {
            return this.variants.filter(variant => (variant.visible ?? 'Visible') === 'Visible')
        },
        hiddenVariants() {
            return this.variants.filter(variant => (variant.visible ?? 'Visible') === 'Hidden')
        },
        applyDiscountPrice() {
            return this.hiddenVariants.length > 0
        },
        discountThreshold() {
            return 240
        },
        shopSettings() {
            return this.$gsb.getShopSettings()
        },
        productSettings() {
            return this.$gsb.getProductSettings()
        },
        builderSettings() {
            return this.$gsb.getBuilderSettings()
        },
        artBoardSettings() {
            return this.$gsb.getArtBoardSettings()
        },
        isShopify() {
            return this.$store.state.isShopify
        },
        isCustomApi() {
            return this.$store.state.isCustomApi
        },
        token() {
            return this.$store.state.token
        },
        isAdminEdit() {
            return Boolean(this.admin || this.token)
        },
        isStandalone() {
            return this.$store.state.isStandalone
        },
        isWooCommerce() {
            return window.isWooCommerce
        },
        appEnv() {
            return window.appEnv
        },
        isDev() {
            return !this.appEnv.includes('production')
        },
        permissions() {
            const allowedReorder = this.builderSettings.allowedReorder
            const allowAutoNest = this.builderSettings.allowedAutoBuild
            const enableTour = this.builderSettings.enableTour

            const canReorder = !this.order && allowedReorder

            const hasPattern = this.variants.some(variant => {
                if (variant.pattern && variant.pattern.objects && variant.pattern.objects.length > 0)
                    return true
            })

            const autoNest = !this.order &&
                !this.editMode &&
                Number(this.variant?.maxAllowedFileCount ?? -1) === -1 && allowAutoNest && !hasPattern || this.admin

            return {
                canReorder: canReorder,
                autoNest: autoNest,
                tour: enableTour && this.isTestStore
            }
        },
        isTestStore() {
            const testStores = [
                'gsb-demo-video-store.myshopify.com'
            ]

            return testStores.includes(this.shop.name) ||
                !this.appEnv.includes('production')
        },
        isNinja() {
            return this.shop.name === 'transferss.myshopify.com' ||
                this.shop.name === 'ryonet.myshopify.com' ||
                this.shop.shop_uuid === '055d352c-8773-41b3-9006-a3e2f75d32b9'
            // this.shop.shop_uuid === '4f21c0f7-940b-4753-b5d4-8772449df254'
        }
    }
}
