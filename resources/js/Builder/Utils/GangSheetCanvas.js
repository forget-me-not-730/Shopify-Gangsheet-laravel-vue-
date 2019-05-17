import {fabric} from 'fabric'
import {v4 as uuidv4} from 'uuid'
import {convertDimension} from '@/Builder/Utils/helpers'
import {BUILD_TYPES, EXTRA_PROPERTIES} from '@/Builder/Utils/constants'
import './fabric-group'
import './fabric-util'
import './fabric-history'
import './fabric-object'
import './fabric-path'
import './fabric-image'
import './fabric-itext'
import './fabric-ntext'
import './fabric-canvas'
import {cloneDeep} from "lodash";

fabric.textureSize = 20480

fabric.Canvas.prototype.extraProps = EXTRA_PROPERTIES

const DEFAULT_MARGIN_INCH = 0.125

fabric.Canvas.prototype.marginEnabled = true
fabric.Canvas.prototype.artboardMarginEnabled = true
/**
 * spacing around all four sides of the image.
 * @type {number}
 */
fabric.Canvas.prototype.margin = DEFAULT_MARGIN_INCH

/**
 * spacing from the edge of the artboard to the image.
 * @type {number}
 */
fabric.Canvas.prototype.artboardMargin = DEFAULT_MARGIN_INCH

fabric.Canvas.prototype.meta = {}
fabric.Canvas.prototype.designWidth = 0
fabric.Canvas.prototype.designHeight = 0
fabric.Canvas.prototype.name = 'New Gang Sheet'
fabric.Canvas.prototype.quantity = 1
fabric.Canvas.prototype.build_type = BUILD_TYPES.GANG_SHEET
fabric.Canvas.prototype.showResolutionLines = true
fabric.Canvas.prototype.hideTerribleResolution = false
fabric.Canvas.prototype.showMarginOutlines = true
fabric.Canvas.prototype.hasOverlapping = false
fabric.Canvas.prototype.gridMode = false
fabric.Canvas.prototype._clipboard = null
fabric.Canvas.prototype.autoResizeSingleImage = true
fabric.Canvas.prototype.isReady = false
fabric.Canvas.prototype.imagesError = false
fabric.Canvas.prototype.artBoardError = false
fabric.Canvas.prototype.ensureOptimalResolution = true
fabric.Canvas.prototype.viewPortOffsetY = 0
fabric.Canvas.prototype.autoSize = false
fabric.Canvas.prototype.visualQuality = true
fabric.Canvas.prototype.fireRightClick = true
fabric.Canvas.prototype.enableFlipping = true
fabric.Canvas.prototype.resolutionLevels = {
    optimal: 300,
    good: 250,
    bad: 200
}

class GangSheetCanvas extends fabric.Canvas {

    images = []
    coordinatesX = []
    coordinatesY = []

    constructor(canvasId, options) {
        super(canvasId, options)

        this.canvasId = canvasId
        this.options = options
        this.isMoving = false
        this.ready = this.initializeGangSheetCanvas(options)
    }

    setGridMode(gridMode) {
        this.gridMode = Boolean(gridMode)
    }

    setCoordinatesX(coordinatesX) {
        this.coordinatesX = coordinatesX
    }

    setCoordinatesY(coordinatesY) {
        this.coordinatesY = coordinatesY
    }

    setMargin(margin) {
        this.margin = Number(margin)
        this.clearMarginOverlapping()
        this.renderAll()
    }

    setArtboardMargin(artboardMargin) {
        this.artboardMargin = Number(artboardMargin)
        this.clearMarginOverlapping()
        this.renderAll()
    }

    setImages(images) {
        this.images = cloneDeep(images)
    }

    setBuildType(build_type) {
        this.build_type = build_type
    }

    updateDesignHeight(height) {
        this.variant.height = height

        const newDesignHeight = convertDimension(height, this.unit, 'px')
        this.viewPortOffsetY += newDesignHeight - this.designHeight

        this.designHeight = newDesignHeight

        this.addClipPath()

        this.fire('design:height:change')
    }

    _addImage(image) {
        this.images.push(image)
    }

    _removeImage(image) {
        const index = this.images.findIndex(im => im.id === image.id)
        if (index > -1) {
            this.images.splice(index, 1)
        }
    }

    toggleMarginEnabled(enable) {
        if (enable === undefined) {
            this.marginEnabled = !this.marginEnabled
        } else {
            this.marginEnabled = Boolean(enable)
        }
        this.renderAll()
        this.clearMarginOverlapping()
    }

    toggleArtboardMarginEnabled(enable) {
        if (enable === undefined) {
            this.artboardMarginEnabled = !this.artboardMarginEnabled
        } else {
            this.artboardMarginEnabled = Boolean(enable)
        }
        this.renderAll()
        this.clearMarginOverlapping()
    }

    toggleSnapEnabled(enable) {
        this.snapEnabled = Boolean(enable)
    }

    parse(number) {
        return number * this.getPixelSize()
    }

    handleBuildTypeChange() {
        if (this.build_type === BUILD_TYPES.AUTO_BUILD) {
            this.build_type = BUILD_TYPES.MIXED
        }
    }

