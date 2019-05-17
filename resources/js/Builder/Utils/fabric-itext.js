fabric.IText.prototype.baseLines = []
fabric.IText.prototype.leftLines = []
fabric.IText.prototype.MIN_TEXT_WIDTH = 20
fabric.IText.prototype.MIN_TEXT_HEIGHT = 30

fabric.IText.prototype.horizontalSpan = 0
fabric.IText.prototype.lineHeight = 1
fabric.IText.prototype._fontSizeMult = 1
fabric.IText.prototype._fontSizeFraction = 0.292
fabric.IText.prototype._fontSizeFractions = []
fabric.IText.prototype.maxLineMetricsWidth = 0
fabric.IText.prototype.lineSpace = undefined

fabric.IText.prototype.render = function (ctx) {
    this.clearContextTop();
    this.reInitDimensions();
    this.callSuper('render', ctx);
    this.cursorOffsetCache = {};
    this.renderCursorOrSelection();
}

fabric.IText.prototype.getLineSpace = function () {
    if (this.lineSpace !== undefined) {
        return this.lineSpace
    }

    if (this.canvas) {
        return this.lineSpace = this.canvas.getMarginPx()
    }

    return 0;
}

fabric.IText.prototype.reInitDimensions = function (fontFamily = null) {
    if (fontFamily) {
        this.set('fontFamily', fontFamily)
    }

    if (this.text.trim()) {
        this.canvas.contextContainer.font = `${this.fontSize}px ${this.fontFamily}`

        let maxLineHeight = 0;
        let maxLineWidth = 0;
        let maxLineMetricsWidth = 0;

        this._textLines.forEach((line, lineIndex) => {
            const lineText = line.join('').trim()
            if (lineText) {
                const metrics = this.canvas.contextContainer.measureText(lineText);
                const lineHeight = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
                const lineWidth = metrics.actualBoundingBoxRight - metrics.actualBoundingBoxLeft
                maxLineHeight = Math.max(maxLineHeight, lineHeight);
                maxLineWidth = Math.max(maxLineWidth, lineWidth);
                maxLineMetricsWidth = Math.max(maxLineMetricsWidth, metrics.width)
                this._fontSizeFractions[lineIndex] = metrics.actualBoundingBoxDescent / lineHeight
            }
        });

        this.horizontalSpan = maxLineWidth - maxLineMetricsWidth
        this._fontSizeFraction = Math.max(this._fontSizeFractions)

        this.initDimensions();
        this.setCoords();
    }
}

fabric.IText.prototype.getHeightOfLine = function (lineIndex) {
    if (this.canvas) {
        if (this.__lineHeights[lineIndex]) {
            return this.__lineHeights[lineIndex];
        }

        const line = this._textLines[lineIndex]
        const lineText = line.join('').trim()
        const {height} = fabric.util.measureText(this.canvas.contextContainer, lineText, this.fontFamily, this.fontSize)

        if (height > 0) {
            if (lineIndex === 0) {
                return this.__lineHeights[lineIndex] = height;
            } else {
                return this.__lineHeights[lineIndex] = height + this.getLineSpace() / this.scaleY;
            }
        }

        if (lineIndex === 0) {
            return this.fontSize
        }

        return this.__lineHeights[lineIndex - 1] || this.fontSize
    } else {
        const line = this._textLines[lineIndex]

        let maxHeight = this.getHeightOfChar(lineIndex, 0);
        for (let i = 1, len = line.length; i < len; i++) {
            maxHeight = Math.max(this.getHeightOfChar(lineIndex, i), maxHeight);
        }

        return maxHeight * this.lineHeight * this._fontSizeMult;
    }
}

fabric.IText.prototype._renderTextLine = function (method, ctx, line, left, top, lineIndex) {
    this._fontSizeFraction = this.getFontSizeFraction(lineIndex)
    this.callSuper('_renderTextLine', method, ctx, line, left, top, lineIndex)
}

fabric.IText.prototype._renderChar = function (method, ctx, lineIndex, charIndex, _char, left, top) {
    this.callSuper('_renderChar', method, ctx, lineIndex, charIndex, _char, left, top)
    this.baseLines[lineIndex] = top * this.scaleY;
    this.leftLines[lineIndex] = this._getLineLeftOffset(lineIndex) * this.scaleX;
}

fabric.IText.prototype.getLineWidth = function (lineIndex) {
    if (this.__lineWidths[lineIndex] !== undefined) {
        return this.__lineWidths[lineIndex];
    }
    let lineInfo = this.measureLine(lineIndex);
    let width = lineInfo.width;
    this.__lineWidths[lineIndex] = width;
    return width;
}

fabric.IText.prototype.calcTextWidth = function () {
    return this.callSuper('calcTextWidth') + this.horizontalSpan
}

fabric.IText.prototype.calcTextHeight = function () {
    if (this.text.trim()) {
        return this.callSuper('calcTextHeight')
    }

    return this.MIN_TEXT_HEIGHT
}

fabric.IText.prototype.getFontSizeFraction = function (lineIndex) {
    if (this._fontSizeFractions[lineIndex] !== undefined) {
        return this._fontSizeFractions[lineIndex]
    }

    if (lineIndex > 0) {
        return this.getFontSizeFraction(lineIndex - 1)
    }

    return this._fontSizeFraction
}

fabric.IText.prototype.renderCursor = function (boundaries, ctx) {
    let cursorLocation = this.get2DCursorLocation(),
        lineIndex = cursorLocation.lineIndex,
        charIndex = cursorLocation.charIndex > 0 ? cursorLocation.charIndex - 1 : 0,
        charHeight = this.getHeightOfLine(lineIndex),
        multiplier = this.scaleX * this.canvas.getZoom(),
        cursorWidth = this.cursorWidth / multiplier,
        topOffset = boundaries.topOffset;

    topOffset += (1 - this.getFontSizeFraction(lineIndex)) * this.getHeightOfLine(lineIndex) / this.lineHeight
        - charHeight * (1 - this.getFontSizeFraction(lineIndex));

    if (this.inCompositionMode) {
        this.renderSelection(boundaries, ctx);
    }

    ctx.fillStyle = this.cursorColor || this.getValueOfPropertyAt(lineIndex, charIndex, 'fill');
    ctx.globalAlpha = this.__isMousedown ? 1 : this._currentCursorOpacity;
    ctx.fillRect(
        boundaries.left + boundaries.leftOffset - cursorWidth / 2,
        topOffset + boundaries.top,
        cursorWidth,
        charHeight);
}

