import {getPixelSize} from '@/Builder/Utils/helpers'

fabric.util.getDimensionsFromCoords = function (coords) {
    return {
        width: coords[1].x - coords[0].x,
        height: coords[3].y - coords[0].y
    }
}

fabric.util.makeImageFromUrl = (url, loadSVG = true) => {
    return new Promise((resolve) => {
        if (url && typeof url === 'string' && url.startsWith('https')) {
            const src = url.trim();
            if (fabric.util.isSvg(src) && loadSVG) {
                fabric.loadSVGFromURL(src, (objects, options) => {
                    const image = fabric.util.groupSVGElements(objects, options)

                    resolve(image)
                }, null, {
                    crossOrigin: 'Anonymous'
                })
            } else {
                fabric.Image.fromURL(src, (image) => {
                    resolve(image)
                }, {
                    crossOrigin: 'anonymous'
                })
            }
        } else {
            resolve(null)
        }
    })
}

fabric.util.makeTextFromPack = async (pack, options) => {
    const viewPort = options.viewPort

    const margin = options.margin
    const artBoardMargin = options.artBoardMargin
    const pixelSize = getPixelSize(options.unit)

    let width = (pack.width - margin) * pixelSize
    let height = (pack.height - margin) * pixelSize

    let left = pack.x * pixelSize + width / 2
    let top = pack.y * pixelSize + height / 2

    left += viewPort.left + artBoardMargin * pixelSize
    top += viewPort.top + artBoardMargin * pixelSize

    const textOptions = {
        left: left,
        top: top,
        scaleX: pack.scaleX,
        scaleY: pack.scaleY,
        angle: pack.rotate ? 90 : 0,
        fontFamily: pack.fontFamily,
        fill: pack.fill,
        backgroundColor: pack.backgroundColor,
        strokeWidth: pack.strokeWidth,
        strokeColor: pack.strokeColor,
        fontWeight: pack.fontWeight,
        fontStyle: pack.fontStyle,
        underline: pack.underline,
        textAlign: pack.textAlign
    }

    if (pack.type === 'n-text') {
        return new fabric.NText(pack.text, textOptions).setCoords()
    } else {
        return new fabric.IText(pack.text, textOptions).setCoords()
    }
}

fabric.util.makeImageFromPack = async (pack, options) => {
    const viewPort = options.viewPort

    const margin = options.margin
    const artBoardMargin = options.artBoardMargin
    const image = await fabric.util.makeImageFromUrl(pack.url, false)

    if (image) {
        let width = (pack.width - margin) * getPixelSize(options.unit)
        let height = (pack.height - margin) * getPixelSize(options.unit)

        let scaleX = width / image.width
        let scaleY = height / image.height

        if (pack.rotate) {
            scaleX = height / image.width
            scaleY = width / image.height
        }

        let left = (pack.x + (pack.width - margin) / 2) * getPixelSize(options.unit)
        let top = (pack.y + (pack.height - margin) / 2) * getPixelSize(options.unit)

        left += viewPort.left + (artBoardMargin - margin / 2) * getPixelSize(options.unit)
        top += viewPort.top + (artBoardMargin - margin / 2) * getPixelSize(options.unit)

        image.set({
            id: pack.imageId,
            parentId: pack.parentId,
            isGalleryImage: pack.isGalleryImage,
            mimeType: pack.mimeType,
            scaleX: scaleX,
            scaleY: scaleY,
            left: left,
            top: top,
            rotate: pack.rotate,
            src: pack.url,
            url: pack.originalUrl,
            angle: pack.rotate ? 90 : 0,
            realWidth: pack.realWidth,
            realHeight: pack.realHeight
        }).setCoords()

        if (image.type === 'group') {
            image.set('group_type', 'svg')
        }

        return image
    }

    return null
}

fabric.util.makeObjectFromPack = async (pack, options) => {
    if (pack.type.includes('text')) {
        return fabric.util.makeTextFromPack(pack, options)
    } else {
        return fabric.util.makeImageFromPack(pack, options)
    }
}

fabric.util.makeViewPort = (center, designWidth, designHeight, offsetY = 0) => {
    const viewPort = {
        centerX: center.left,
        centerY: center.top + offsetY / 2,
        width: designWidth,
        height: designHeight
    }

    viewPort.left = viewPort.centerX - viewPort.width / 2
    viewPort.top = viewPort.centerY - viewPort.height / 2
    viewPort.right = viewPort.centerX + viewPort.width / 2
    viewPort.bottom = viewPort.centerY + viewPort.height / 2

    return viewPort
}