    async initializeGangSheetCanvas(options) {
        return new Promise((resolve) => {

            const canvas = this

            this.designWidth = options.designWidth
            this.designHeight = options.designHeight
            this.printPattern = options.printPattern
            this.patternMode = options.patternMode

            if (options.zoom) {
                this.setZoomByCenter(options.zoom)
            }

            if (options.jsonData) {
                options = _.merge({}, options.jsonData.meta, options)
            }

            if (options.gridMode) {
                this.gridMode = options.gridMode
            }

            if (options.variant) {
                this.variant = {
                    id: options.variant.id,
                    width: options.variant.width,
                    height: options.variant.height,
                    unit: options.variant.unit,
                    price: options.variant.price,
                    title: options.variant.title || options.variant.label,
                    maxAllowedFileCount: options.variant.maxAllowedFileCount,
                    pattern: options.variant.pattern
                }
            }

            if (options.images) {
                this.images = options.images || []
            }

            if (options.build_type) {
                this.build_type = options.build_type
            }

            if (options.images) {
                this.images = options.jsonData.meta.images.map((image, index) => {
                    if (typeof image === 'string') {
                        return {
                            id: new Date().getTime() + index,
                            url: image
                        }
                    }
                    return image
                })
            }

            // add mask
            this.controlsAboveOverlay = true
            this.absolutePositioned = false
            this.autoScaleStrokeWidth = true

            const removeEmptyTextObject = (textObjects) => {

                const checkTextObject = (object) => {
                    if (object && object.type.includes('text') && !object.text) {
                        this.remove(object)
                    }
                }

                if (Array.isArray(textObjects)) {
                    for (const object of textObjects) {
                        checkTextObject(object)
                    }
                } else {
                    checkTextObject(textObjects)
                }
            }

            const handleSelectionCleared = (e) => {
                this.fire('object:off-board', {status: false})
                this.fire('object:overlapping', {status: false})
                this.clearMarginOverlapping()
                removeEmptyTextObject(e.deselected)
            }

            const handleMoved = () => {
                canvas.checkObjectOverlapping()
                canvas.checkDesignOverlapping()
            }

            const handleObjectUpdated = () => {
                this.checkDesignOverlapping()
                this.checkObjectOverlapping()
            }

            const handleSelectionCreated = (e) => {
                this.checkDesignOverlapping()
                this.checkObjectOverlapping()
            }

            const handleSelectionUpdated = (e) => {
                this.checkDesignOverlapping()
                this.checkObjectOverlapping()
                removeEmptyTextObject(e.deselected)
            }

            const filterCharacters = (input) => {
                const emojiRegex = /([\u2700-\u27BF]|[\uE000-\uF8FF]|[\uD83C\uD000-\uDBFF\uDFFF]|[\u2011-\u26FF]|[\u2600-\u2B55])/gu;
                return input.replace(emojiRegex, '')
            }

            const handleBuildTypeChange = () => {
                if (this.build_type === BUILD_TYPES.AUTO_BUILD) {
                    this.build_type = BUILD_TYPES.MIXED
                }
            }

            this.on({
                'object:updated': handleObjectUpdated,
                'object:moving': (e) => {
                    if (!this.isMoving) {
                        this.isMoving = true
                        this.tempOpacity = e.target.opacity
                        e.target.opacity = 0.8
                    }
                    canvas.checkSnapObjects()
                    canvas.checkObjectOverlapping()
                    canvas.checkDesignOverlapping()
                },
                'object:moved': handleMoved,
                'object:scaling': (e) => {
                    e.target.set({
                        lockScalingFlip: !this.enableFlipping
                    })
                    canvas.checkObjectOverlapping()
                    canvas.checkDesignOverlapping()
                },
                'selection:created': handleSelectionCreated,
                'selection:cleared': handleSelectionCleared,
                'selection:updated': handleSelectionUpdated,
                'object:modified': function (e) {
                    handleBuildTypeChange()
                },
                'object:removed': handleBuildTypeChange,
                'text:changed': function (e) {
                    e.target.text = filterCharacters(e.target.text)
                    e.target.styles = {}
                    canvas.renderAll()
                },
                'object:rotating': function (e) {
                    // rotation snap
                    const snapAngle = 8
                    if (e.target.angle % 90 < snapAngle || e.target.angle % 90 > 90 - snapAngle) {
                        e.target.set('angle', Math.round(e.target.angle / 90) * 90)
                    }
                },
                'mouse:up': function (e) {
                    if (this.isMoving) {
                        this.isMoving = false
                        e.target.opacity = this.tempOpacity
                    }
                    if (e.button === 3) {
                        if (!e.target) {
                            this.discardActiveObject()
                            canvas.renderAll()
                            return;
                        }
                        this.setActiveObject(e.target)
                    }
                }
            })

            this.on('before:render', () => {
                this.hasLines = false
            })

            this.on('after:render', () => {
                if (!this.hasLines) {
                    this.hasLines = true

                    canvas.contextContainer.lineWidth = 1

                    const activeObject = this.getActiveObject()

                    const drawOutline = (obj) => {
                        this.contextContainer.beginPath()
                        const cords = obj.calcOCoords()
                        this.contextContainer.moveTo(cords.tl.x, cords.tl.y)
                        this.contextContainer.lineTo(cords.tr.x, cords.tr.y)
                        this.contextContainer.lineTo(cords.br.x, cords.br.y)
                        this.contextContainer.lineTo(cords.bl.x, cords.bl.y)
                        this.contextContainer.lineTo(cords.tl.x, cords.tl.y)
                        this.contextContainer.stroke()
                    }

                    this.forEachObject((obj) => {
                        if (obj?.isPattern)
                            return
                        if (obj.type === 'n-text') {
                            obj.setControlsVisibility({
                                mb: false,
                                mr: false,
                                ml: false,
                                mt: false,
                                bl: false,
                                br: false,
                                tl: false,
                                tr: false,
                                mtr: true,
                            })

                        } else if (!obj.type.includes('text')) {
                            if (obj.overlapping && this.showMarginOutlines) {
                                this.contextContainer.strokeStyle = 'blue'
                                drawOutline(obj)
                            } else if (this.showResolutionLines) {
                                if (activeObject?.type !== 'activeSelection' || !activeObject.contains(obj)) {
                                    if (obj.type === 'image' && !fabric.util.isSvg(obj.getSourceUrl())) {
                                        this.contextContainer.strokeStyle = obj.getStrokeColor(this.hideTerribleResolution)
                                    } else {
                                        this.contextContainer.strokeStyle = '#22c55e'
                                    }

                                    drawOutline(obj)
                                }
                            }
                        }
                    })
                }
            })

            if (options.jsonData) {
                const json = this.adaptJsonToCanvas(options.jsonData, options.visualQuality)
                this.loadFromJSON(json, () => {
                    this.addClipPath()
                    this.imagesError = options.jsonData.imagesError
                    this.artBoardError = options.jsonData.artBoardError
                    this.initializing = false
                    this._historyInit();

                    resolve(this)
                })
            } else {
                this.addClipPath()
                this.initializing = false

                resolve(this)
            }
        })
    }

