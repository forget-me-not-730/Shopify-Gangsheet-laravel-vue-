import {getSessionId} from "@/Builder/Utils/helpers";

export const saveDraftDesign = async (data) => {
    return new Promise((resolve) => {
        window.axios.post(route('builder.save-draft-design'), data).then(res => {
            resolve(res.data)
        }).catch(() => {
            resolve(false)
        })
    })
}

export const removeBackground = async (image) => {
    return new Promise((resolve) => {
        window.axios.post(route('builder.remove-background'), {
            image: image
        }).then(res => {
            resolve(res.data)
        }).catch(() => {
            resolve(false)
        })
    })
}

export const removeBgAndUpload = async (data) => {
    return new Promise((resolve) => {
        window.axios.post(route('builder.removebg-upload'), data
        ).then(res => {
            resolve(res.data)
        }).catch(() => {
            resolve(false)
        })
    })
}

export const getDesign = async (designId) => {
    return new Promise((resolve) => {
        window.axios.get(route('builder.shop-design', {design_id: designId})).then(res => {
            if (res.data.success) {
                resolve(res.data.design)
            } else {
                resolve(null)
            }
        }).catch(() => {
            resolve(null)
        })
    })
}

export const uploadBase64Image = async (data) => {
    return new Promise((resolve) => {
        window.axios.post(route('builder.upload-base64'), data).then(res => {
            resolve(res.data)
        }).catch(() => {
            resolve(false)
        })
    })
}

export const deleteCustomerImages = async (image_ids) => {
    return new Promise((resolve) => {
        window.axios.delete(route('builder.delete-customer-images'), {data: {image_ids}}).then(res => {
            resolve(res.data)
        }).catch(() => {
            resolve(false)
        })
    })
}

export const getCustomer = async (customerId, userId, customerEmail) => {
    return new Promise((resolve) => {
        window.axios.get(route('builder.get-customer', {
            customer_id: customerId,
            user_id: userId,
            customer_email: customerEmail
        })).then(res => {
            if (res.data.success) {
                resolve(res.data.customer)
            } else {
                resolve(null)
            }
        }).catch(() => {
            resolve(null)
        })
    })
}

export const getCustomerSession = async () => {
    return new Promise((resolve) => {
        window.axios.get(route('builder.get-customer-session', {session_id: getSessionId()})).then(res => {
            if (res.data.success) {
                resolve(res.data.customer)
            } else {
                resolve(null)
            }
        }).catch(() => {
            resolve(null)
        })
    })
}

export const getImageContour = async (imageUrl) => {
    return new Promise((resolve) => {
        axios.post(route('builder.contour'), {
            image_url: imageUrl
        }).then((res) => {
            if (res.data.success) {
                resolve(res.data.path)
            } else {
                resolve(null)
            }
        }).catch(() => {
            resolve(null)
        })
    })
}

export const logout = async () => {
    return new Promise((resolve) => {
        axios.post(route('builder.logout')).then((res) => {
            if (res.data.success) {
                resolve(true)
            } else {
                resolve(false)
            }
        }).catch(() => {
            resolve(false)
        })
    })
}

export const getDieCutPath = async (data) => {
    return new Promise((resolve) => {
        axios.post(route('builder.contour'), data).then((res) => {
            if (res.data.success) {
                resolve(res.data.path)
            } else {
                resolve(null)
            }
        }).catch(() => {
            resolve(null)
        })
    })
}

export const getCanvaAuthToken = async (params = {}) => {
    return new Promise((resolve) => {
        params['session_id'] = getSessionId()
        axios.post(route('canva-auth.authorize-url', params)).then((res) => {
            if (res.data.success) {
                resolve(res.data)
            } else {
                resolve(null)
            }
        }).catch(() => {
            resolve(null)
        })
    })
}

export const disconnectCanvaAccount = async (params) => {
    params['session_id'] = getSessionId()
    return new Promise((resolve) => {
        window.axios.post(route('canva-auth.disconnect'), params).then(res => {
            resolve(res.data);
        }).catch(() => {
            resolve(false);
        });
    });
}

export const getGoogleDriveAuthToken = async (params = {}) => {
    return new Promise((resolve) => {
        params['session_id'] = getSessionId()
        axios.post(route('google-auth.authorize-url', params)).then((res) => {
            if (res.data.success) {
                resolve(res.data)
            } else {
                resolve(null)
            }
        }).catch(() => {
            resolve(null)
        })
    })
}

export const disconnectGoogleDriveAccount = async (params) => {
    params['session_id'] = getSessionId()
    return new Promise((resolve) => {
        window.axios.post(route('google-auth.disconnect'), params).then(res => {
            resolve(res.data);
        }).catch(() => {
            resolve(false);
        });
    });
}
