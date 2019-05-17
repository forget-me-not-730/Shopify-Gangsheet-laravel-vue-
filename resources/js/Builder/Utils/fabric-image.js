fabric.Image.prototype.mimeType = undefined
fabric.Image.prototype.overlayColor = null
fabric.Image.prototype.url = null
fabric.Image.prototype.overlayFilter = 'blend'
fabric.Image.prototype.enableColorOverlay = false

fabric.Image.prototype.changeImageColor = function (color, filterMode) {

    // Disable for SVG files.
    if (fabric.util.isSvg(this.getSourceUrl())) {
        return
    }

    this.filters = !color ? [] : filterMode === 'blend' ?
        [new fabric.Image.filters.BlendColor({
            color: color,
            mode: 'overlay'
        })]
        :
        filterMode === 'overlay' ? [new fabric.Image.filters.BlendColor({
            color: color,
            mode: 'tint',
            opacity: 1
        })] : []

    this.applyFilters()

    this.overlayColor = color

    this.overlayFilter = filterMode

    this.canvas.renderAll()
}

fabric.Image.prototype.getOriginalUrl = function () {
    return this.url || this.getSrc()
}
