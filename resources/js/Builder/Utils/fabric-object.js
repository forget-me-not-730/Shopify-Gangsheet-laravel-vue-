import {removeBackground} from '@/Builder/Apis/builderApi'
import {convertDimension, hashString} from '@/Builder/Utils/helpers'
import normalizeUrl from 'normalize-url'

const CORNER_SIZE = 16

fabric.Object.NUM_FRACTION_DIGITS = 10

fabric.Object.prototype.originX = 'center'
fabric.Object.prototype.originY = 'center'

fabric.Object.prototype.cornerStrokeColor = 'gray'
fabric.Object.prototype.cornerColor = 'black'
fabric.Object.prototype.cornerStyle = 'circle'
fabric.Object.prototype.transparentCorners = false

fabric.Object.prototype.controls.mt.visible = false
fabric.Object.prototype.controls.ml.visible = false
fabric.Object.prototype.controls.mr.visible = false
fabric.Object.prototype.controls.mb.visible = false

fabric.Object.prototype.rockedRatio = true
fabric.Object.prototype.backgroundRemoved = false
fabric.Object.prototype.overlapping = false

fabric.Object.prototype.removingBackground = false

fabric.Object.prototype.realWidth = null
fabric.Object.prototype.realHeight = null

// for reference
fabric.Object.prototype.canvas = null
fabric.Object.prototype.borderColor = 'rgb(202,128,255)'
fabric.Object.prototype.editingBorderColor = 'rgba(202,128,255,0.5)'

// upload image attributes
fabric.Object.prototype.id = null
fabric.Object.prototype.parentId = null
fabric.Object.prototype.canvaId = null
fabric.Object.prototype.isGalleryImage = false

// config caching
fabric.Object.prototype.objectCaching = true
fabric.Object.prototype.statefullCache = false
fabric.Object.prototype.noScaleCache = false

// draw resolution lines
fabric.Object.prototype.getStrokeColor = function (hideTerribleResolution) {
    const {resolutionX, resolutionY} = this.getResolution()
    const dpi = Math.min(resolutionX, resolutionY)
    const showResolutionLines = this.canvas.showResolutionLines
    const resolutionLevels = this.canvas.resolutionLevels

    if (dpi > 0 && showResolutionLines && resolutionLevels) {
        let strokeColor = '#22c55e'

        if (dpi < resolutionLevels.optimal) {
            strokeColor = 'yellow'
        }

        if (dpi < resolutionLevels.good) {
            strokeColor = 'red'
        }

        if (!hideTerribleResolution) {
            if (dpi < resolutionLevels.bad) {
                strokeColor = 'black'
            }
        }

        return strokeColor
    }

    return null
}

fabric.Object.prototype.getRealWidth = function () {
    return this.realWidth ?? this.width
}

fabric.Object.prototype.getRealHeight = function () {
    return this.realHeight ?? this.height
}

fabric.Object.prototype.getResolution = function () {

    if (this.mimeType === 'image/svg+xml' || fabric.util.isSvg(this.getSourceUrl())) {
        return {
            resolutionX: 300,
            resolutionY: 300
        }
    }

    const dimension = this.getDimensions()

    return {
        resolutionX: Math.ceil(this.getRealWidth() / convertDimension(dimension.width, this.canvas.unit, 'in', 6)),
        resolutionY: Math.ceil(this.getRealHeight() / convertDimension(dimension.height, this.canvas.unit, 'in', 6))
    }
}

fabric.Object.prototype.getDimensions = function () {
    const pixelSize = this.canvas.getPixelSize()
    const rect = this.getRect()
    const viewport = this.canvas.getViewPort()

    return {
        left: (rect.left - viewport.left) / pixelSize,
        top: (rect.top - viewport.top) / pixelSize,
        width: rect.width / pixelSize,
        height: rect.height / pixelSize,
        textHeight: this.maxLineHeight / pixelSize
    }
}

