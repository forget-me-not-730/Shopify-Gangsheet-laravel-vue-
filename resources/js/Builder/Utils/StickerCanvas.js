import {fabric} from 'fabric'

import {v4 as uuidv4} from 'uuid'
import {CUT_LINES} from '@/Builder/Utils/constants'

import './fabric-util'
import './fabric-history'
import './fabric-object'
import './fabric-rect'
import './fabric-canvas'
import './fabric-itext'
import * as d3 from 'd3'

fabric.Canvas.prototype.name = 'New Sticker'
fabric.Canvas.prototype.backgroundSrc = null
fabric.Canvas.prototype.margin = 0.05
fabric.Canvas.prototype.borderWidth = 0.004
fabric.Canvas.prototype.bgColor = 'white'
fabric.Canvas.prototype.borderColor = 'white'
fabric.Canvas.prototype.dieCutPath = null
fabric.Canvas.prototype.cutLineType = CUT_LINES.dieCut
fabric.Canvas.prototype.showImageOutline = true
fabric.Canvas.prototype.quantity = 1
fabric.textureSize = 20480

fabric.Canvas.prototype.extraProps = [
    'id',
    'name',
    'quantity',
    'backgroundSrc',
    'margin',
    'cutLineType',
    'dieCutPath',
    'selectable',
    'borderWidth',
    'bgColor',
    'baseLines',
    'leftLines',
    'borderColor',
]

class StickerCanvas extends fabric.Canvas {

    constructor(canvasId, options, callback) {
        super(canvasId, options)
        this.ready = this.initializeStickerCanvas(options, callback)

        this.renderAll()
    }

    async initializeStickerCanvas(options) {

        return new Promise((resolve) => {
            this.designWidth = options.stickerWidth
            this.designHeight = options.stickerHeight

            if (options.zoom) {
                this.setZoomByCenter(options.zoom)
            }

            if (options.jsonData) {
                options = _.merge({}, options.jsonData.meta, options)
            }

            if (options.variant) {
                this.variant = options.variant
            }

            if (options.unit) {
                this.unit = options.unit
            }

            if (options.jsonData) {

                const oldViewPort = options.jsonData.meta.viewport

                if (oldViewPort) {
                    const viewPort = this.getViewPort()

                    options.jsonData.objects = options.jsonData.objects.reduce((r, o) => {
                        if (o.type.endsWith('text') && !o.text) {
                            return r
                        }

                        const obj = {
                            ...o,
                            left: viewPort.left + (o.left - oldViewPort.left),
                            top: viewPort.top + (o.top - oldViewPort.top),
                        }

                        r.push(obj)

                        return r
                    }, [])
                }

                this.loadFromJSON(options.jsonData, () => {
                    this.variantChanged()

                    this.initializing = false
                    this.isReady = true
                    resolve(this)
                })
            } else {
                this.initializing = false
                this.isReady = true
                resolve(this)
            }

            if (options.stickerOutlineWidth) {
                this.setBorderWidth(options.stickerOutlineWidth)
            }

            if (options.stickerOutlineColor) {
                this.setBorderColor(options.stickerOutlineColor)
            }

            this.controlsAboveOverlay = true;

        })

    }

    variantChanged() {
        this.drawBackground().then(() => {
            this.drawCutLine()
        })
        this._objects.forEach(object => {
            if (object.type.includes('text')) {
                this.remove(object)
            }
        })
    }

    addImage(image) {
        let src = image.url

        fabric.util.makeImageFromUrl(src, false).then(image => {
            if (image) {
                const maxWidth = this.designWidth
                const maxHeight = this.designHeight

                if (image.width * image.scaleX > maxWidth) {
                    image.scaleToWidth(maxWidth)
                }

                if (image.height * image.scaleY > maxHeight) {
                    image.scaleToHeight(maxHeight)
                }

                image.set({
                    src: src,
                }).setCoords()

                this.add(image)
                image.center()
                this.setActiveObject(image)

                this.renderAll()
            }
        })
    }


    setMargin(margin) {
        this.margin = Number(margin)
        this.setScaleBackground()
    }

    setBorderWidth(borderWidth) {
        this.borderWidth = Number(borderWidth)
        this.setScaleBackground()
        this.drawCutLine()
    }

    setBorderColor(color) {
        this.borderColor = color
        this.drawCutLine()
    }

    setShowImageOutline(show) {
        this.showImageOutline = show
        this.drawCutLine()
    }

    getShowImageOutline() {
        return this.showImageOutline
    }

    getMargin() {
        return Number(this.margin)
    }

    getBorderWidth() {
        return Number(this.borderWidth)
    }

    getBgColor() {
        return this.bgColor
    }

    getBorderColor() {
        return this.borderColor
    }

    getMarginPx() {
        return this.getMargin() * this.getPixelSize()
    }