    adaptJsonToCanvas(jsonData, visualQuality) {
        const newJson = {...jsonData}
        const oldViewPort = jsonData?.meta?.viewport ?? null

        if (oldViewPort) {
            const oldScale = jsonData.meta.scale ?? 1
            const viewPort = this.getViewPort()

            newJson.objects = jsonData.objects.reduce((r, o) => {
                if (o.type.endsWith('text') && !o.text) {
                    return r
                }

                const obj = {
                    ...o,
                    left: viewPort.left + (o.left - oldViewPort.left) / oldScale,
                    top: viewPort.top + (o.top - oldViewPort.top) / oldScale,
                    scaleX: o.scaleX / oldScale,
                    scaleY: o.scaleY / oldScale,
                }

                if (
                    obj.type === 'image' &&
                    obj.realWidth &&
                    obj.realHeight &&
                    !fabric.util.isSvg(obj.src) &&
                    !obj.url.includes('canva')
                ) {
                    const imageName = (obj.url || obj.src)?.split('/').pop()

                    if (imageName) {
                        if (visualQuality) {
                            if (obj.src.includes('thumb')) {
                                obj.thumb_url = obj.src
                            }
                            obj.src = obj.url || route('asset.image', {image_name: imageName})

                            obj.scaleX = obj.width * obj.scaleX / obj.realWidth
                            obj.scaleY = obj.height * obj.scaleY / obj.realHeight
                            obj.width = obj.realWidth
                            obj.height = obj.realHeight
                        } else {
                            if (obj.thumb_url) {
                                obj.src = route('asset.thumb-image', {image_name: imageName})

                                const thumbWidth = 300
                                const thumbHeight = obj.realHeight * thumbWidth / obj.realWidth

                                obj.scaleX = obj.width * obj.scaleX / thumbWidth
                                obj.scaleY = obj.height * obj.scaleY / thumbHeight
                                obj.width = thumbWidth
                                obj.height = thumbHeight
                            }
                        }
                    }
                }

                r.push(obj)

                return r
            }, [])
        }
        return newJson
    }

    getImageRectToPlace(image) {
        const viewport = this.getViewPort()
        const margin = this.getMarginPx()
        const artboardMargin = this.getArtboardMarginPx()

        let left = viewport.left + artboardMargin
        let top = viewport.top + artboardMargin

        const imageWidth = image.width * image.scaleX
        const imageHeight = image.height * image.scaleY

        function getImageRect(left, top, rotate = false) {
            const width = rotate ? imageHeight : imageWidth
            const height = rotate ? imageWidth : imageHeight

            return {
                left,
                top,
                width,
                height,
                right: left + width,
                bottom: top + height,
                centerX: left + width / 2,
                centerY: top + height / 2,
                rotate: rotate
            }
        }

        let startRect = getImageRect(left, top)

        if (this.isEmpty()) {
            if (!this.checkOverlapping(startRect)) {
                return startRect
            } else {
                let startRect = getImageRect(left, top, true)
                if (!this.checkOverlapping(startRect)) {
                    return startRect
                }
            }
            return startRect
        }

        let availableRect = null

        let points = []
        for (const object of this._objects) {
            const rect = object.getBoundaryRect()
            points = points.concat([
                {
                    left: rect.right + margin,
                    top: rect.top
                },
                {
                    left: rect.left,
                    top: rect.bottom + margin
                }
            ])
        }

        points = points.sort((a, b) => {
            if (Math.round(a.top) !== Math.round(b.top)) {
                return Math.round(a.top) - Math.round(b.top)
            } else {
                return Math.round(a.left) - Math.round(b.left)
            }
        })

        for (const point of points) {
            const imgRect = getImageRect(point.left, point.top)
            if (!this.checkOverlapping(imgRect)) {
                availableRect = imgRect
                break
            }

            const imgRectWithRotated = getImageRect(point.left, point.top, true)
            if (!this.checkOverlapping(imgRectWithRotated)) {
                availableRect = imgRectWithRotated
                break
            }
        }

        if (availableRect) {
            return availableRect
        }

        return startRect
    }

