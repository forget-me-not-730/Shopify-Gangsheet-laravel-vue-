import {v4 as uuidv4} from 'uuid'
import {FILETYPE_MAP} from './constants'

export const getBase64FromUrl = async (url) => {
    try {
        const data = await fetch(url)
        const blob = await data.blob()
        return new Promise((resolve) => {
            const reader = new FileReader()
            reader.readAsDataURL(blob)
            reader.onloadend = () => {
                const base64data = reader.result
                resolve(base64data)
            }

            reader.onerror = () => {
                resolve(false)
            }
        })
    } catch (e) {
        return false
    }
}

export const getPixelSize = (unit = 'inch') => {

    if (!unit) {
        unit = 'in'
    }

    if (unit === 'inch' || unit === 'in') {
        return 300
    }

    if (unit === 'mm') {
        return 11.81102362204724
    }

    if (unit === 'cm') {
        return 118.1102362204724
    }

    return 1
}

export const getFileFromBase64Image = (imageSrc) => {
    return new Promise((resolve) => {
        fetch(imageSrc)
            .then(res => res.blob())
            .then(async blob => {
                const file = new File([blob], 'File name', {type: 'image/png'})
                resolve(file)
            }).catch(() => {
            resolve(false)
        })
    })
}

export const bigJsonStringify = (data) => {
    let objectsJson = ''
    for (let i = 0; i < data.objects.length; i++) {
        if (i === data.objects.length - 1) {
            objectsJson += JSON.stringify(data.objects[i])
        } else {
            objectsJson += JSON.stringify(data.objects[i]) + ','
        }
    }
    data.objects = []
    return JSON.stringify(data).replace('"objects":[]', `"objects":[${objectsJson}]`)
}

export const hexToRgb = (color) => {
    let _color = color.replace(/ /g, '')
    _color = _color.charAt(0) === '#' ? _color.substring(1, 7) : _color
    const r = parseInt(_color.substring(0, 2), 16) // hexToR
    const g = parseInt(_color.substring(2, 4), 16) // hexToG
    const b = parseInt(_color.substring(4, 6), 16) // hexToB

    return {
        r, g, b
    }
}

export const getSessionId = () => {
    let sessionId = localStorage.getItem('gbs-session-id')

    if (!sessionId) {
        sessionId = uuidv4()
        localStorage.setItem('gbs-session-id', sessionId)
    }

    return sessionId
}

export const clearSessionId = () => {
    localStorage.removeItem('gbs-session-id')
}

export const waitForWebfonts = async (fonts, callback) => {

    // Function to load a single font
    const loadFont = (font) => {
        return new Promise((resolve, reject) => {
            const fontUrl = `url(${font.url})`.replace(/ /g, '%20')
            const webFont = new FontFace(font.name, fontUrl, {
                style: font.style,
                weight: font.weight,
            })

            webFont.load().then((fontFace) => {
                const fontSet = document.fonts.add(fontFace)
                // fontSet is undefined in Firefox
                if (fontSet) {
                    fontSet.load('40px ' + font.name).then(() => {
                        resolve(true)
                    }).catch((error) => {
                        reject(error)
                    })
                } else {
                    resolve(true)
                }
            }).catch((error) => {
                reject(error)
            })
        })
    }

    // Attempt to load each font, with retries
    const loadFontWithRetry = (font, retries = 3) => {
        return new Promise((resolve) => {
            const attemptLoad = (attemptsLeft) => {
                loadFont(font).then(() => {
                    if (typeof callback === 'function') {
                        callback(font)
                    }
                    resolve(true)
                }).catch((error) => {
                    console.log(error)
                    if (attemptsLeft > 0) {
                        attemptLoad(attemptsLeft - 1)
                    } else {
                        if (typeof callback === 'function') {
                            callback(null)
                        }
                        resolve(false)
                    }
                })
            }

            attemptLoad(retries)
        })
    }

    await Promise.all((fonts || []).map((font) => loadFontWithRetry(font)))

    await document.fonts.ready
}

export const getSearchParams = () => {
    const gsUrlSearchParams = new URLSearchParams(window.location.search)
    return Object.fromEntries(gsUrlSearchParams.entries())
}

