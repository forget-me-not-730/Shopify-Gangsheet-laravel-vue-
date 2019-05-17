import emitter from 'tiny-emitter/instance'

export default {
    $on: (...args) => emitter.on(...args),
    $once: (...args) => emitter.once(...args),
    $off: (...args) => emitter.off(...args),
    $emit: (...args) => emitter.emit(...args),

    CANVAS_INITIALIZED: 'initializeCanvas',
    CANVAS_LOADED: 'loadCanvas',
    CANVAS_RESIZE: 'resizeCanvas',

    OPEN_DESIGN: 'open:design',
    OPEN_NEW_DESIGN: 'open:new-design',
    OPEN_MODAL: 'open:modal',

    CLOSE_MODAL: 'close:modal',
    CLOSE_MODAL_ALL: 'close:modal-all',

    REFRESH_BUILDER: 'refresh:builder',

    SAVE_DRAFT_DESIGN: 'save:draft-design',
    SAVE_ADD_CART_DESIGN: 'save:add-cart-design',
    SAVE_DESIGN: 'save:design',

    CUSTOMER_LOGIN: 'customer:login',
    CUSTOMER_LOGOUT: 'customer:logout',
    CUSTOMER_SWITCH_ACCOUNT: 'customer:switch-account',
    CUSTOMER_LOGIN_SUCCESS: 'customer:login-success',

    IMAGE_UPLOAD: 'image:upload',
    IMAGE_UPLOADED: 'image:uploaded',
    IMAGE_ADD: 'image:add',
    IMAGE_REUPLOADED: 'image:reupload',

    VARIANT_CHANGED: 'variant',

    DESIGNS_CONFIRM_AND_ADD_TO_CART: 'design:confirm-and-add-to-cart',
    DESIGNS_ADD_TO_CART: 'design:add-to-cart',

    UPDATE_SHOW_RESOLUTION_LINES: 'artboard:update-show-resolution-lines',
    UPDATE_SHOW_OVERLAPPING_LINES: 'artboard:update-show-overlapping-lines',

    STICKER_EDIT: 'sticker:edit',
    ALERT_LOADING: 'alert:loading',
    STICKER_CLEARED: 'sticker:cleared',

    CONTEXT_MENU: 'context:menu',

    ADD_TEXT_TO_AUTO_NEST: 'add:text-to-auto-nest'
}