    addImage(image) {

        const maxFileCount = Number(this.variant.maxAllowedFileCount || -1)

        if (maxFileCount > 0) {
            if (this.size() >= maxFileCount) {
                if (maxFileCount === 1) {
                    window.Toast.error({
                        message: `This product allows only one image.`
                    })
                } else {
                    window.Toast.error({
                        message: `This product allows ${maxFileCount} images.`
                    })
                }
                return
            }
        }

        const imageId = image.id
        const parentId = image.parentId
        const originUrl = image.url
        const thumbUrl = image.thumb_url || image.url
        const isGalleryImage = image.isGalleryImage
        const enableColorOverlay = image.enableColorOverlay
        let mimeType = image.mimeType
        const realWidth = image.width
        const realHeight = image.height
        const resolution = image.resolution
        const canvaId = image.canvaId
        let extension = image.extension || originUrl.split('.').pop()

        let src = thumbUrl
        if (this.visualQuality) {
            src = originUrl
        }

        if (mimeType === 'image/svg+xml') {
            extension = 'svg'
        }

        if (!mimeType && extension === 'svg') {
            mimeType = 'image/svg+xml'
        }

        if (isGalleryImage && mimeType === 'image/svg+xml' && originUrl) {
            src = originUrl
        }

        fabric.util.makeImageFromUrl(src, false).then(image => {
            if (image) {
                const maxWidth = this.designWidth - this.getArtboardMarginPx() * 2
                const maxHeight = this.designHeight - this.getArtboardMarginPx() * 2

                if (maxFileCount === 1 && this.autoResizeSingleImage) {
                    image.scaleToWidth(maxWidth)
                } else {
                    if (extension === 'svg') {
                        image.set({
                            scaleX: 300 / 96,
                            scaleY: 300 / 96
                        })

                        if (image.getScaledWidth() < 150) {
                            image.scaleToWidth(150)
                        }

                        if (image.getScaledWidth() > maxWidth) {
                            image.scaleToWidth(maxWidth)
                        }

                        if (image.getScaledHeight() > maxHeight) {
                            image.scaleToHeight(maxHeight)
                        }
                    } else {
                        if (!this.ensureOptimalResolution && typeof resolution === 'number' && resolution > 0) {
                            image.scaleToWidth(Math.min(maxWidth, Math.round(realWidth * 300 / resolution)))
                        } else {
                            image.scaleToWidth(Math.min(maxWidth, realWidth))
                        }
                    }
                }

                const imageRect = this.getImageRectToPlace(image)

                if (!imageRect) return

                image.set({
                    id: imageId,
                    parentId: parentId,
                    canvaId: canvaId,
                    isGalleryImage: isGalleryImage,
                    enableColorOverlay: isGalleryImage ? enableColorOverlay : true,
                    mimeType: mimeType,
                    src: src,
                    url: originUrl,
                    thumb_url: originUrl,
                    left: imageRect.centerX,
                    top: imageRect.centerY,
                    realWidth: realWidth,
                    realHeight: realHeight,
                    angle: imageRect.rotate ? 90 : 0
                })

                if (image.type === 'group') {
                    image.set('group_type', 'svg')
                }

                this.fire('object:before-add', {target: image})

                this.add(image)
                this.setActiveObject(image)
                this.renderAll()

                this.handleBuildTypeChange()
                this.checkDesignOverlapping()
            }
        })
    }

    addText(text, fontFamily) {
        const viewport = this.getViewPort()
        const artBoardMargin = this.getArtboardMarginPx()
        const margin = this.getMarginPx()

        const textObject = new fabric.IText(text, {
            fill: 'black',
            fontFamily: fontFamily,
            editable: true,
            selectable: true
        })

        textObject.scaleToWidth(viewport.width * 0.5)

        const {height} = fabric.util.measureText(this.contextContainer, text, fontFamily, 40 * textObject.scaleX)

        let top = height / 2

        const lowestObject = this.getLowestObject(true)

        if (lowestObject) {
            const rect = lowestObject.getBoundaryRect()
            top += rect.bottom + margin
        } else {
            top += viewport.top + artBoardMargin
        }

        textObject.set('top', top)
        textObject.set('height', height / textObject.scaleY)

        this.fire('object:before-add', {target: textObject})

        this.add(textObject)
        this.centerObjectH(textObject)
        this.setActiveObject(textObject)
        this.handleBuildTypeChange()
    }