export const hashString = (str) => {
    let hash = 0, chr, i, len

    for (i = 0, len = str.length; i < len; i++) {
        chr = str.charCodeAt(i)
        hash = ((hash << 5) - hash) + chr
        hash |= 0
    }

    return hash.toString()
}

export const clearStorageDesignForVariant = (variant_id) => {
    if (variant_id) {
        localStorage.removeItem('GangSheetDesign_' + variant_id)
    } else {
        for (let i = localStorage.length - 1; i >= 0; i--) {
            const key = localStorage.key(i)
            if (key.startsWith('GangSheetDesign_')) {
                localStorage.removeItem(key)
            }
        }
    }
}

export const convertDimension = (dimension, from, to = 'px', factional = 2) => {
    if (dimension) {
        const pixel = Number(dimension) * getPixelSize(from)
        let result = 0

        if (!to) {
            to = 'px'
        }

        if (to === 'in' || to === 'inch') {
            result = pixel / 300
        }

        if (to === 'mm') {
            result = pixel / 11.81102362204724 // 1 inch = 25.4 mm
        }

        if (to === 'cm') {
            result = pixel / 118.1102362204724 // 1 inch = 2.54 cm
        }

        if (result === 0) {
            result = pixel
        }

        if (to === 'px') {
            factional = 0
        }

        return Number(result.toFixed(factional))
    }

    return 0
}

export const getImageDimensions = async (src) => {
    return new Promise(resolve => {
        const img = new Image()

        img.onload = () => {
            resolve({
                width: img.width,
                height: img.height
            })
        }

        img.src = src
    })
}

export const formatFonts = (fonts, defaultFont) => {
    const formattedFonts = fonts.reduce((acc, font) => {
        const hasBold = font.weight === 'bold'
        const hasItalic = font.style === 'italic'

        const findIndex = acc.findIndex(f => f.name === font.name)

        if (findIndex > -1) {
            acc[findIndex].bold = acc[findIndex].bold || hasBold
            acc[findIndex].italic = acc[findIndex].italic || hasItalic
        } else {
            acc.push({
                name: font.name,
                bold: hasBold,
                italic: hasItalic
            })
        }

        return acc
    }, [])

    let defaultFontName = defaultFont

    if (formattedFonts.find(f => f.name === defaultFont) === undefined) {
        defaultFontName = formattedFonts[0]?.name
    }

    return [formattedFonts, defaultFontName]
}

export const getFileTypes = (allowedFileTypes) => {
    return allowedFileTypes.reduce((result, item) => {
        if (FILETYPE_MAP[item] && !result.includes(FILETYPE_MAP[item])) {
            result.push(FILETYPE_MAP[item])
        }
        return result
    }, [])
}

// Convert base64 image to a binary string
export const base64ImageToBinaryString = (imageBase64) => {
    const base64Data = imageBase64.split(',')[1]

    const binaryString = window.atob(base64Data)
    const len = binaryString.length
    const bytes = new Uint8Array(len)
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i)
    }
    return bytes
}

export const uint8ArrayToBase64 = (u8Arr) => {
    const CHUNK_SIZE = 0x8000
    let index = 0
    let length = u8Arr.length
    let result = ''
    let slice
    while (index < length) {
        slice = u8Arr.subarray(index, Math.min(index + CHUNK_SIZE, length))
        result += String.fromCharCode.apply(null, slice)
        index += CHUNK_SIZE
    }
    return btoa(result)
}

export const isValidHex6 = (color) => {
    const hex6Pattern = /^#?([A-Fa-f0-9]{6})$/;
    return hex6Pattern.test(color);
}

export const correctHex6 = (newColor) => {
    let color = newColor.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();

    if (color.length === 0) {
        return '#330000';
    }

    if (color.length === 3) {
        color = `#${color[0]}${color[0]}${color[1]}${color[1]}${color[2]}${color[2]}`;
    } else if (color.length === 4) {
        color = `#${color}00`;
    } else {
        color = `#${color.padEnd(6, 'F').substr(0, 6)}`;
    }

    return color;
}

export const getSizeLabel = (design) => {
    const variant = design.data?.meta?.variant;
    if (variant) {
        if (variant.title || variant.label) {
            return variant.title || variant.label;
        }

        return `${variant.width}x${variant.height} ${variant.unit}`;
    }

    return design.size.label;
}

export const isValidEmail = (email) => {
    return /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,7})+$/.test(email)
}
