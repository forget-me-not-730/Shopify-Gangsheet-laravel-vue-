import {getPixelSize} from '@/Builder/Utils/helpers'

const DEFAULT_UNIT = 'inch'
const SNAP_MARGIN = 10

fabric.Canvas.prototype.designWidth = 0
fabric.Canvas.prototype.designHeight = 0
fabric.Canvas.prototype.viewPortOffsetY = 0

fabric.Canvas.prototype.unit = DEFAULT_UNIT
fabric.Canvas.prototype.snapEnabled = true

fabric.Canvas.prototype.snapPositions = {
    object: [],
    edge: [],
    alignment: []
}

fabric.Canvas.prototype.initializing = true

fabric.Canvas.prototype.id = null
fabric.Canvas.prototype.designId = null
fabric.Canvas.prototype.variant = null

fabric.Canvas.prototype.setDesignId = function (designId) {
    this.designId = designId
}

fabric.Canvas.prototype.getDesignId = function () {
    return this.designId
}

fabric.Canvas.prototype.setVariant = function (variant) {
    this.variant = variant
}

fabric.Canvas.prototype.getVariant = function () {
    return this.variant
}

fabric.Canvas.prototype.setZoomByCenter = function (zoomLevel) {
    const center = {x: this.getWidth() / 2, y: this.getHeight() / 2}
    const zoomPoint = new fabric.Point(center.x, center.y)
    // Zoom to the specified level
    this.zoomToPoint(zoomPoint, zoomLevel)
}

fabric.Canvas.prototype.moveActiveObject = function (direction) {
    const step = 1 / this.getZoom()
    const selected = this.getActiveObject()
    if (selected) {
        switch (direction) {
            case 'left':
                selected.set('left', selected.left - step)
                break
            case 'up':
                selected.set('top', selected.top - step)
                break
            case 'right':
                selected.set('left', selected.left + step)
                break
            case 'down':
                selected.set('top', selected.top + step)
                break
        }
        selected.setCoords()
        this.renderAll()
        this.fire('object:moved', {target: selected})
    }
}

fabric.Canvas.prototype.getPixelSize = function () {
    return getPixelSize(this.unit)
}

fabric.Canvas.prototype.getViewPort = function () {
    const center = this.getCenter()

    return fabric.util.makeViewPort(center, this.designWidth, this.designHeight, this.viewPortOffsetY)
}

fabric.Canvas.prototype.getMargin = function () {
    if (this.marginEnabled) {
        return Number(this.margin)
    }
    return 0
}

fabric.Canvas.prototype.getArtboardMargin = function () {
    if (this.artboardMarginEnabled) {
        return Number(this.artboardMargin)
    }
    return 0
}

fabric.Canvas.prototype.getMarginPx = function () {
    return this.getMargin() * this.getPixelSize()
}

fabric.Canvas.prototype.getArtboardMarginPx = function () {
    return this.getArtboardMargin() * this.getPixelSize()
}

fabric.Canvas.prototype.getViewPortMargin = function () {
    return this.getMarginPx()
}

