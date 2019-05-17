import axios from 'axios'

const gsbApi = axios.create({
    baseURL: window.gs_api_base_url
})

gsbApi.interceptors.request.use(
    function (config) {

        const accessToken = window.gs_access_token
        if (accessToken) {
            config.headers['Authorization'] = `Bearer ${accessToken}`
        }

        config.headers['Content-Type'] = 'application/json'

        return config
    }
)

export const getShop = async (data) => {
    return await gsbApi.get('shop', data)
}

export const getShopOrders = async (data = {}) => {

    const searchParams = new URLSearchParams()
    for (const key in data) {
        searchParams.append(key, data[key])
    }

    return await gsbApi.get('orders' + (searchParams.toString() ? '?' + searchParams.toString() : ''))
}

export const getDesignStatus = async (design_id) => {
    return await gsbApi.get('design/' + design_id + '/status')
}

export const getDesignDownloadUrl = async (design_id) => {
    const searchParam = new URLSearchParams()

    if (window.GangSheetOptions.order_id) {
        searchParam.append('order_id', window.GangSheetOptions.order_id)
    }

    return await gsbApi.get('design/' + design_id + '/download?' + searchParam.toString())
}

export const getDesign = async (design_id) => {
    return await gsbApi.get('design/' + design_id)
}

export const updateStoreSettings = async (data) => {
    return await gsbApi.post('shop/settings', data)
}

export const getStripeCheckoutUrl = async (data) => {
    return await gsbApi.post('shop/add-credit', data)
}

export const getShopPayments = async () => {
    return await gsbApi.get(`payments`)
}

export const getLoginMagicLink = async (data) => {
    const searchParams = new URLSearchParams()
    for (const key in data) {
        searchParams.append(key, data[key])
    }

    return await gsbApi.get(`shop/login-magiclink?` + searchParams.toString())
}

export const getShopDesigns = async (data) => {

    const searchParams = new URLSearchParams()
    for (const key in data) {
        searchParams.append(key, data[key])
    }

    return await gsbApi.get('designs?' + searchParams.toString())
}

export const generateDesign = async (design_id) => {
    return await gsbApi.post(`design/${design_id}/generate`)
}

export const getProducts = async () => {
    return await gsbApi.get('products')
}

export const updateProduct = async (data) => {
    return await gsbApi.post('product', data)
}

export const cacheProduct = async (product_id) => {
    return await gsbApi.post('product', product_id)
}
