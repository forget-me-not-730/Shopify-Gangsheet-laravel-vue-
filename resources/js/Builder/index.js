import './Styles/builder.scss'

import {fabric} from "fabric";
import ElementPlus from 'element-plus'

import {createStore} from 'vuex'
import {createI18n} from 'vue-i18n'

import messages from './Messages/locales'
import {v4 as uuidv4} from 'uuid'
import {clearStorageDesignForVariant, convertDimension, getPixelSize} from "@/Builder/Utils/helpers";
import {cloneDeep} from "lodash";
import eventBus from "@/Builder/Utils/eventBus";
import {DEFAULT_CONFIRMATION_LABEL, ART_BOARD_TYPES, EXTRA_PROPERTIES, BUILD_TYPES} from "@/Builder/Utils/constants";

const i18n = createI18n({
    locale: 'en',
    messages,
})

const defaultState = {

    /**
     * General
     */
    shop: {},
    product: {
        art_board_type: ART_BOARD_TYPES.GANG_SHEET
    },
    order: null,
    variant: null,
    customer: null,
    quantity: 1,
    variants: [],
    shopFonts: [],
    nameAndNumberFonts: [],
    defaultFont: [],
    images: [],
    hasDesignChange: false,
    loadingDesign: false,
    savingDesign: false,
    admin: false,
    isStandalone: false,
    isShopify: false,
    isCustomApi: false,
    oldDesignId: null,
    editMode: false,
    variantUpdated: false,
    isFullScreen: false,
    canva: {
        loading: true,
        access_token: null,
        authorize_url: null,
        name: null,
        email: null
    },
    googleDrive: {
        loading: true,
        access_token: null,
        authorize_url: null,
        name: null,
        email: null
    },
    tool: 'main',
    customerDesigns: null,
    recentDesigns: null,
    workingDesignId: null,
    workingDesigns: [],
    showArtBoardOutline: false,
    workingDesignIndex: 0,
    showImageOutline: true,
    cutLineType: 'die-cut',
    gangSheetsPreview: false,
    autoNestMode: false,
    openWorkingDesigns: true,
    autoSize: false,
    patternMode: false,

    shopSettings: null,
    productSettings: null,
    builderSettings: null,
    artBoardSettings: null
}

const store = createStore({
    state: defaultState,
    mutations: {
        setStore(state, {path, value}) {
            _.set(state, path, value)
        }
    },
    actions: {},
    getters: {}
})