fabric.Canvas.prototype.checkSnapObjects = function () {
    if (!this.snapEnabled) return

    const canvas = this

    const object = this.getActiveObject()
    const viewport = this.getViewPort()
    const activeRect = object.getBoundaryRect()
    const activeOutline = object.getBoundaryOutlineRect(true)

    const margin = canvas.getViewPortMargin()

    const snapPosition = {left: null, top: null}

    const edgeSnapPositions = []

    const snapMargin = SNAP_MARGIN

    if (Math.abs(activeRect.left - viewport.left) < snapMargin) {
        edgeSnapPositions.push({x: 0})
        snapPosition.left = viewport.left
    }

    if (Math.abs(activeRect.right - viewport.right) < snapMargin) {
        edgeSnapPositions.push({x: viewport.width})
        snapPosition.left = viewport.right - activeRect.width
    }

    if (Math.abs(activeRect.top - viewport.top) < snapMargin) {
        edgeSnapPositions.push({y: 0})
        snapPosition.top = viewport.top
    }

    if (Math.abs(activeRect.bottom - viewport.bottom) < snapMargin) {
        edgeSnapPositions.push({y: viewport.height})
        snapPosition.top = viewport.bottom - activeRect.height
    }

    if (canvas.marginEnabled) {

        if (Math.abs(activeRect.left - margin - viewport.left) < snapMargin) {
            edgeSnapPositions.push({x: 0})
            snapPosition.left = viewport.left + margin
        }

        if (Math.abs(activeRect.right + margin - viewport.right) < snapMargin) {
            edgeSnapPositions.push({x: viewport.width})
            snapPosition.left = viewport.right - activeRect.width - margin
        }

        if (Math.abs(activeRect.top - margin - viewport.top) < snapMargin) {
            edgeSnapPositions.push({y: 0})
            snapPosition.top = viewport.top + margin
        }

        if (Math.abs(activeRect.bottom + margin - viewport.bottom) < snapMargin) {
            edgeSnapPositions.push({y: viewport.height})
            snapPosition.top = viewport.bottom - activeRect.height - margin
        }
    }

    this.snapPositions.edge = edgeSnapPositions

    const alignmentSnapPositions = []
    if (Math.abs(activeRect.centerX - viewport.centerX) < snapMargin) {
        alignmentSnapPositions.push({x: viewport.centerX - viewport.left})
        snapPosition.left = viewport.centerX - activeRect.width / 2
    }

    if (Math.abs(activeRect.centerY - viewport.centerY) < snapMargin) {
        alignmentSnapPositions.push({y: viewport.centerY - viewport.top})
        snapPosition.top = viewport.centerY - activeRect.height / 2
    }
    this.snapPositions.alignment = alignmentSnapPositions

    const objectSnapPositions = []
    this.forEachObject(function (obj) {
        if (obj === object) return

        const rect = obj.getBoundaryRect()

        if (Math.abs(rect.left - activeRect.left) < snapMargin) {
            objectSnapPositions.push({x: rect.left - viewport.left})
            snapPosition.left = rect.left
        }

        if (Math.abs(rect.right - activeRect.right) < snapMargin) {
            objectSnapPositions.push({x: rect.right - viewport.left})
            snapPosition.left = rect.right - activeRect.width
        }

        if (Math.abs(rect.top - activeRect.top) < snapMargin) {
            objectSnapPositions.push({y: rect.top - viewport.top})
            snapPosition.top = rect.top
        }

        if (Math.abs(rect.bottom - activeRect.bottom) < snapMargin) {
            objectSnapPositions.push({y: rect.bottom - viewport.top})
            snapPosition.top = rect.bottom - activeRect.height
        }

        if (Math.abs(rect.right - activeRect.left) < snapMargin) {
            objectSnapPositions.push({x: rect.right - viewport.left})
            snapPosition.left = rect.right
        }

        if (Math.abs(rect.left - activeRect.right) < snapMargin) {
            objectSnapPositions.push({x: rect.left - viewport.left})
            snapPosition.left = rect.left - activeRect.width
        }

        if (Math.abs(rect.bottom - activeRect.top) < snapMargin) {
            objectSnapPositions.push({y: rect.bottom - viewport.top})
            snapPosition.top = rect.bottom
        }

        if (Math.abs(rect.top - activeRect.bottom) < snapMargin) {
            objectSnapPositions.push({y: rect.top - viewport.top})
            snapPosition.top = rect.top - activeRect.height
        }

        if (Math.abs(rect.centerX - activeRect.centerX) < snapMargin) {
            objectSnapPositions.push({x: rect.centerX - viewport.left})
            snapPosition.left = rect.centerX - activeRect.width / 2
        }

        if (Math.abs(rect.centerY - activeRect.centerY) < snapMargin) {
            objectSnapPositions.push({y: rect.centerY - viewport.top})
            snapPosition.top = rect.centerY - activeRect.height / 2
        }

        // Check margin bound
        if (canvas.marginEnabled) {
            const outline = obj.getBoundaryOutlineRect(true)

            if (Math.abs(outline.right - activeOutline.left) < snapMargin) {
                objectSnapPositions.push({x: outline.right - viewport.left})
                snapPosition.left = rect.right + margin
            }

            if (Math.abs(outline.left - activeOutline.right) < snapMargin) {
                objectSnapPositions.push({x: outline.left - viewport.left})
                snapPosition.left = rect.left - activeRect.width - margin
            }

            if (Math.abs(outline.top - activeOutline.bottom) < snapMargin) {
                objectSnapPositions.push({y: outline.top - viewport.top})
                snapPosition.top = rect.top - activeRect.height - margin
            }

            if (Math.abs(outline.bottom - activeOutline.top) < snapMargin) {
                objectSnapPositions.push({y: outline.bottom - viewport.top})
                snapPosition.top = rect.bottom + margin
            }
        }

    })

    this.snapPositions.object = objectSnapPositions

    this.snapTo(object, snapPosition)
    this.fire('object:snap')
}

