import {beforeTabClose, closeBuilderModal, getGsBuilderModal, getGsEditUrl, getGsPreviewUrl, getGsThumbnailUrl} from '@/Woo/woo-gs-helpers'
import {getDesign, getDesignDownloadUrl} from '@/Woo/Apis/gsbApi'

window.isWooCommerce = true

const gs_regex = /(^gs_design_id\s?:?\s?)([a-z0-9A-Z\-]{36})(\s?[\W\w]*$)/

function getGsDesignId(element) {
    const textContent = element.textContent.trim()
        .replace(/\t/g, '')
        .replace(/\n/g, '')
        .replace(/\\t/g, '')
        .replace(/\\n/g, '')
        .replace(/\s/g, '')

    if (
        element.tagName?.toLowerCase() === 'dd' &&
        element.previousElementSibling?.tagName?.toLowerCase() === 'dt' &&
        element.previousElementSibling.textContent.trim().startsWith('gs_design_id') &&
        /^[a-z0-9A-Z\-]{36}$/.test(textContent)
    ) {
        return textContent
    }

    if (element.textContent && gs_regex.test(textContent)) {
        const groups = textContent.match(gs_regex)
        return groups[2]
    }
    return null
}

function addGangSheetEventListeners() {
    document.querySelectorAll('.gs-design-preview').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault()

            const previewUrl = this.getAttribute('href')
            const previewModal = getGsBuilderModal('gang-sheet-preview-modal')

            const previewContainer = document.createElement('div')
            previewContainer.classList.add('gs-preview-container')

            previewContainer.innerHTML = `
                <div class="gs-preview-header">
                   <h4>Preview</h4>
                   <div id="gs-preview-close">X</div>
                </div>
                <div class="gs-preview-body">
                    <iframe src="${previewUrl}"></iframe>
                </div>
            `
            previewModal.appendChild(previewContainer)

            document.body.style.overflow = 'hidden'
            document.querySelector('html').style.overflow = 'hidden'

            const closeButton = document.getElementById('gs-preview-close')
            closeButton.addEventListener('click', () => {
                closeBuilderModal('gang-sheet-preview-modal')
            })
        })
    })

    document.querySelectorAll('.gs-design-edit').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault()
            const editUrl = this.getAttribute('href')

            const builderModal = getGsBuilderModal()

            let iframe = document.createElement('iframe')
            iframe.src = editUrl
            builderModal.appendChild(iframe)

            document.body.style.overflow = 'hidden'

            window.addEventListener('beforeunload', beforeTabClose)
        })
    })
}

function replaceGangSheetDesignEditAndPreviewLinks() {
    const gsAllElements = document.body.querySelectorAll('*')

    let gsElement = null
    for (const element of gsAllElements) {
        const design_id = getGsDesignId(element)
        if (design_id) {
            if (gsElement === null || document.body.contains(element)) {

                // remove gs element
                if (element.previousElementSibling?.tagName?.toLowerCase() === 'dt' && element.previousElementSibling.textContent.trim().startsWith('gs_design_id')) {
                    element.previousElementSibling.remove()
                    element.innerHTML = ''
                } else if (element.children.length > 2) {
                    for (let i = 0; i < element.children.length; i++) {
                        const textContent = element.children[i].textContent.trim()
                        if (textContent.includes('gs_design_id') || textContent.includes(design_id)) {
                            element.children[i--].remove()
                        }
                    }
                } else {
                    element.innerHTML = ''
                }

                const gsLinks = document.createElement('div')
                gsLinks.classList.add('gs_links')
                element.prepend(gsLinks)

                const previewUrl = getGsPreviewUrl(design_id)
                const previewLinkElement = document.createElement('a')
                previewLinkElement.setAttribute('href', previewUrl)
                previewLinkElement.classList.add('gs-design-preview')
                previewLinkElement.innerHTML = 'Preview'
                gsLinks.appendChild(previewLinkElement)

                const editUrl = getGsEditUrl(design_id)
                const editLinkLinkElement = document.createElement('a')
                editLinkLinkElement.setAttribute('href', editUrl)
                editLinkLinkElement.classList.add('gs-design-edit')
                editLinkLinkElement.innerHTML = 'Edit'
                gsLinks.appendChild(editLinkLinkElement)

                if (window.gs_admin) {
                    getDesign(design_id).then(res => {
                        if (res.data.success) {
                            const adminEditUrl = editUrl + `&token=${res.data.design.access_token}`
                            editLinkLinkElement.setAttribute('href', adminEditUrl)
                        }
                    })

                    const downloadLinkElement = document.createElement('a')
                    downloadLinkElement.classList.add('gs-design-download')
                    downloadLinkElement.innerHTML = 'Print Ready File'
                    gsLinks.appendChild(downloadLinkElement)

                    downloadLinkElement.addEventListener('click', function (e) {
                        e.preventDefault()
                        getDesignDownloadUrl(design_id).then(res => {
                            if (res.data.success) {
                                window.open(res.data.url, '_blank')
                            } else {
                                alert(res.data.error)
                            }
                        })
                    })
                }

                // replace product image
                let cartItemRow = element.closest('tr.wc-block-cart-items__row')
                if (!cartItemRow) {
                    cartItemRow = element.closest('tr.cart_item')
                }
                if (cartItemRow) {
                    const productImage = cartItemRow.querySelector('img')
                    if (productImage) {
                        productImage.setAttribute('src', getGsThumbnailUrl(design_id))
                        productImage.classList.add('gs-design-thumbnail')
                    }
                }
            }
            gsElement = element
        }
    }

    addGangSheetEventListeners()

}

function addGsMessageEventHandler() {
    window.addEventListener('message', function (e) {
        let data = e.data
        if (data && data.action) {
            if (data.action === 'gs_close_builder') {
                closeBuilderModal()
            }
        }
    }, false)
}

window.addEventListener('load', () => {
    if (window.GangSheetOptions) {
        replaceGangSheetDesignEditAndPreviewLinks()
        addGsMessageEventHandler()
    }
})