const GangSheetBuilder = {
    install(app) {

        app.use(i18n)
        app.use(store)
        app.use(ElementPlus)

        app.mixin({
            methods: {
                setLocale(locale) {
                    this.$i18n.locale = locale ?? 'en'
                }
            },
        })

        app.config.globalProperties.$gsb = {
            getCanvas() {
                return window._gangSheetCanvasEditor
            },

            /**
             * Get the all available variants
             * @returns {[]}
             */
            getAllVariants() {
                return store.state.variants
            },

            getVisibleVariants() {
                return store.state.variants.filter(variant => (variant.visible ?? 'Visible') === 'Visible')
            },

            getHiddenVariants() {
                return store.state.variants.filter(variant => (variant.visible ?? 'Visible') === 'Hidden')
            },

            getDiscountThresholdHeight() {
                return 240
            },

            /**
             * Get the current design
             * @returns {*}
             */
            getCurrentDesign() {
                return store.state.workingDesigns[store.state.workingDesignIndex]
            },

            /**
             * Set the current design
             * @param design
             */
            setCurrentDesign(design) {
                store.state.workingDesigns[store.state.workingDesignIndex] = design
            },

            /**
             * Get the variant of the first design
             * @returns {*}
             */
            getHomeVariant() {
                return store.state.workingDesigns[0]?.meta.variant || store.state.variant
            },

            /**
             * Get/update the shop settings
             * @returns {*}
             */
            getShopSettings() {
                if (!store.state.shopSettings) {
                    const shopSettings = store.state.shop.settings ?? {}

                    const defaultSettings = {
                        enableTour: false,
                        disableTextFeature: false,
                        disableBackgroundRemoveTool: false,
                        allowedReorder: true,

                        // auto build
                        allowedAutoBuild: true,
                        alwaysDisplayVariantsInAutoBuild: false,
                        autoBuildSetUp: 'recommended',
                        openAutoBuildAsDefault: false,

                        // margin
                        turnOnMargin: true,
                        turnOnArtboardMargin: shopSettings?.turnOnMargin ?? true,
                        defaultMarginSize: 0.125,
                        defaultArtboardMarginSize: shopSettings?.defaultArtboardMarginSize ?? shopSettings?.defaultMarginSize ?? 0.125,
                        defaultMarginUnit: 'in',
                        defaultArtboardMarginUnit: 'in',

                        // colors
                        useCustomTextColors: false,
                        customTextColors: [],
                        useCustomImageOverlayColors: false,
                        customImageOverlayColors: [],

                        // welcome popup
                        showStartModal: true,
                        startModalView: 'list',
                        startModals: (() => {
                            const defaultStartModals = [
                                {
                                    id: 1,
                                    image: '',
                                    label: 'Start a Brand New Gang Sheet',
                                    enabled: true
                                }, {
                                    id: 2,
                                    image: '',
                                    label: 'Open a Previously Ordered Gang Sheet',
                                    enabled: true
                                }, {
                                    id: 3,
                                    image: '',
                                    label: 'Auto Build',
                                    enabled: true
                                }, {
                                    id: 4,
                                    image: '',
                                    label: 'Open Working Gang Sheet',
                                    enabled: true
                                }, {
                                    id: 5,
                                    image: '',
                                    label: 'Name and Number',
                                    enabled: false
                                }
                            ];

                            const existingStartModals = shopSettings?.startModals ?? [];

                            const missingItems = defaultStartModals.filter(defaultItem =>
                                !existingStartModals.some(existingItem => existingItem.id == defaultItem.id)
                            );

                            return [...existingStartModals, ...missingItems];
                        })(),
                        enableLiveChat: false,

                        // gallery
                        showGallery: store.state.shop.show_gallery,
                        galleryMode: 'dropdown',
                        enableSort: true,
                        enableColorOverlay: false,
                        colorOverlayAllowed: 'all',

                        // image upload/add
                        file_types: [
                            'image/png',
                            'image/jpg',
                            'image/jpeg',
                            'image/webp',
                            'image/svg+xml',
                            '.psd',
                            'application/pdf',
                            'application/postscript'
                        ],
                        autoResizeSingleImage: true,
                        ensureOptimalResolution: true,
                        enableImageBackgroundWarning: false,
                        resolutionLevels: {
                            optimal: 300,
                            good: 250,
                            bad: 200
                        },
                        currencyCode: 'USD',
                        currencySymbol: '$',
                    }

                    const settings = _.merge({}, defaultSettings, shopSettings)

                    settings.enableLiveChat = settings.enableChat && settings.chatScript && settings.chatScript.includes('code.tidio.co')

                    store.state.shopSettings = settings
                }

                return store.state.shopSettings
            },

            /**
             * Get the product settings
             * @returns {null}
             */
            getProductSettings() {
                if (!store.state.productSettings) {
                    const productSettings = store.state.product?.settings ?? {}

                    const defaultSettings = {
                        printerWidth: 22,
                        startHeight: 40,
                        minHeight: 20,
                        maxHeight: 360,
                        pricing: {
                            type: 'flat',
                            price: 0.03,
                            prices: [
                                {
                                    height: 22,
                                    price: 0.0001
                                }
                            ]
                        },
                        disableUploadingImage: false,
                    }

                    store.state.productSettings = _.merge({}, defaultSettings, productSettings)
                }

                return store.state.productSettings
            },

            /**
             * Get the builder settings
             * @returns {null}
             */
            getBuilderSettings() {
                if (!store.state.builderSettings) {
                    const shopSettings = this.getShopSettings()
                    const productSettings = this.getProductSettings()

                    const defaultSettings = {
                        // spacing
                        lockMargin: false,
                        lockArtboardMargin: false,
                        turnOnMargin: true,
                        turnOnArtboardMargin: true,
                        defaultMarginSize: 0.125,
                        defaultArtboardMarginSize: 0.125,
                        defaultMarginUnit: 'in',
                        defaultArtboardMarginUnit: 'in',
                        defaultAutoDuplicateMarginSize: 0.125,
                        nameAndNumber: {
                            enabled: false,
                            unit: 'in',
                            default: false,
                            size: {
                                name: {
                                    sm: 1,
                                    lg: 2,
                                },
                                number: {
                                    sm: 4,
                                    lg: 8,
                                }
                            },
                        },
                        enableQuantity: true,
                        enableAddNewDesign: true,
                        confirmationButtonLabel: DEFAULT_CONFIRMATION_LABEL,
                        enableCustomerAccount: true,
                        enableCanva: false,
                        enableGoogleDrive: false,
                        enableFlipping: true,
                    }

                    store.state.builderSettings = _.merge({}, defaultSettings, shopSettings, productSettings)
                }

                return store.state.builderSettings
            },

            /**
             * Get empty design
             * @returns {*}
             */
            getEmptyDesign(variant) {
                let newDesignName = 'New Gang Sheet'

                let newDesignNameCount = 0

                for (const design of store.state.workingDesigns) {
                    if (design.name.toLocaleString().startsWith(newDesignName.toLocaleString())) {
                        newDesignNameCount++

                        const tempName = `${newDesignName} - (${newDesignNameCount})`
                        if (store.state.workingDesigns.every(_d => _d.name !== tempName)) {
                            newDesignName = tempName
                        }
                    }
                }

                variant = store.state.variant

                if (this.getHiddenVariants().length && this.getCurrentDesign() && variant.height >= this.getDiscountThresholdHeight()) {
                    const discountVariant = this.getSameTypeSizeVariants(this.getHiddenVariants()).find(v => v.visible === 'Hidden' && v.height === variant.height)

                    if (discountVariant) {
                        variant = discountVariant
                    }
                }

                return {
                    id: uuidv4(),
                    designId: null,
                    name: newDesignName,
                    quantity: 1,
                    meta: {
                        variant: variant,
                        images: store.state.images,
                        build_type: BUILD_TYPES.GANG_SHEET
                    },
                    objects: []
                }
            },

            getArtBoardSettings() {
                if (!store.state.artBoardSettings) {
                    let artBoardSettings = localStorage.getItem('gs-art-board-settings')

                    const defaultArtBoardSettings = {
                        visualQuality: true,
                        enableImageBackgroundWarning: this.getBuilderSettings().enableImageBackgroundWarning,
                        lang: this.getShopSettings().language
                    }

                    if (artBoardSettings) {
                        store.state.artBoardSettings = Object.assign({}, defaultArtBoardSettings, JSON.parse(artBoardSettings))
                    } else {
                        store.state.artBoardSettings = defaultArtBoardSettings
                    }
                }

                return store.state.artBoardSettings
            },

            setArtBoardSettings(key, value = null) {
                let artBardSettings = this.getArtBoardSettings()

                if (typeof key === 'object') {
                    artBardSettings = {...artBardSettings, ...key}
                } else {
                    artBardSettings[key] = value
                }
                localStorage.setItem('gs-art-board-settings', JSON.stringify(artBardSettings))
            },

            resetArtBoardSettings() {
                localStorage.removeItem('gs-art-board-settings')
            },

            /**
             * Update the canvas data
             */
            updateCanvasData() {
                if (window._gangSheetCanvasEditor && !store.state.gangSheetsPreview) {
                    const json = window._gangSheetCanvasEditor.exportJson()

                    if (store.state.patternMode) {
                        if (json.objects.length) {
                            store.state.variant.pattern = json
                        } else {
                            store.state.variant.pattern = null
                        }
                    } else {
                        if (!this.getCurrentDesign()) {
                            json.quantity = store.state.quantity
                        }

                        this.setCurrentDesign(json)

                        if (json.objects.length && !window._gangSheetCanvasEditor.designId) {
                            const key = 'GangSheetDesign_' + json.meta.variant.id
                            if (store.state.editMode) {
                                localStorage.removeItem(key)
                            } else {
                                try {
                                    localStorage.setItem(key, JSON.stringify(json))
                                } catch (error) {
                                    localStorage.removeItem(key)
                                }
                            }
                        }
                    }
                }
            },

            /**
             * Filter the variants by the selected options of a variant
             * @param variants
             * @param variant
             * @returns {*}
             */
            getSameTypeSizeVariants(variants, variant) {
                if (!variants) {
                    variants = store.state.variants
                }

                if (!variant) {
                    variant = this.getHomeVariant()
                }

                if ((variant.selectedOptions || []).length > 1 && store.state.product.size_option) {
                    return variants.reduce((r, v) => {
                        const isSameType = v.selectedOptions.every((option, index) => {
                            if (option.name === store.state.product.size_option) {
                                return true
                            }

                            return option.value === variant.selectedOptions[index].value
                        })

                        if (isSameType && !r.find(_v => v.width === _v.width && v.height === _v.height)) {
                            r.push(v)
                        }

                        return r
                    }, [])
                }

                return variants
            },

            getTotalHeight() {
                return store.state.workingDesigns.reduce((acc, design) => {
                    return acc + design.meta.variant.height
                }, 0)
            },

            getTieredVariant() {
                const findVariant = store.state.variants.find(v => v.height >= this.getTotalHeight())
                if (findVariant) {
                    return findVariant
                }

                return store.state.variants[store.state.variants.length - 1]
            },

            /**
             * Create the current design from the bin
             * @param bin
             * @param options
             * @returns {Promise<void>}
             */
            async createCurrentDesignFromBin(bin, options = {}) {

                const {margin = 0, artBoardMargin = 0, autoTrim = false} = options

                const canvas = this.getCanvas()
                if (canvas) {
                    const viewPort = canvas.getViewPort()
                    const newJson = cloneDeep(this.getCurrentDesign())

                    if (autoTrim) {
                        const lowestRect = bin.rects.reduce((r, rect) => {
                            const rectBottom = rect.y + rect.height
                            const rBottom = r.y + r.height
                            if (rectBottom > rBottom) {
                                return rect
                            }
                            return r
                        }, bin.rects[0])
                        const lowestRectBottom = lowestRect.y + lowestRect.height
                        const productSettings = this.getProductSettings()
                        const minHeight = store.state.workingDesignIndex > 0 ? productSettings.minHeight : productSettings.startHeight
                        newJson.meta.variant.height = Math.max(Number((lowestRectBottom + artBoardMargin).toFixed(2)), minHeight)

                        const findVariant = this.getTieredVariant()
                        newJson.meta.variant.id = findVariant.id
                    }

                    newJson.meta.build_type = BUILD_TYPES.AUTO_BUILD

                    newJson.margin = margin
                    newJson.artboardMargin = artBoardMargin
                    newJson.marginEnabled = margin > 0
                    newJson.artboardMarginEnabled = artBoardMargin > 0

                    newJson.objects = []

                    await Promise.all(bin.rects.map(async (rect) => {
                        const object = await fabric.util.makeObjectFromPack(rect, {
                            viewPort,
                            margin: margin,
                            artBoardMargin: artBoardMargin,
                            unit: newJson.meta.variant.unit
                        })

                        if (object) {
                            newJson.objects.push(object.toJSON(EXTRA_PROPERTIES))
                        }
                    }))

                    eventBus.$emit(eventBus.REFRESH_BUILDER, newJson)
                }
            },

            /**
             * Create designs from the auto nested bins
             * @param bins
             * @param options
             * @returns {Promise<void>}
             */
            async createDesignsFromBins(bins, options = {}) {

                const {margin = 0, artBoardMargin = 0, autoTrim = false} = options

                store.state.openWorkingDesigns = false
                clearStorageDesignForVariant()
                const designs = []
                const center = this.getCanvas().getCenter()

                for (let index = 0; index < bins.length; index++) {
                    const pack = bins[index]
                    const images = []
                    const variant = pack.variant

                    if (autoTrim) {
                        const lowestRect = pack.rects.reduce((r, rect) => {
                            const rectBottom = rect.y + rect.height
                            const rBottom = r.y + r.height
                            if (rectBottom > rBottom) {
                                return rect
                            }
                            return r
                        }, pack.rects[0])
                        const lowestRectBottom = lowestRect.y + lowestRect.height
                        const productSettings = this.getProductSettings()
                        const minHeight = index > 0 ? productSettings.minHeight : productSettings.startHeight
                        variant.height = Math.max(Number((lowestRectBottom + artBoardMargin).toFixed(2)), minHeight)

                    }

                    const designWidth = variant.width * getPixelSize(pack.variant.unit)
                    const designHeight = variant.height * getPixelSize(pack.variant.unit)
                    const viewPort = fabric.util.makeViewPort(center, designWidth, designHeight)

                    const newJson = {
                        id: uuidv4(),
                        designId: null,
                        name: `Gang Sheet - (${index + 1})`,
                        quantity: 1,
                        margin: margin,
                        artboardMargin: artBoardMargin,
                        marginEnabled: margin > 0,
                        artboardMarginEnabled: artBoardMargin > 0,
                        meta: {
                            variant: variant,
                            viewport: viewPort,
                            unit: variant.unit,
                            build_type: BUILD_TYPES.AUTO_BUILD
                        },
                        objects: []
                    }

                    await Promise.all(pack.rects.map(async (rect) => {
                        const image = await fabric.util.makeObjectFromPack(rect, {
                            viewPort,
                            margin: margin,
                            artBoardMargin: artBoardMargin,
                            unit: variant.unit
                        })
                        if (image) {
                            newJson.objects.push(image.toJSON(EXTRA_PROPERTIES))

                            if (rect.url && !images.find(img => img.url === rect.url)) {
                                images.push({
                                    id: image.id,
                                    parentId: image.parentId,
                                    url: rect.url,
                                    width: image.realWidth,
                                    height: image.realHeight,
                                    isGalleryImage: image.isGalleryImage,
                                    mimeType: image.mimeType,
                                })
                            }
                        }
                    }))

                    newJson.meta.images = images
                    newJson.actualHeight = viewPort.height
                    newJson.actualHeightLabel = convertDimension(viewPort.height, 'px', variant.unit).toFixed(2) + ' ' + variant.unit

                    designs[index] = newJson
                }


                store.state.workingDesigns = designs

                if (store.state.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                    store.state.workingDesigns.forEach(design => {
                        design.meta.variant.id = this.getTieredVariant().id;
                    });
                }

                store.state.workingDesignIndex = 0
                store.state.openWorkingDesigns = true
                store.state.variant = bins[0].variant
                store.state.autoNestMode = false

                if (autoTrim) {
                    eventBus.$emit(eventBus.CANVAS_RESIZE)
                }
            },

            getVariantsForAutoNest(nameAndNumber = false) {
                return this.getSameTypeSizeVariants(this.getVisibleVariants()).reduce((r, v) => {
                    const _variant = this.getHomeVariant()

                    let isValidVariant = v.width === _variant.width;

                    if (nameAndNumber) {
                        // if it's for name and number, the height should not be less than the currently selected variant
                        isValidVariant = isValidVariant && v.height >= _variant.height;
                    }

                    if (isValidVariant) {
                        const useHiddenVariants = this.getHiddenVariants().length > 0 && v.height >= this.getDiscountThresholdHeight()

                        r.push({
                            width: Number(v.width),
                            height: Number(v.height),
                            useHiddenVariants: useHiddenVariants,
                            ...v
                        })
                    }

                    return r
                }, [])
            },

            addNewImage(newImage) {
                if (store.state.images.length < 100) {
                    const hasImage = store.state.images.some(img => img.url === newImage.url)
                    if (!hasImage) {
                        store.state.images.push({...newImage})
                        if (window._gangSheetCanvasEditor) {
                            window._gangSheetCanvasEditor._addImage({...newImage})
                        }
                    }
                } else {
                    this.warning('You can have up to 100 images saved.')
                }

                if (store.state.autoNestMode) {
                    eventBus.$emit(eventBus.IMAGE_ADD, newImage)
                } else {
                    if (window._gangSheetCanvasEditor) {
                        window._gangSheetCanvasEditor.addImage(newImage)
                    }
                }
            },

            addUploadImageToStoreImages(image) {
                const newImage = {
                    id: image.id,
                    url: image.url,
                    thumb_url: image.thumb_url,
                    width: image.width,
                    height: image.height,
                    resolution: image.resolution,
                }

                this.addNewImage(newImage)
            },

            addGalleryImageToStoreImages(image) {
                const newImage = {
                    id: 'gallery_' + image.id,
                    url: image.url,
                    originUrl: image.original_url,
                    isGalleryImage: true,
                    enableColorOverlay: image.color_overlay,
                    mimeType: image.mime_type,
                    width: image.width,
                    height: image.height,
                }

                this.addNewImage(newImage)
            },

            error(message) {
                if (message) {
                    window.Toast.error({
                        message: message
                    })
                }
            },

            success(message) {
                if (message) {
                    window.Toast.success({
                        message: message
                    })
                }
            },

            warning(message) {
                window.Toast.warning({
                    message: message
                })
            }
        }
    }
}

export default GangSheetBuilder
