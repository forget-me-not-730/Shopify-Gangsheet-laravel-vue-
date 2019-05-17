import axios from 'axios'

const wooWebsiteUrl = window.gs_shop?.website || window.gs_website

const wooApi = axios.create({
    baseURL: `${wooWebsiteUrl}/wp-json/gang_sheet/v1/`,
    headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate'
    }
})

wooApi.interceptors.request.use(
    function (config) {
        config.headers['Authorization'] = `Bearer ${window.gs_access_token}`
        config.headers['X-GS-Token'] = `Bearer ${window.gs_access_token}`
        return config
    }
)

export const updateShopSettings = async (data) => {
    return await wooApi.post('settings', data)
}

export const getProducts = async () => {
    return await wooApi.get('products')
}

export const updateProduct = async (product_id, data) => {
    return await wooApi.post(`product/${product_id}`, data)
}

export const upgradeVersion = async () => {
    return await wooApi.post('upgrade')
}
