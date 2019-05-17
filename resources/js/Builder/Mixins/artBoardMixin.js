import {fabric} from "fabric";
import eventBus from "@/Builder/Utils/eventBus";

export default {
    data() {
        return {
            moveMode: false,
            gridMode: false,

            // scaleX, skewX, skewY, scaleY, translateX, translateY
            originalTransform: [1, 0, 0, 1, 0, 0],
            zoom: 1,

            translateY: 0,
            translateX: 0,

            touchStartX: 0,
            touchStartY: 0,

            spaceKyePressed: false,
            mousePressed: false,

            // redo/undo
            hasUndoHistory: false,
            hasRedoHistory: false,

            // scroll screen
            scrollHEnabled: false,
            scrollVEnabled: false,
            scrollX: 0,
            scrollY: 0,

            // mobile touch pinch event
            initialDistance: 0,
            zooming: false,
            lastTouchTime: 0,

            viewPortOffsetY: 0,

            contextMenuVisible: false,
            contextMenuPosition: {
                x: 0,
                y: 0
            },
            timer: 0,

            isContextMenuEnabled: true
        }
    },
    computed: {
        isMoving() {
            return (this.spaceKyePressed || this.moveMode) && this.mousePressed
        },
        scrollXRate() {
            return (this.designWidth * this.zoom) / (this.screenWidth / 2)
        },
        scrollYRate() {
            return (this.designHeight * this.zoom) / (this.screenHeight / 2)
        }
    },
    mounted() {
        eventBus.$on(eventBus.OPEN_DESIGN, this.handleOpenDesign)
        eventBus.$on(eventBus.REFRESH_BUILDER, this.handleRefreshBuilder)

        document.onkeydown = this.handleKeyDown
        document.onkeyup = this.handleKeyUp
        document.onmouseup = this.handleMouseUp
        document.onmousemove = this.handleMouseMove

        document.addEventListener('touchmove', this.handleTouchMove, {passive: false})
        document.addEventListener('touchend', this.handleMouseUp, {passive: false})

        if (this.$refs.editorRoot) {
            this.$refs.editorRoot.addEventListener('contextmenu', this.handleContextMenu)
            document.addEventListener('click', this.closeContextMenu)
        }
    },
    beforeUnmount() {
        eventBus.$off(eventBus.OPEN_DESIGN, this.handleOpenDesign)
        eventBus.$off(eventBus.REFRESH_BUILDER, this.handleRefreshBuilder)

        document.onkeydown = null
        document.onkeyup = null
        document.onmouseup = null
        document.onmousemove = null

        document.removeEventListener('touchmove', this.handleTouchMove)
        document.removeEventListener('touchend', this.handleMouseUp)
        document.removeEventListener('click', this.closeContextMenu)
    },
    methods: {
        handleKeyDown(e) {
            if (e.target.closest('input')) {
                return
            }

            const key = e.which || e.keyCode

            switch (key) {
                case 8: // Backspace
                    this.handleDelete()
                    break
                case 32: // Space
                    this.spaceKyePressed = true
                    break
                case 37: // left arrow
                    this.handleMoveByKey('left')
                    break
                case 38: // up arrow
                    this.handleMoveByKey('up')
                    break
                case 39: // right arrow
                    this.handleMoveByKey('right')
                    break
                case 40: // down arrow
                    this.handleMoveByKey('down')
                    break
                case 46: // Delete
                    this.handleDelete()
                    break
            }

            if (e.ctrlKey) {
                if ([68, 83, 65].includes(key)) {
                    e.preventDefault()
                }

                switch (key) {
                    case 67: // Ctrl + C
                        window._gangSheetCanvasEditor.copy()
                        break
                    case 86: // Ctrl + V
                        window._gangSheetCanvasEditor.paste()
                        break
                    case 68: // Ctrl + D
                        window._gangSheetCanvasEditor.duplicate()
                        break
                    case 83: // Ctrl + S
                        this.$gsb.updateCanvasData()
                        break
                    case 88: // Ctrl + X
                        window._gangSheetCanvasEditor.copy().then(copied => {
                            if (copied) {
                                this.handleDelete()
                            }
                        })
                        break
                    case 65: // Ctrl + A
                        window._gangSheetCanvasEditor.selectAll()
                        break
                }
            }
        },
        handleKeyUp(e) {
            this.spaceKyePressed = false

            if (!e.ctrlKey) {
                return
            }
            if (e.keyCode === 90) {
                this.handleUndo()
            }

            // Check pressed button is Y - Ctrl+Y.
            if (e.keyCode === 89) {
                this.handleRedo()
            }
        },
        handleContextMenu(e) {
            e.preventDefault();
            this.contextMenuPosition = {
                x: e.clientX,
                y: e.clientY
            };
            this.openContextMenu()
        },
        openContextMenu(e) {
            if (this.isContextMenuEnabled) {
                const activeObject = _gangSheetCanvasEditor.getActiveObject();

                if (activeObject) {
                    const {type, isGalleryImage} = activeObject;

                    eventBus.$emit(eventBus.CONTEXT_MENU, {
                        position: this.contextMenuPosition,
                        visible: true,
                        type: type,
                        isGalleryImage: isGalleryImage
                    });
                }
            }
        },
        closeContextMenu() {
            eventBus.$emit(eventBus.CONTEXT_MENU, {
                visible: false
            });
        },
        handleMouseUp() {
            this.mousePressed = false
            this.scrollHEnabled = false
            this.scrollVEnabled = false

            if (this.zooming) {
                this.initialDistance = 0
                this.zooming = false
                window._gangSheetCanvasEditor.unlockAll()
            }
        },
        handleMouseMove(e) {
            clearTimeout(this.timer)

            const movementX = e.clientX - this.touchStartX
            const movementY = e.clientY - this.touchStartY

            if (this.isMoving) {
                e.preventDefault()
                e.stopImmediatePropagation()

                this.moveCanvasTransform(movementX, movementY)
            } else {
                if (this.scrollHEnabled) {
                    this.moveCanvasTransform(-movementX * this.scrollXRate, 0)
                }

                if (this.scrollVEnabled) {
                    this.moveCanvasTransform(0, -movementY * this.scrollYRate)
                }
            }

            this.touchStartX = e.clientX
            this.touchStartY = e.clientY
        },
        handleTouchMove(e) {
            clearTimeout(this.timer);

            if (this.isMoving || this.scrollHEnabled || this.scrollHEnabled || this.zooming) {
                e.preventDefault()
                e.stopImmediatePropagation()
            }

            const movementX = e.touches[0].clientX - this.touchStartX
            const movementY = e.touches[0].clientY - this.touchStartY

            if (this.isMoving) {
                this.translateX += movementX
                this.translateY += movementY

                this.moveCanvasTransform(movementX, movementY)
            }

            if (this.scrollHEnabled) {
                this.moveCanvasTransform(-movementX * this.scrollXRate, 0)
            }

            if (this.scrollVEnabled) {
                this.moveCanvasTransform(0, -movementY * this.scrollYRate)
            }

            this.touchStartX = e.touches[0].clientX
            this.touchStartY = e.touches[0].clientY

            if (e.touches.length === 2 && this.initialDistance) {
                const touch1 = e.touches[0]
                const touch2 = e.touches[1]
                const currentDistance = this.calculateDistance(touch1, touch2)

                // Calculate the pinch/pull ratio
                const pinchPullRatio = Math.sqrt(currentDistance / this.initialDistance)

                // Update the zoom level
                if (this.zoom > 0.001) {
                    this.zoom *= pinchPullRatio

                    const editorRect = this.$refs.editorRef.getBoundingClientRect()

                    const point = {
                        x: (touch1.clientX - editorRect.left + touch2.clientX - editorRect.left) / 2,
                        y: (touch1.clientY - editorRect.top + touch2.clientY - editorRect.top) / 2
                    }

                    this.handleZoomChange(point)
                }

                this.initialDistance = currentDistance
            }
        },
        handleZoomChange(point) {
            if (window._gangSheetCanvasEditor) {
                point = point || {
                    x: this.screenWidth / 2,
                    y: this.screenHeight / 2
                }
                const zoomPoint = new fabric.Point(point.x, point.y)
                window._gangSheetCanvasEditor.zoomToPoint(zoomPoint, this.zoom)
                this.onCanvasTransform(window._gangSheetCanvasEditor.viewportTransform.slice())
            }
        },
        moveCanvasTransform(movementX, movementY) {
            const newTransform = window._gangSheetCanvasEditor.viewportTransform.slice()
            newTransform[4] += movementX || 0
            newTransform[5] += movementY || 0
            window._gangSheetCanvasEditor.setViewportTransform(newTransform)
            this.onCanvasTransform(newTransform)
        },
        onCanvasTransform(transform) {
            const zoomDelta = this.originalTransform[0] - transform[0]
            this.translateX = transform[4] - (zoomDelta * this.screenWidth + this.originalTransform[4] * 2) / 2
            this.translateY = transform[5] - (zoomDelta * this.screenHeight + this.originalTransform[5] * 2) / 2

            this.scrollX = -(transform[4] - this.originalTransform[4]) / this.scrollXRate
            this.scrollY = -(transform[5] - this.originalTransform[5]) / this.scrollYRate
        },
        handleDelete() {
            if (window._gangSheetCanvasEditor) {
                window._gangSheetCanvasEditor.deleteActiveObjects()
            }
        },
        handleScroll(e) {
            if (e.ctrlKey) {
                e.stopPropagation()

                const point = {x: e.layerX, y: e.layerY}

                if (e.deltaY > 0) {
                    this.zoom /= 1.1
                    this.handleZoomChange(point)
                } else {
                    this.zoom *= 1.1
                    this.handleZoomChange(point)
                }
            } else {
                const transformY = (e.deltaY > 0 ? -1 : 1) * Math.sqrt(Math.abs(e.deltaY)) * 2
                this.moveCanvasTransform(0, transformY)
                this.scrollY -= transformY / this.scrollYRate
            }
        },
        handleMouseDown(e) {
            this.mousePressed = true
            this.touchStartX = e.clientX
            this.touchStartY = e.clientY
            clearTimeout(this.timer)
        },
        handleMouseLeave() {
            this.mousePressed = false
            clearTimeout(this.timer)
        },
        handleTouchStart(e, eventName) {
            this.mousePressed = true

            if (this.isMoving || this.scrollHEnabled || this.scrollHEnabled || this.zooming) {
                e.preventDefault()
                e.stopImmediatePropagation()
            }

            this.touchStartX = e.touches[0].clientX
            this.touchStartY = e.touches[0].clientY
            this[eventName] = true

            this.closeContextMenu()
            clearTimeout(this.timer)
            this.timer = setTimeout(() => {
                this.handleLongPress(e)
            }, 1000)
        },
        handleLongPress(e) {
            if (this.mousePressed) {
                e.preventDefault()
                this.contextMenuPosition = {
                    x: e.touches[0].clientX,
                    y: e.touches[0].clientY
                }
                this.openContextMenu()
            }
        },
        handleUndo() {
            _gangSheetCanvasEditor.undo()
        },
        handleRedo() {
            _gangSheetCanvasEditor.redo()
        },
        showSnapLines() {
            this.snapEnabled = true
            _gangSheetCanvasEditor.checkSnapObjects()
            setTimeout(() => {
                this.snapEnabled = false
            }, 500)
        },
        handleZoomIn(e) {
            e.preventDefault()
            e.stopImmediatePropagation()
            this.zoom *= 1.1
            this.handleZoomChange()
        },
        handleZoomOut(e) {
            e.preventDefault()
            e.stopImmediatePropagation()
            this.zoom /= 1.1
            this.handleZoomChange()
        },
        handleEditorTouchStart(e) {
            e.preventDefault()
            if (e.touches.length === 2) {
                _gangSheetCanvasEditor.lockAll()
                const touch1 = e.touches[0]
                const touch2 = e.touches[1]
                this.initialDistance = this.calculateDistance(touch1, touch2)
                this.zooming = true
            }
        },
        updateHistory() {
            this.hasUndoHistory = _gangSheetCanvasEditor?.canUndo()
            this.hasRedoHistory = _gangSheetCanvasEditor?.canRedo()

            this.hasDesignChange = this.hasUndoHistory || this.hasRedoHistory
        },
        handleDrop(e) {
            e.preventDefault()
            const url = e.dataTransfer.getData('imageUrl')
            if (url) {
                _gangSheetCanvasEditor.addImage(url)
            }
        },
    }
}
