import {beforeTabClose, closeBuilderModal, getGsBaseUrl, getGsBuilderModal, getGsProductForm} from '@/Woo/woo-gs-helpers'

function buildGangSheetButton() {
    const gsBuilderButton = document.querySelector('#gang-sheet-builder-button')

    // make display block
    gsBuilderButton.style.display = 'inline-block!important'

    gsBuilderButton.addEventListener('click', function (event) {
        event.preventDefault()

        const form = getGsProductForm(gsBuilderButton)

        if (form) {
            if (!form[0].checkValidity()) {
                form[0].reportValidity()
                return
            }
        }

        const gsBuilderModal = getGsBuilderModal()

        let variant_id = window.GangSheetOptions.variants[0].id

        const variantTitle = document.querySelector('[name="attribute_size"]')?.value
        if (variantTitle) {
            const selectedVariant = window.GangSheetOptions.variants.find(variant => variant.title === variantTitle)
            if (selectedVariant) {
                variant_id = selectedVariant.id
            }
        }

        if (!variant_id) {
            variant_id = window.GangSheetOptions.variants[0].id
        }

        let quantity = 1

        const quantityElement = document.querySelector('[name="quantity"]')
        if (quantityElement && quantityElement.value) {
            quantity = Number(quantityElement.value)
        }

        const builderUrl = new URL(`${getGsBaseUrl()}/woo/builder`)
        builderUrl.searchParams.append('domain', window.location.host)
        builderUrl.searchParams.append('product', window.GangSheetOptions.product_id)
        builderUrl.searchParams.append('variant', variant_id)
        builderUrl.searchParams.append('quantity', quantity)

        if (window.GangSheetOptions.customer?.id) {
            builderUrl.searchParams.append('customer_id', window.GangSheetOptions.customer.id)
        }

        const iframe = document.createElement('iframe')
        iframe.src = builderUrl.toString()
        gsBuilderModal.appendChild(iframe)

        document.body.style.overflow = 'hidden'
        document.querySelector('html').style.overflow = 'hidden'
        window.addEventListener('beforeunload', beforeTabClose)
    })

    return gsBuilderButton
}

document.addEventListener('DOMContentLoaded', function () {

    // Remove Add To Cart Button
    const addToCartButton = document.querySelector('.single_add_to_cart_button:not(#gang-sheet-builder-button)')
    if (addToCartButton) {
        addToCartButton.remove()
    }

    if (window.GangSheetOptions) {

        const builderButton = buildGangSheetButton()

        window.addEventListener('message', async (e) => {
            let data = e.data
            if (data && data.action) {
                if (data.action === 'gs_close_builder') {
                    closeBuilderModal()
                }

                let cartPageUrl = '/cart'

                if (data.action === 'gs_add_to_cart') {

                    if (!data.create_new_sheet) {
                        window.removeEventListener('beforeunload', beforeTabClose)
                    }

                    let form = getGsProductForm(builderButton)

                    async function addToCartOne(data) {
                        return new Promise(resolve => {
                            const item = {
                                action: 'gang_sheet_add_to_cart',
                                product_id: window.GangSheetOptions.product_id,
                                variation_id: data.variant_id,
                                variation_title: data.variant_title,
                                quantity: data.quantity,
                                gs_design_id: data.design_id,
                                product_type: data.product_type,
                                custom_price: data.custom_price
                            }

                            function completeFormData(form) {

                                let formData = new FormData()

                                if (form) {
                                    formData = new FormData(form)
                                }

                                formData.append('gs_design_id', item.gs_design_id)

                                if (formData.has('quantity')) {
                                    formData.set('quantity', item.quantity)
                                } else {
                                    formData.append('quantity', item.quantity)
                                }

                                if (formData.has('attribute_size')) {
                                    formData.set('attribute_size', item.variation_title)
                                } else {
                                    formData.append('attribute_size', item.variation_title)
                                }

                                if (formData.has('variation_id')) {
                                    formData.set('variation_id', item.variation_id)
                                } else {
                                    formData.append('variation_id', item.variation_id)
                                }

                                if (formData.has('product_id')) {
                                    formData.set('product_id', item.product_id)
                                } else {
                                    formData.append('product_id', item.product_id)
                                }

                                if (formData.has('product_type')) {
                                    formData.set('product_type', item.product_type)
                                } else {
                                    formData.append('product_type', item.product_type)
                                }

                                if (formData.has('custom_price')) {
                                    formData.set('custom_price', item.custom_price)
                                } else {
                                    formData.append('custom_price', item.custom_price)
                                }

                                if (data.submitGangSheetHeight) {
                                    formData.set('gang_sheet_height', data.actualHeightLabel)
                                } else {
                                    formData.append('gang_sheet_height', data.actualHeightLabel)
                                }

                                return formData
                            }

                            function handleFormSubmitError() {
                                alert('Something went wrong')
                                window.removeEventListener('beforeunload', beforeTabClose)
                                window.location.reload()
                            }

                            if (form && form.action) {

                                const formData = completeFormData(form)

                                fetch(form.action, {
                                    method: 'POST',
                                    body: formData
                                }).then(response => {
                                    if (!response.ok) {
                                        handleFormSubmitError()

                                        return null
                                    }

                                    return response.text()
                                }).then((response) => {
                                    if (response) {
                                        cartPageUrl = GangSheetOptions.cart_url || '/cart'
                                        resolve(true)
                                    } else {
                                        resolve(false)
                                    }
                                }).catch(() => {
                                    resolve(false)
                                })

                            } else {
                                const formData = completeFormData(form)

                                formData.append('action', item.action)

                                const addCartUrl = window.wc_add_to_cart_params?.ajax_url || '/wp-admin/admin-ajax.php'

                                fetch(addCartUrl, {
                                    method: 'POST',
                                    body: formData
                                }).then(response => {
                                    if (!response.ok) {
                                        handleFormSubmitError()

                                        return null
                                    }

                                    return response.json()
                                }).then(response => {
                                    if (response) {
                                        if (response.success) {
                                            cartPageUrl = response.data.cart_url
                                            resolve(true)
                                        } else {
                                            handleFormSubmitError()
                                            resolve(false)
                                        }
                                    }
                                }).catch(error => {
                                    console.error('Fetch error:', error)
                                    resolve(false)
                                })
                            }
                        })
                    }

                    if (data.submit_type === 'bulk' && (data.designs || []).length) {
                        for (const design of data.designs) {
                            await addToCartOne(design)
                        }
                    } else {
                        await addToCartOne(data)
                    }

                    if (!data.create_new_sheet) {
                        window.location.href = cartPageUrl
                    }
                }
            }
        }, false)

    }
}, false)