    setScaleBackground() {
        const background = this.getBackground()
        if (background) {
            const newWidth = this.designWidth - this.getMarginPx() * 2 - this.getBorderWidthPx() * 2
            const newHeight = this.designHeight - this.getMarginPx() * 2 - this.getBorderWidthPx() * 2
            if (this.cutLineType === CUT_LINES.ellipseCut) {
                background.scale(Math.min(newWidth / background.width / Math.sqrt(2), newHeight / background.height / Math.sqrt(2)))
            } else if (this.cutLineType === CUT_LINES.roundedCut) {
                const innerWidth = newWidth * 0.95 - this.getMarginPx() / Math.sqrt(2);
                const innerHeight = newHeight * 0.95 - this.getMarginPx() / Math.sqrt(2);
                background.scale(Math.min(innerWidth / background.width, innerHeight / background.height))
            } else {
                background.scale(
                    Math.min(
                        newWidth / background.width,
                        newHeight / background.height
                    )
                )
            }
            this.renderAll()
        }
    }

    getBorderWidthPx() {
        return this.getBorderWidth() * this.getPixelSize()
    }

    clearCanvas() {
        this.backgroundSrc = null
        this.clear()
        this.renderAll()
        this.fire('canvas:cleared')
    }

    removeBackground() {
        const background = this.getBackground()
        if (background) {
            this.remove(background)
        }
    }

    async drawBackground() {
        this.removeBackground()

        if (this.backgroundSrc) {
            const image = await fabric.util.makeImageFromUrl(this.backgroundSrc, false)
            if (image) {
                const width = this.designWidth - this.getMarginPx() * 2 - this.getBorderWidthPx() * 2
                const height = this.designHeight - this.getMarginPx() * 2 - this.getBorderWidthPx() * 2

                image.scale(Math.min(width / image.width, height / image.height))

                image.set({
                    src: this.backgroundSrc,
                    id: 'background',
                    selectable: false,
                }).setCoords()

                this.add(image)
                image.center()
                image.sendToBack()
                this.renderAll()
            }
        }
    }

    async addBackground(src) {
        this.backgroundSrc = src
        await this.drawBackground()
    }

    deleteActiveObjects() {
        const selected = this.getActiveObject()

        if (selected) {
            if (selected.type === 'i-text' && selected.isEditing) {
                return
            }

            if (selected._objects) {
                selected._objects.forEach(object => {
                    this.remove(object)
                })
                this.discardActiveObject()
            }
            this.remove(selected)
        }

        this.renderAll()
    }

    exportJson() {
        const json = this.toDatalessJSON(this.extraProps)

        json.designId = this.getDesignId()
        json.name = this.name

        json.meta = {
            variant: this.variant,
            unit: this.unit,
            viewport: this.getViewPort()
        }

        if (!json.id) {
            json.id = uuidv4()
        }

        json.objects.forEach(object => {
            if (object.id === 'out-line') {
                object.stroke = this.borderColor
            }
        })

        return json
    }

    async exportSticker() {
        const zoom = this.getZoom()
        const multiplier = 1 / zoom

        return this.exportCanvas(multiplier)
    }

    getCutLine() {
        return this.getObjects().find(object => object.id === 'cut-line')
    }

    removeCutLine() {
        const cutLine = this.getCutLine()
        if (cutLine) {
            this.remove(cutLine)
        }
        const outLine = this.getObjects().find(object => object.id === 'out-line')
        if (outLine) this.remove(outLine)

    }

    getBackground() {
        return this.getObjects().find(object => object.id === 'background')
    }

    async exportThumbnail() {
        const zoom = this.getZoom()
        const multiplier = (300 / Math.max(this.designWidth, this.designHeight)) / zoom

        return this.exportCanvas(multiplier)
    }

    setDieCutPath(paths) {

        const dieCutPath = []
        for (const points of paths) {
            const line = d3.line()
                .x(d => d[0])
                .y(d => d[1])
                .curve(d3.curveBasis)

            const path = line([...points, points[0]])

            dieCutPath.push(path)
        }

        this.dieCutPath = dieCutPath.join(' ')

        this.drawCutLine()
    }

    getDieCutMargin() {
        return this.getMarginPx()
    }

    drawCutLine() {
        const background = this.getBackground()

        if (background) {
            this.removeCutLine()

            switch (this.cutLineType) {
                case CUT_LINES.dieCut:
                    this.drawDieCutPath()
                    break
                case CUT_LINES.rectCut:
                    this.drawRectCutLine()
                    break
                case CUT_LINES.ellipseCut:
                    this.drawEllipsisCutLine()
                    break
                case CUT_LINES.roundedCut:
                    this.drawRoundedCutLine()
                    break
                default:
                    this.drawDieCutPath()
            }
            this.setScaleBackground()
        } else {
            this.removeCutLine()
        }

        const cutLine = this.getCutLine()
        this.fire('cut-line:updated', {cutLine})
    }