    addClipPath() {
        const viewport = this.getViewPort()

        if (!this.clipPath) {
            this.clipPath = new fabric.Rect({
                width: viewport.width
            })
        }

        this.clipPath.set({
            left: viewport.centerX,
            top: viewport.centerY,
            height: viewport.height,
            width: viewport.width
        })

        this.renderAll()
    }

    clearMarginOverlapping() {
        this.hasOverlapping = false
        this.forEachObject(obj => {
            obj.overlapping = false
        })
        this.fire('object:overlapping', {status: false})
    }

    removeOutViewPortObjects() {
        for (const object of this._objects) {
            const rect = object.getRect()
            if (this._intersectWithViewPortFullOut(rect)) {
                this.remove(object)
            }
        }
        this.renderAll()
    }

    hasOutViewPortObjects() {
        this.discardActiveObject()
        return this._objects.some((obj) => this._intersectWithViewPortFullOut(obj.getRect()))
    }

    _intersectWithViewPortFullOut(rect) {
        const viewPort = this.getViewPort()

        return Math.round(viewPort.left) > Math.round(rect.right) ||
            Math.round(viewPort.right) < Math.round(rect.left) ||
            Math.round(viewPort.top) > Math.round(rect.bottom) ||
            Math.round(viewPort.bottom) < Math.round(rect.top)
    }

    _intersectWithViewPortOut(rect) {
        const viewPort = this.getViewPort()

        let isIntersect = Math.round(viewPort.left) > Math.round(rect.left) ||
            Math.round(viewPort.right) < Math.round(rect.right) ||
            Math.round(viewPort.top) > Math.round(rect.top)

        if (!this.autoSize) {
            isIntersect = isIntersect || Math.round(viewPort.bottom) < Math.round(rect.bottom)
        }

        return isIntersect
    }

    checkDesignOverlapping() {
        if (this.patternMode)
            return
        const selected = this.getActiveObject()
        if (selected) {
            const rect = selected.getBoundaryRect()

            if (this._intersectWithViewPortOut(rect)) {
                this.fire('object:off-board', {status: true})
            } else {
                this.fire('object:off-board', {status: false})
            }
        }
    }

    checkOverlapping(rect) {
        let overlapping = false
        for (const obj of this._objects) {
            if (obj.isPattern)
                continue
            const outline = obj.getBoundaryRect()
            if (rect.left >= outline.right || rect.right <= outline.left || rect.top >= outline.bottom || rect.bottom <= outline.top) {
                continue
            }
            overlapping = true
            break;
        }

        return overlapping || this._intersectWithViewPortOut(rect)
    }

    updateOverlappingStatus(overlapping) {
        if (overlapping) {
            if (this.hasOverlapping === false) {
                this.fire('object:overlapping', {status: true})
            }
        } else if (this.hasOverlapping === true) {
            this.fire('object:overlapping', {status: false})
        }
        this.hasOverlapping = overlapping
    }

    checkRectsOverlapping(rect1, rect2) {
        return !(Math.round(rect1.left) >= Math.round(rect2.right) ||
            Math.round(rect1.right) <= Math.round(rect2.left) ||
            Math.round(rect1.top) >= Math.round(rect2.bottom) ||
            Math.round(rect1.bottom) <= Math.round(rect2.top))
    }

    checkMarginOverlapping() {
        const selectedObject = this.getActiveObject()
        if (selectedObject) {
            let overlapping = false

            for (const obj of this._objects) {
                if (obj === selectedObject || obj.isPattern) continue
                obj.overlapping = false
                const outline = obj.getOutlineRect()
                const activeOutline = selectedObject.getOutlineRect()

                if (this.checkRectsOverlapping(activeOutline, outline)) {
                    overlapping = true
                    obj.overlapping = true
                }
            }

            this.updateOverlappingStatus(overlapping)

            selectedObject.overlapping = overlapping
            this.renderAll()
        } else if (this.hasOverlapping) {
            this.clearMarginOverlapping()
        }
    }

    checkObjectOverlapping() {
        if (this.patternMode)
            return
        const selectedObject = this.getActiveObject()
        let overlapping = false

        if (selectedObject && ['image', 'group', 'path', 'i-text', 'n-text'].includes(selectedObject.type)) {
            for (const obj of this._objects) {
                if (obj === selectedObject || obj.isPattern) continue
                obj.overlapping = false
                if (selectedObject.overlapsWithOther(obj)) {
                    overlapping = true
                    obj.overlapping = true
                }
            }

            this.updateOverlappingStatus(overlapping)

            selectedObject.overlapping = overlapping
            this.renderAll()
        } else if (this.hasOverlapping) {
            this.clearMarginOverlapping()
        }

        return overlapping
    }

    duplicate() {
        this.copy().then((copied) => {
            if (copied) {
                this.paste()
                this.checkDesignOverlapping()
            }
        })
    }

    sendForward() {
        const selectedObject = this.getActiveObject()
        if (selectedObject) {
            selectedObject.bringToFront()
        }
    }

    sendBackward() {
        const selectedObject = this.getActiveObject()
        if (selectedObject) {
            selectedObject.sendToBack()
            let index = 1
            let object = this.item(index)
            while(object && object.isPattern) {
                selectedObject.bringForward()
                index++
                object = this.item(index)
            }
        }
    }

