/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios'
import lodash from 'lodash'
import Toast from 'izitoast'
import 'izitoast/dist/css/iziToast.css'
import NProgress from 'nprogress'

window.axios = axios
window.axios.interceptors.request.use(
    function (config) {
        config.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

        if (window.shopUUID) {
            config.headers['x-shop-uuid'] = window.shopUUID
        }

        return config
    },
    null,
    {
        synchronous: true
    },
)

axios.interceptors.response.use((res) => {
    if (res.data && res.data.flash) {
        const onAxiosSuccess = new CustomEvent('onAxiosSuccess', {
            detail: res.data.flash
        })
        window.dispatchEvent(onAxiosSuccess)
    }
    return res
}, function (error) {
    const onAxiosError = new CustomEvent('onAxiosError', {
        detail: error
    })
    window.dispatchEvent(onAxiosError)

    if (error.response?.status === 419) {
        if (window.top === window.self) {
            window.location.reload()
        } else {
            window.top.location.reload()
        }
    }

    if (error.response?.status === 401) {
        window.location.href = '/'
    }

    throw error
})

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.withCredentials = true

window._ = lodash

Toast.settings({
    position: 'topRight',
})
window.Toast = Toast

window.NProgress = NProgress