    getCutLineDimensions() {
        if (this.dieCutPath) {
            const dieCutPath = new fabric.Path(this.dieCutPath)

            dieCutPath.scale(Math.min(this.designWidth / dieCutPath.width, this.designHeight / dieCutPath.height))

            return {
                width: dieCutPath.getScaledWidth(),
                height: dieCutPath.getScaledHeight()
            }
        }

        return {
            width: 0,
            height: 0
        }
    }

    drawDieCutPath() {
        if (this.dieCutPath) {
            const cutline = new fabric.Path(this.dieCutPath, {
                id: 'cut-line',
                fill: null,
                objectCaching: false,
                selectable: false,
            })
            cutline.scale(Math.min(this.designWidth / cutline.width, this.designHeight / cutline.height))
            this.clipPath = cutline
            this.add(cutline)
            cutline.center()

            const outline = new fabric.Path(this.dieCutPath, {
                id: 'out-line',
                fill: this.bgColor,
                stroke: this.showImageOutline ? this.borderColor : 'transparent',
                strokeLineCap: 'round',
                objectCaching: false,
                selectable: false,
                shadow: {
                    color: 'rgba(0,0,0,0.3)',
                    blur: 10
                }
            })

            const scale = Math.min((this.designWidth - this.getBorderWidthPx()) / outline.width, (this.designHeight - this.getBorderWidthPx()) / outline.height)
            outline.set({strokeWidth: this.getBorderWidthPx() / scale})
            outline.scale(scale)
            this.add(outline)
            outline.center()
            outline.sendToBack()

            cutline.sendToBack()

            this.renderAll()
        }
    }

    drawRectCutLine() {
        const {width, height} = this.getCutLineDimensions()

        const cutline = new fabric.Rect({
            id: 'cut-line',
            width: width,
            height: height,
            fill: null,
            objectCaching: false,
            selectable: false,
        })
        this.clipPath = cutline
        this.add(cutline)
        cutline.center()

        const outline = new fabric.Rect({
            id: 'out-line',
            width: width - this.getBorderWidthPx(),
            height: height - this.getBorderWidthPx(),
            fill: this.bgColor,
            stroke: this.showImageOutline ? this.borderColor : 'transparent',
            strokeWidth: this.getBorderWidthPx(),
            objectCaching: false,
            selectable: false,
            shadow: {
                color: 'rgba(0,0,0,0.3)',
                blur: 10
            }
        })
        this.add(outline)
        outline.center()
        outline.sendToBack()

        cutline.sendToBack()

        this.renderAll()
    }

    drawEllipsisCutLine() {

        const {width, height} = this.getCutLineDimensions()

        const cutline = new fabric.Ellipse({
            id: 'cut-line',
            rx: width / 2,
            ry: height / 2,
            objectCaching: false,
            fill: null,
            selectable: false,
        })
        this.clipPath = cutline
        this.add(cutline)
        cutline.center()

        const outline = new fabric.Ellipse({
            id: 'out-line',
            rx: (width - this.getBorderWidthPx()) / 2,
            ry: (height - this.getBorderWidthPx()) / 2,
            fill: this.bgColor,
            stroke: this.showImageOutline ? this.borderColor : 'transparent',
            strokeWidth: this.getBorderWidthPx(),
            selectable: false,
            shadow: {
                color: 'rgba(0,0,0,0.3)',
                blur: 10
            }
        })
        this.add(outline)
        outline.center()
        outline.sendToBack()

        cutline.sendToBack()

        this.renderAll()
    }

    drawRoundedCutLine() {

        const {width, height} = this.getCutLineDimensions()

        const cutline = new fabric.Rect({
            id: 'cut-line',
            width: width,
            height: height,
            objectCaching: false,
            fill: null,
            rx: width / 10,
            ry: height / 10,
            selectable: false,
        })
        this.clipPath = cutline
        this.add(cutline)
        cutline.center()

        const outline = new fabric.Rect({
            id: 'out-line',
            width: width - this.getBorderWidthPx(),
            height: height - this.getBorderWidthPx(),
            objectCaching: false,
            fill: this.bgColor,
            stroke: this.showImageOutline ? this.borderColor : 'transparent',
            strokeWidth: this.getBorderWidthPx(),
            rx: width / 10,
            ry: height / 10,
            selectable: false,
            shadow: {
                color: 'rgba(0,0,0,0.3)',
                blur: 10
            }
        })
        this.add(outline)
        outline.center()
        outline.sendToBack()

        cutline.sendToBack()

        this.renderAll()
    }

    setCutLineType(cutLineType) {
        this.cutLineType = cutLineType
        this.drawCutLine()
    }

    setBgColor(color) {
        this.bgColor = color
        this.drawCutLine()
    }

    addText(text, fontFamily) {
        const viewport = this.getViewPort()
        const textObject = new fabric.IText(text, {
            fill: 'black',
            fontFamily: fontFamily,
            editable: true,
            selectable: true
        })
        textObject.scaleToWidth(viewport.width * 0.5)
        textObject.setCoords()
        this.add(textObject)
        textObject.center()
        this.setActiveObject(textObject)
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
}

export default StickerCanvas