    checkLayerIsTop() {
        return this._objects.indexOf(this.getActiveObject()) === this._objects.length - 1
    }

    checkLayerIsBottom() {
        const objects = this._objects.filter(obj => !obj.isPattern)
        return objects.indexOf(this.getActiveObject()) === 0
    }

    async cloneSelection(object) {
        return new Promise(resolve => {
            object.clone(clonedObject => {
                resolve(clonedObject)
            }, this.extraProps)
        })
    }

    checkRectOverlappingWithObjects(rect, objects) {
        const rectProps = {
            left: rect.centerX,
            top: rect.centerY,
            width: rect.width - 2,
            height: rect.height - 2
        }

        const tempRect = new fabric.Rect(rectProps)
        tempRect.canvas = this
        tempRect.setCoords()

        for (const obj of objects) {
            if (obj.overlapsWithOther(tempRect, false, true)) {
                return true
            }
        }

        return false
    }

    checkRectOverlappingObjects(rect, excludeActiveObjects = false) {
        let objects = this._objects

        if (excludeActiveObjects) {
            const activeObjects = this.getActiveObjects()
            objects = this._objects.filter(obj => activeObjects.every(aObj => aObj !== obj))
        }

        return this.checkRectOverlappingWithObjects(rect, objects)
    }

    async autoFill(quantity = 1, margin = 0) {
        const viewport = this.getViewPort()
        const canvas = this

        const activeObject = this.getActiveObject()
        const originObject = await canvas.cloneSelection(activeObject)
        originObject.canvas = this

        let row = 0
        let col = 0

        const originRect = activeObject.getBoundaryRect()

        let _margin = margin ? margin * this.getPixelSize() : this.getViewPortMargin()

        const existingObjects = activeObject.type === 'activeSelection' ?
            this._objects.filter(obj => !activeObject.contains(obj) && !obj.isPattern)
            : this._objects.filter(obj => obj !== activeObject && !obj.isPattern)

        let added = 0
        while (added < quantity) {
            try {
                col++
                let offsetLeft = (originRect.width + _margin) * col
                let left = originRect.centerX + offsetLeft

                if (left + originRect.width / 2 > viewport.right) {
                    row++
                    col = 0
                    offsetLeft = 0
                    left = originRect.centerX

                    while (viewport.left < left - (originRect.width * 3 / 2 + _margin)) {
                        offsetLeft -= originRect.width + _margin
                        left = originRect.centerX + offsetLeft
                        col--
                    }
                }

                let offsetTop = (originRect.height + _margin) * row
                let top = originRect.centerY + offsetTop

                if (this.autoSize) {
                    if (top + originRect.height / 2 > viewport.top + convertDimension(360, this.unit, 'px')) {

                        break;
                    }
                } else {
                    if (originRect.bottom + offsetTop >= viewport.bottom) {
                        break
                    }
                }

                const nextBox = fabric.util.getMarginRect({...originRect}, _margin)

                nextBox.centerX += offsetLeft
                nextBox.centerY += offsetTop

                if (!this.checkRectOverlappingWithObjects(nextBox, existingObjects)) {
                    if (originObject.type === 'activeSelection') {
                        originObject.forEachObject(async (obj) => {
                            const clonedObject = await canvas.cloneSelection(obj)
                            clonedObject.canvas = canvas
                            clonedObject.set({
                                left: originObject.left - obj.left + offsetLeft,
                                top: originObject.top + obj.top + offsetTop,
                                scaleX: obj.scaleX,
                                scaleY: obj.scaleY
                            })
                            this.fire('object:before-add', {target: clonedObject})
                            this.add(clonedObject)
                            clonedObject.setCoords()
                        })
                    } else {
                        const clonedObject = await this.cloneSelection(originObject)
                        clonedObject.canvas = canvas
                        clonedObject.set({
                            left: left,
                            top: top,
                            scaleX: originObject.scaleX,
                            scaleY: originObject.scaleY
                        })
                        this.fire('object:before-add', {target: clonedObject})
                        this.add(clonedObject)
                        clonedObject.setCoords()
                    }

                    added++
                }

            } catch (err) {
                console.error(err)
                break
            }
        }

        this.requestRenderAll()
        this.checkDesignOverlapping()
    }

    async exportGangSheet(from = 0, to = null) {
        const zoom = this.getZoom()
        const multiplier = 1 / zoom

        return this.exportCanvas(multiplier, from, to)
    }

    async exportThumbnail() {
        const zoom = this.getZoom()
        const multiplier = (800 / Math.max(this.designWidth, this.designHeight)) / zoom

        return this.exportCanvas(multiplier)
    }

    exportJson() {
        const json = this.toDatalessJSON(this.extraProps)

        json.designId = this.getDesignId()
        json.name = this.name

        const viewPort = this.getViewPort()

        json.meta = {
            variant: this.variant,
            unit: this.unit,
            viewport: viewPort,
            images: this.images,
            build_type: this.build_type
        }

        if (!json.id) {
            json.id = uuidv4()
        }

        json.printPattern = this.printPattern
        json.imagesError = this.hasOverlappingImages()
        json.resolutionError = this.hasLowResolutionImages()
        json.artBoardError = this.hasOffBoardImages()

        json.actualHeight = this.getActualHeight()
        json.actualHeightLabel = convertDimension(json.actualHeight, 'px', this.unit).toFixed(2) + ' ' + this.unit
        json.submitActualHeight = convertDimension(viewPort.height, 'px', 'in') - convertDimension(json.actualHeight, 'px', 'in') > 1

        json.qualityError = json.imagesError || json.resolutionError || json.artBoardError

        return json
    }