fabric.Canvas.prototype.snapTo = function (object, position) {
    const rect = object.getBoundaryRect()

    if (position.left) {
        object.set('left', position.left + rect.width / 2)
    }
    if (position.top) {
        object.set('top', position.top + rect.height / 2)
    }
    this.renderAll()
}

fabric.Canvas.prototype.selectObjects = function (objects) {
    this.discardActiveObject()
    const groupSelection = new fabric.ActiveSelection(objects, {
        canvas: this
    })
    this.setActiveObject(groupSelection)
    this.requestRenderAll()
}

fabric.Canvas.prototype.selectAll = function () {
    // Select all objects
    this.selectObjects(this._objects)
}

fabric.Canvas.prototype.copy = async function () {
    const activeObject = this.getActiveObject()
    return new Promise((resolve) => {
        if (activeObject) {
            activeObject.clone((cloned) => {
                this._clipboard = cloned
                resolve(true)
            }, this.extraProps)
        } else {
            resolve(false)
        }
    })
}

fabric.Canvas.prototype.paste = function () {
    const objectToPaste = this._clipboard
    if (objectToPaste) {
        const canvas = this
        canvas.discardActiveObject()
        objectToPaste.clone(function (clonedObj) {
            if (clonedObj.type === 'activeSelection') {
                // active selection needs a reference to the canvas.
                clonedObj.canvas = canvas
                clonedObj.forEachObject(function (obj) {
                    canvas.add(obj)
                })
                // this should solve the unselectability
                clonedObj.setCoords()
            } else {
                canvas.add(clonedObj)
            }

            clonedObj.set({
                left: clonedObj.left + 200,
                top: clonedObj.top + 200,
                evented: true
            })

            canvas.setActiveObject(clonedObj)
            canvas.renderAll()
        }, this.extraProps)
    }
}

fabric.Canvas.prototype.addCrossOriginToImages = async function () {
    const addCrossOriginOne = (obj) => {
        return new Promise(resolve => {
            try {
                if (obj.type === 'image' && obj._element && obj._element.nodeName.toLowerCase() === 'img') {
                    const crossOrigin = obj._element.getAttribute('crossorigin')
                    if (crossOrigin === 'anonymous') {
                        resolve(true)
                    } else {
                        obj._element.setAttribute('crossorigin', 'anonymous')
                        obj._element.onload = () => {
                            resolve(true)
                        }
                        obj._element.onerror = () => {
                            resolve(false)
                        }
                    }
                } else {
                    resolve(true)
                }
            } catch (e) {
                resolve(false)
            }
        })
    }

    return await Promise.all(this._objects.map(obj => addCrossOriginOne(obj)))
}

fabric.Canvas.prototype.exportCanvas = async function (multiplier, from = 0, to = null) {
    await this.addCrossOriginToImages()

    const zoom = this.getZoom()

    const center = this.getCenterPoint()
    const vpCenter = this.getVpCenter()

    let height = this.designHeight * zoom
    if (to) {
        height = (to - from) * zoom
    }

    const left = (this.width / 2 - this.designWidth / 2 * zoom) - (vpCenter.x - center.x) * zoom
    const top = (this.height / 2 - this.designHeight / 2 * zoom) - (vpCenter.y - center.y - this.viewPortOffsetY / 2) * zoom + from * zoom

    const dataUrl = this.toDataURL({
        format: 'png',
        multiplier,
        left: left,
        top: top,
        width: this.designWidth * zoom,
        height: height
    })

    this.renderAll()

    return dataUrl
}