fabric.Object.prototype.getRect = function () {
    let rect = {
        centerX: this.left,
        centerY: this.top,
        width: this.width * this.scaleX,
        height: this.height * this.scaleY
    }

    rect.left = rect.centerX - rect.width / 2
    rect.top = rect.centerY - rect.height / 2
    rect.right = rect.left + rect.width
    rect.bottom = rect.top + rect.height
    rect.angle = this.angle ?? 0

    return rect
}

fabric.Object.prototype.getOriginRect = function () {
    const rect = this.getRect()
    const zoom = this.canvas.getZoom()
    for (const key in rect) {
        rect[key] *= zoom
    }

    return rect
}

fabric.Object.prototype.getSourceUrl = function () {
    let url = this.url || this.src

    if (!url && typeof this.getSrc === 'function') {
        url = this.getSrc()
    }

    return url
}

fabric.Object.prototype.getHashIdentifier = function () {

    let str = ''

    if (this.type === 'text' || this.type === 'i-text') {
        str += this.text
        str += this.fontFamily
    } else {
        str += this.getSourceUrl()
    }

    return hashString(str)
}

fabric.Object.prototype.getBoundaryRect = function () {
    if (this.angle === 0) {
        return this.getRect()
    }
    return fabric.util.computeBoundingBox(this.getRect())
}

fabric.Object.prototype.getBoundaryOutlineRect = function (withMargin = false) {
    if (withMargin) {
        return fabric.util.computeBoundingBox(this.getOutlineRect())
    } else {
        return fabric.util.computeBoundingBox(this.getRect())
    }
}

// returns object's bounding area including margin
fabric.Object.prototype.getOutlineRect = function () {
    const rect = this.getRect()
    const margin = this.canvas.getViewPortMargin()

    return fabric.util.getMarginRect(rect, margin)
}

// https://codepen.io/dziul/pen/ZEQbJKy
fabric.Object.prototype.removeBackground = function () {
    return new Promise(async (resolve) => {
        const imageSrc = this.getSrc()
        this.removingBackground = true
        const res = await removeBackground(imageSrc)
        if (res && res.success) {
            const url = normalizeUrl(res.url)
            await fetch(url, {cache: 'reload', mode: 'no-cors'})
            this.setSrc(url, async () => {
                this.set('backgroundRemoved', true)
                resolve(url)
                this.removingBackground = false
                this.canvas.fire('background:removed', this)

                for (const obj of this.canvas._objects) {
                    if (normalizeUrl(obj.getSrc()) === url) {
                        await obj.setSrc(url)
                    }
                }

                this.canvas.renderAll()
            })
        } else {
            this.removingBackground = false
            console.error(res.error)
            resolve(null)
        }
    })
}

fabric.Object.prototype.overlapsWithOther = function (other, absolute, calculate) {
    const c1 = this.getCoords(absolute, calculate)
    const c2 = other.getCoords(absolute, calculate)

    if (c1.every((p, i) => Math.floor(p.x) === Math.floor(c2[i].x) && Math.floor(p.y) === Math.floor(c2[i].y))) {
        return true
    }

    let mi = Math.floor(this.angle / 90)
    mi = (4 - mi) % 4

    c1[mi % 4].x += 0.0001
    c1[mi % 4].y += 0.0001

    c1[(mi + 1) % 4].x -= 0.0001
    c1[(mi + 1) % 4].y += 0.0001

    c1[(mi + 2) % 4].x -= 0.0001
    c1[(mi + 2) % 4].y -= 0.0001

    c1[(mi + 3) % 4].x += 0.0001
    c1[(mi + 3) % 4].y -= 0.0001

    const intersection = fabric.Intersection.intersectPolygonPolygon(c1, c2)

    return intersection.status === 'Intersection'
        || other.isContainedWithinObject(this, absolute, calculate)
        || this.isContainedWithinObject(other, absolute, calculate)
}

fabric.Object.prototype.overlapsWithOthers = function () {
    for (const obj of this.canvas._objects) {
        if (obj === this) continue

        if (this.overlapsWithOther(obj)) {
            return true
        }
    }

    return false
}
