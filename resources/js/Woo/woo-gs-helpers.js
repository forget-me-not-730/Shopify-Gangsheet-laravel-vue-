export const getGsBaseUrl = () => {
    switch (window.appEnv) {
        case 'local':
            return `https://${window.GangSheetOptions.shop_slug}.buildagangsheet.test`
        case 'development':
            return `https://${window.GangSheetOptions.shop_slug}.dev.buildagangsheet.com`
        default:
            return `https://${window.GangSheetOptions.shop_slug}.buildagangsheet.com`
    }
}

export const getGsThumbnailUrl = (design_id) => {
    const previewUrl = new URL(`${getGsBaseUrl()}/thumbnail/${design_id}.png`)
    previewUrl.searchParams.append('domain', window.location.host)
    previewUrl.searchParams.append('v', new Date().getTime().toString())

    return previewUrl.toString()
}

export const getGsPreviewUrl = (design_id) => {
    const previewUrl = new URL(`${getGsBaseUrl()}/preview/${design_id}.png`)
    previewUrl.searchParams.append('domain', window.location.host)
    previewUrl.searchParams.append('v', new Date().getTime().toString())

    return previewUrl.toString()
}

export const getGsEditUrl = (design_id) => {
    const editUrl = new URL(`${getGsBaseUrl()}/woo/builder/edit`)
    editUrl.searchParams.append('domain', window.location.host)
    editUrl.searchParams.append('design_id', design_id)

    return editUrl.toString()
}

export const beforeTabClose = (e) => {
    const confirmationMessage = 'Are you sure you want to leave?'
    e.returnValue = confirmationMessage
    return confirmationMessage
}

export const closeBuilderModal = (id = 'gang-sheet-builder-modal') => {
    window.removeEventListener('beforeunload', beforeTabClose)
    let modal = document.getElementById(id)
    modal?.remove()
    document.body.style.overflow = 'auto'
    document.querySelector('html').style.overflow = 'auto'
}

export const getGsBuilderModal = (id = 'gang-sheet-builder-modal') => {
    let gsBuilderModal = document.getElementById(id)
    if (!gsBuilderModal) {
        gsBuilderModal = document.createElement('div')
        gsBuilderModal.id = id
        document.body.appendChild(gsBuilderModal)
    }

    return gsBuilderModal
}

export const getGsProductForm = (builderButton) => {
    let form = builderButton.closest('form')
    if (!form) {
        const forms = document.querySelectorAll('form')
        for (const _form of forms) {
            if (_form && _form.action.includes(GangSheetOptions.product_slug)) {
                form = _form
            }
        }
    }

    return form
}