    exportFinalJson() {
        this.discardActiveObject()

        return this.exportJson()
    }

    selectLastObject() {
        if (this.size() === 0)
            return null
        const lastObject = this.item(this.size() - 1)
        if (lastObject.isPattern) return null
        if (lastObject) {
            this.setActiveObject(lastObject)
            this.renderAll()
            return lastObject
        }
        return null
    }

    selectObjects() {
        const objects = this.getObjects().filter(object => {
            return !object.isPattern
        })
        const groupSelection = new fabric.ActiveSelection(objects, {
            canvas: this
        })
        this.setActiveObject(groupSelection)
    }

    deleteActiveObjects() {
        const selected = this.getActiveObject()

        if (selected) {
            if (selected.type.includes('text') && selected.isEditing) {
                return
            }

            if (selected._objects) {
                selected._objects.forEach(object => {
                    this.remove(object)
                })
                this.discardActiveObject()
            }
            this.remove(selected)
            this.selectLastObject()
        }
        this.renderAll()
        this.checkDesignOverlapping()
    }

    rockSelectionRatio(enabled) {
        const selected = this.getActiveObject()
        if (selected) {
            let objects = []
            if (selected._objects) {
                objects = selected._objects
            } else {
                objects = [selected]
            }

            for (const object of objects) {
                object.setControlsVisibility({
                    mb: !enabled,
                    mr: !enabled
                })

                object.set('rockedRatio', enabled)
            }

            this.renderAll()
        }
    }

    removeLastObject() {
        let selected = this.getActiveObject()
        if (!selected) {
            selected = this.selectLastObject()
        }
        if (selected) {
            this.deleteActiveObjects()
        }
    }

    fitDesignToTopLeft() {
        this.selectObjects()
        const selected = this.getActiveObject()
        if (selected) {
            const viewport = this.getViewPort()
            const rect = selected.getBoundaryRect()

            const margin = this.getMarginPx()

            selected.set({
                left: viewport.left + rect.width / 2 + margin,
                top: viewport.top + rect.height / 2 + margin
            })
            this.discardActiveObject()
            this.renderAll()
        }
    }

    fitImageToWidth() {
        const objects = this.getObjects()
        if(objects && objects.length === 1) {
            const object = objects[0]
            if (object.type === "image") {
                const maxFileCount = Number(this.variant.maxAllowedFileCount || -1)
                const maxWidth = this.designWidth - this.getArtboardMarginPx() * 2
                if (maxFileCount === 1 && this.autoResizeSingleImage) {
                    object.scale(maxWidth / object.width)
                    const viewport = this.getViewPort()
                    const rect = object.getBoundaryRect()

                    const margin = this.getArtboardMarginPx()

                    object.set({
                        left: viewport.left + rect.width / 2 + margin,
                        top: viewport.top + rect.height / 2 + margin
                    })
                    this.renderAll()
                }
            }
        }
    }

    resizeCanvasHeight(height) {
        this.variant = {width: this.variant.width, height}
        this.renderAll()
    }

    /**
     * Checks if there are any images that are overlapping each other.
     * @returns {boolean}
     */
    hasOverlappingImages() {
        const objects = this._objects.filter(obj => !obj.type.includes('text') && !obj?.isPattern)

        for (let i = 0; i < objects.length - 1; i++) {
            for (let j = i + 1; j < objects.length; j++) {
                if (objects[i].overlapsWithOther(objects[j])) {
                    return true
                }
            }
        }

        return false
    }

    /**
     * Checks if there is any object that has low resolution.
     * @returns {boolean}
     */
    hasLowResolutionImages() {
        return this._objects.some((object) => {
            if (!object.isPattern && object.type === 'image' && !fabric.util.isSvg(object.getSourceUrl())) {
                const resolution = object.getResolution()
                return Math.min(resolution.resolutionX, resolution.resolutionY) < this.resolutionLevels.good
            }
            return false
        })
    }

    /**
     * Checks if there is any object that is out of the viewport.
     * @returns {boolean}
     */
    hasOffBoardImages() {
        if (this.patternMode)
            return
        return this._objects.some((object) => {
            if (object?.isPattern)
                return false;
            const rect = object.getBoundaryRect()
            return this._intersectWithViewPortOut(rect)
        })
    }

    /**
     * Checks if there is any object that affects the design quality.
     * @returns {boolean}
     */
    hasQualityWarning() {
        return this.hasLowResolutionImages() || this.hasOverlappingImages() || this.hasOffBoardImages()
    }

    applyPattern(pattern) {
        const jsonData = this.adaptJsonToCanvas(pattern, true)
        if (!jsonData.objects)
            return
        return new Promise(resolve => {
            fabric.util.enlivenObjects(jsonData.objects, async fabricObjects => {
                this.removePattern()
                fabricObjects.forEach(obj => {
                    obj.isPattern = true
                    this.add(obj)
                })
                this.lockBackgroundObjects()
                resolve()
            })
        })
    }

