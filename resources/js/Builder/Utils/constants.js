export const MODAL_NAMES = {
    AGREE: 'modal-agree',
    AGREE_ALL: 'modal-agree-all',
    CONFIRM_DESIGN_SAVE: 'modal-confirm-design-save',
    CROP_IMAGE: 'modal-crop-image',
    DESIGN_QUALITY_CONFIRM: 'modal-design-quality-confirm',
    EDIT_IMAGE: 'modal-edit-image',
    IMAGE_UPLOAD: 'modal-image-upload',
    CUSTOMER_LOGIN: 'modal-customer-login',
    CUSTOMER_PROFILE: 'modal-customer-profile',
    CUSTOMER_DESIGNS: 'modal-customer-designs',
    SAVE_DESIGN: 'modal-save-design',
    START: 'modal-start',
    SELECTOR: 'modal-selector',
    ADD_QUANTITY: 'modal-add-quantity',

    AUTO_NEST: 'modal-auto-nest',
    AUTO_NEST_CONFIRM: 'modal-auto-nest-confirm',

    CANVA_DESIGNS: 'modal-canva-designs',
    REMOVE_IMAGE_CONFIRM: 'modal-remove-unnecessary-image',

    NAME_AND_NUMBERS: 'modal-name-and-numbers',
    GANG_SHEET_SETTINGS: 'modal-gang-sheet-settings',

    SELECT_IMAGE: 'modal-select-image',
    IMAGE_EDIT: 'modal-image-edit',

    IMAGE_UPLOAD_WARNING: 'modal-image-upload-warning',
    TEXT_EDIT: 'modal-text-edit',
    BACKGROUND_SETTINGS: 'modal-background-settings',
    CANVA_EXPORT_DESIGN: 'modal-canva-export-design'
}

export const FILE_TYPES = [
    {label: 'PNG', value: 'image/png'},
    {label: 'WEBP', value: 'image/webp'},
    {label: 'JPG', value: 'image/jpg,image/jpeg'},
    {label: 'SVG', value: 'image/svg,image/svg+xml'},
    {label: 'PSD', value: '.psd'},
    {label: 'AI/ESP', value: 'application/postscript'},
    {label: 'PDF', value: 'application/pdf'},
]

export const FILETYPE_MAP = {
    'image/png': 'png',
    'image/jpg': 'jpg',
    'image/webp': 'webp',
    'image/jpg,image/jpeg': 'jpg, jpeg',
    'image/jpeg': 'jpeg',
    'image/svg+xml': 'svg',
    '.psd': 'psd',
    'application/pdf': 'pdf',
    'application/postscript': 'ai, eps',
}

export const EXTRA_PROPERTIES = [
    'id',
    'designId',
    'name',
    'quantity',
    'meta',
    'unit',
    'selectable',
    'editable',
    'backgroundRemoved',
    'src',
    'group_type',
    'parentId',
    'isGalleryImage',
    'mimeType',
    'baseLines',
    'leftLines',
    'overlayColor',
    'overlayFilter',
    'margin',
    'marginEnabled',
    'artboardMargin',
    'artboardMarginEnabled',
    'thumb_url',
    'url',
    'realWidth',
    'realHeight',
    'lineSpace',
    'snapEnabled',
    'canvaId',
    'build_type',
    'enableColorOverlay',
    'isPattern'
]

export const CUT_LINES = {
    dieCut: 'die-cut',
    rectCut: 'rect-cut',
    roundedCut: 'rounded-cut',
    ellipseCut: 'ellipse-cut'
}

export const ART_BOARD_TYPES = {
    GANG_SHEET: 'gang-sheet',
    ROLLING_GANG_SHEET: 'rolling-gang-sheet',
    STICKER: 'sticker',
    BUSINESS_CARD: 'business-card',
    BANNER: 'banner',
    LASER: 'laser',
}

export const PRODUCT_TYPES = {
    GANG_SHEET: 1,
    STICKER: 2,
    LASER: 3,
    BUSINESS_CARD: 4,
    BANNER: 5,
    ROLLING_GANG_SHEET: 6,
    UPLOAD_BY_SIZE: 7,
    UPLOAD: 8
}

export const DEFAULT_AGREE_LABEL = 'By checking this box, you agree that you have full rights or own this artwork. You also agree that you understand that the images uploaded are correct and the printer is not responsible for any differences in color or artwork quality.'

export const DESIGN_TYPES = {
    GANG_SHEET: 1,
    STICKER: 2,
    ROLLING_LASER: 3,
    BUSINESS_CARD: 4,
    BANNER: 5,
    ROLLING_GANG_SHEET: 6,
}

export const DEFAULT_CONFIRMATION_LABEL = 'Add to Cart & Exit'

export const FILTERS = {
    GLOSS: {
        contrast: 0.9,
        brightness: 0.1,
        saturation: 0,
    },
    MATTE: {
        contrast: -0.2,
        brightness: -0.1,
        saturation: -0.2,
    },
    CLEAR: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        opacity: 0.5,
    },
    GLOW_IN_THE_DARK: {
        contrast: 0,
        brightness: 0.8,
        saturation: 0,
    },
    SILVER_FOIL: {
        contrast: 1.0,
        brightness: 0.1,
        saturation: 0,
    },
    BLEND_GREEN: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        color: "#7DBB36",
    },
    BLEND_RED: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        color: "#D0021B",
    },
    BLEND_BLUE: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        color: "#4A90E2",
    },
    BLEND_YELLOW: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        color: "#F8E71C",
    },
    BLEND_PURPLE: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        color: "#B569FC",
    },
    BLEND_BROWN: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        color: "#703B0F",
    },
    BLEND_TEAL: {
        contrast: 0,
        brightness: 0,
        saturation: 0,
        color: "#50E3C2",
    }

}

export const TOOLS = {
    main: 'main',
    image: 'image',
    nameAndNumbers: 'nameAndNumbers',
    uploads: 'uploads',
    gallery: 'gallery',
    canva: 'canva',
    google: 'google',
    settings: 'settings',
    text: 'text',
    edit: 'edit',
}

export const BUILD_TYPES = {
    GANG_SHEET: 'gangsheet',
    AUTO_BUILD: 'auto-build',
    MIXED: 'mixed',
}

export const LANGUAGES = [
    {label: 'English', value: 'en'},
    {label: 'French', value: 'fr'},
    {label: 'Japanese', value: 'ja'},
    {label: 'Denmark', value: 'dk'},
    {label: 'Hungarian', value: 'hu'},
    {label: 'Portuguese', value: 'pt'},
    {label: 'German', value: 'de'},
    {label: 'Italian', value: 'it'},
    {label: 'Spanish', value: 'es'},
    {label: 'Dutch', value: 'nl'},
    {label: 'Arabic', value: 'ar'}
]

export const THEME_PRESETS = [
    // main, top-bar, sidebar, primary, secondary, text
    ['#ffffff', '#ffffff', '#ffffff', '#0f172a', '#019aff', '#333333'],
    ['#ffffff', '#ffffff', '#ffffff', '#019aff', '#fb9133', '#222222'],
    ['#1e003f', '#0d001c', '#0d001c', '#a007ff', '#ce00bd', '#eeeeee'],
    ['#0f172a', '#020811', '#020811', '#005efc', '#005efc', '#eeeeee'],
]