// https://jsfiddle.net/w8r/9rnnk545/
fabric.util.computeBoundingBox = (rect) => {
    function getCorner(pivotX, pivotY, cornerX, cornerY, angle) {

        let x, y, distance, diffX, diffY

        /// get distance from center to point
        diffX = cornerX - pivotX
        diffY = cornerY - pivotY
        distance = Math.sqrt(diffX * diffX + diffY * diffY)

        /// find angle from pivot to corner
        angle += Math.atan2(diffY, diffX)

        /// get new x and y and round it off to integer
        x = pivotX + distance * Math.cos(angle)
        y = pivotY + distance * Math.sin(angle)

        return {x: x, y: y}
    }

    // variables
    let px = rect.centerX,      // pivot
        py = rect.centerY,

        ar = (rect.angle ?? 0) * Math.PI / 180, // angle in radians

        c1, c2, c3, c4 = {},    // corners
        bx1, by1, bx2, by2 // bounding box

    // get corner coordinates
    c1 = getCorner(px, py, rect.left, rect.top, ar)
    c2 = getCorner(px, py, rect.right, rect.top, ar)
    c3 = getCorner(px, py, rect.right, rect.bottom, ar)
    c4 = getCorner(px, py, rect.left, rect.bottom, ar)

    // get bounding box
    bx1 = Math.min(c1.x, c2.x, c3.x, c4.x)
    by1 = Math.min(c1.y, c2.y, c3.y, c4.y)
    bx2 = Math.max(c1.x, c2.x, c3.x, c4.x)
    by2 = Math.max(c1.y, c2.y, c3.y, c4.y)

    return {
        left: bx1,
        right: bx2,
        top: by1,
        bottom: by2,
        width: bx2 - bx1,
        height: by2 - by1,
        centerX: rect.centerX,
        centerY: rect.centerY
    }
}

fabric.util.getMarginRect = (rect, margin) => {

    rect.left = rect.left - margin / 2
    rect.right = rect.right + margin / 2
    rect.top = rect.top - margin / 2
    rect.bottom = rect.bottom + margin / 2
    rect.width = rect.width + margin
    rect.height = rect.height + margin

    return rect
}

fabric.util.measureText = (ctx, text, fontFamily = 'Oswald', fontSize = 40) => {
    ctx.font = `${fontSize}px ${fontFamily}`
    const metrics = ctx.measureText(text)

    const height = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
    const width = metrics.actualBoundingBoxRight - metrics.actualBoundingBoxLeft

    return {width, height}
}

fabric.util.getResolutionFromObject = (obj, unit = 'in') => {
    const resolutionX = obj.realWidth / (obj.width * getPixelSize(unit) / 300)
    const resolutionY = obj.realHeight / (obj.height * getPixelSize(unit) / 300)

    return Math.round(Math.min(resolutionX, resolutionY))
}

fabric.util.getAutoNestObjectsFromDesigns = (designs) => {
    designs = Array.isArray(designs) ? designs : [designs]
    const objects = []

    for (const design of designs) {
        const unit = design.unit || design.meta?.variant?.unit || 'in'

        for (const obj of design.objects) {
            if (obj.isPattern)
                continue
            if (obj.src) {
                const src = obj.src.trim();

                const width = Number((obj.width * obj.scaleX / getPixelSize(unit)).toFixed(2))
                const height = Number((obj.height * obj.scaleY / getPixelSize(unit)).toFixed(2))

                const findIndex = objects.findIndex(_o => _o.url === src && Math.floor(Math.abs(_o.width - width)) === 0 && Math.floor(Math.abs(_o.height - height)) === 0)

                if (findIndex > -1) {
                    objects[findIndex].quantity++
                } else {
                    const newObject = {
                        id: obj.id,
                        imageId: obj.id,
                        isGalleryImage: obj.isGalleryImage,
                        parentId: obj.parentId,
                        mimeType: obj.mimeType,
                        type: obj.type,
                        quantity: 1,
                        url: src,
                        originalUrl: obj.url,
                        width: width,
                        height: height,
                        realWidth: obj.realWidth,
                        realHeight: obj.realHeight,
                        lockedAspectRatio: true
                    }

                    if (fabric.util.isSvg(obj.url)) {
                        newObject['resolution'] = 300
                    } else {
                        newObject['resolution'] = fabric.util.getResolutionFromObject(newObject, unit)
                    }

                    objects.push(newObject)
                }
            } else if (obj.text?.trim()) {
                const text = obj.text
                const fillColor = obj.fill

                const width = Number((obj.width * obj.scaleX / getPixelSize(unit)).toFixed(2))
                const height = Number((obj.height * obj.scaleY / getPixelSize(unit)).toFixed(2))

                const findIndex = objects.findIndex(_o => _o.text === text && _o.fill === fillColor && _o.fontFamily === obj.fontFamily && _o.scaleY === obj.scaleY)

                if (findIndex > -1) {
                    objects[findIndex].quantity++
                } else {
                    const newObject = {
                        id: text,
                        text: text,
                        type: obj.type,
                        quantity: 1,
                        width: width,
                        height: height,
                        scaleX: obj.scaleX,
                        scaleY: obj.scaleY,
                        fontSize: obj.fontSize,
                        fontFamily: obj.fontFamily,
                        lockedAspectRatio: true,
                        fill: obj.fill,
                        backgroundColor: obj.backgroundColor,
                        strokeWidth: obj.strokeWidth,
                        strokeColor: obj.stroke,
                        fontWeight: obj.fontWeight,
                        fontStyle: obj.fontStyle,
                        underline: obj.underline,
                        textAlign: obj.textAlign
                    }

                    objects.push(newObject)
                }
            }
        }
    }

    return objects;
}

fabric.util.getAutoNestRectsFromObjects = (objects, margin = 0) => {
    return objects.reduce((result, object) => {
        for (let i = 0; i < object.quantity; i++) {
            const r = {
                ...object,
                width: Number(object.width) + margin,
                height: Number(object.height) + margin
            }

            if (!object.type.includes('text')) {
                r.imageId = object.id
            }

            delete r.id
            delete r.quantity
            delete r.lockedAspectRatio

            result.push(r)
        }
        return result
    }, [])
}

fabric.util.isSvg = (url) => {
    if (url) {
        return url.trim().split('?')[0].endsWith('.svg')
    }
    return false
}