    removePattern() {
        this.getObjects().forEach((item) => {
            if (item.isPattern) {
                this.remove(item)
            }
        })
    }

    lockBackgroundObjects() {
        this.getObjects().forEach((item) => {
            if (item.isPattern) {
                item.sendToBack()
                item.evented = false
                item.selectable = false
                item.hasBorders = false
                item.hasControls = false
                item.lockMovementX = true
                item.lockMovementY = true
            }
        })
    }

    lockAll() {

        this.selection = false

        this.forEachObject((item) => {
            item.selectable = false
            item.lockMovementX = true
            item.lockMovementY = true
        })

        this.discardActiveObject().renderAll()

    }

    unlockAll() {

        this.selection = true

        this.forEachObject((item) => {
            item.selectable = true
            item.lockMovementX = false
            item.lockMovementY = false
        })

        this.discardActiveObject().renderAll()
    }

    setOpacity(opacity) {
        this.forEachObject((item) => {
            item.opacity = opacity
        })
        this.renderAll()
    }

    alignSelectedObject(align, withMargin = true) {
        const selected = this.getActiveObject()
        if (selected) {
            const viewport = this.getViewPort()
            const rect = selected.getBoundaryRect()

            const margin = withMargin ? this.getArtboardMarginPx() : 0

            switch (align) {
                case 'left':
                    selected.set('left', viewport.left + rect.width / 2 + margin)
                    break
                case 'right':
                    selected.set('left', viewport.right - rect.width / 2 - margin)
                    break
                case 'top':
                    selected.set('top', viewport.top + rect.height / 2 + margin)
                    break
                case 'bottom':
                    selected.set('top', viewport.bottom - rect.height / 2 - margin)
                    break
                case 'h-center':
                    selected.centerH()
                    break
                case 'v-center':
                    selected.centerV()
                    break
                case 'center':
                    selected.center()
                    break
                case 'top-left':
                    selected.set('top', viewport.top + rect.height / 2 + margin)
                    selected.set('left', viewport.left + rect.width / 2 + margin)
                    break
            }
            this.renderAll()
            this.fire('object:updated')
        }
    }

    fitToViewPortWidth(withMargin = true) {
        const selected = this.getActiveObject()
        if (selected) {
            const viewport = this.getViewPort()
            const bound = selected.getBoundaryRect()

            const margin = withMargin ? this.getMarginPx() : 0

            const scaleX = (viewport.width - margin * 2) / bound.width * selected.scaleX
            const r = selected.scaleX / scaleX
            const scaleY = selected.scaleY / r

            selected.set({
                left: viewport.centerX,
                scaleX: scaleX,
                scaleY: scaleY,
                top: selected.top + selected.height * (scaleY - selected.scaleY) / 2
            })

            this.renderAll()
            this.fire('object:updated', {target: selected})
        }
    }

    fitToViewPortHeight(withMargin = true) {
        const selected = this.getActiveObject()
        if (selected) {
            const viewport = this.getViewPort()
            const bound = selected.getBoundaryRect()

            const margin = withMargin ? this.getMarginPx() : 0

            selected.set('top', viewport.centerY)
            const scaleY = (viewport.height - margin * 2) / bound.height * selected.scaleY
            const r = selected.scaleY / scaleY
            const scaleX = selected.scaleY / r
            selected.set('scaleX', scaleX)
            selected.set('scaleY', scaleY)
            selected.centerH()

            this.renderAll()
            this.fire('object:updated', {target: selected})
        }
    }

    getCurrentHeight(skipPattern = false) {
        const lowestObject = this.getLowestObject(skipPattern)
        if (lowestObject) {
            const viewPort = this.getViewPort()
            return lowestObject.getBoundaryRect().bottom - viewPort.top
        }
        return 0;
    }

    getActualHeight() {
        const viewPort = this.getViewPort()
        return Math.min(viewPort.height, this.getCurrentHeight(!this.printPattern) + 300)
    }

    getLowestObject(skipPattern = false) {
        return this._objects.reduce((lowest, obj) => {
            if (skipPattern && obj.isPattern) {
                return lowest
            }

            if (lowest) {
                return obj.getBoundaryRect().bottom > lowest.getBoundaryRect().bottom ? obj : lowest
            }

            return obj
        }, null)
    }

    rerenderTextObjects() {
        this.getObjects('i-text').forEach((text) => {
            text.objectCaching = false
        })
        this.renderAll()
        this.getObjects('i-text').forEach((text) => {
            text.objectCaching = true
        })
    }

    rerenderNNObjects() {
        this.getObjects('n-text').forEach((text) => {
            text.objectCaching = false
        })
        this.renderAll()
        this.getObjects('n-text').forEach((text) => {
            text.objectCaching = true
        })
    }

    hasDefaultTexts() {
        return this.getObjects('i-text').some(obj => !obj.isPattern && obj.text?.toLowerCase() === 'gang sheet')
    }

    isEmpty() {
        return this.getObjects().filter(object => !object.isPattern).length === 0
    }
}

export default GangSheetCanvas
