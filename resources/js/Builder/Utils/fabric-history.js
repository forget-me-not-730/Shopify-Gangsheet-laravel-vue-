//https://github.com/coxpert/fabric-history

/**
 * Override the initialize function for the _historyInit();
 */
fabric.Canvas.prototype.initialize = (function (originalFn) {
    return function (...args) {
        originalFn.call(this, ...args);
        this._historyInit();
        return this;
    };
})(fabric.Canvas.prototype.initialize);

/**
 * Override the dispose function for the _historyDispose();
 */
fabric.Canvas.prototype.dispose = (function (originalFn) {
    return function (...args) {
        originalFn.call(this, ...args);
        this._historyDispose();
        return this;
    };
})(fabric.Canvas.prototype.dispose);

/**
 * Returns current state of the string of the canvas
 */
fabric.Canvas.prototype._historyNext = function () {
    return this.toDatalessJSON(this.extraProps);
};

/**
 * Returns an object with fabricjs event mappings
 */
fabric.Canvas.prototype._historyEvents = function () {
    return {
        'object:added': e => {
            if (e.target.isPattern)
                return
            this._historySaveAction('object:added')
        },
        'object:removed': e => {
            if (e.target.isPattern)
                return
            this._historySaveAction('object:removed')
        },
        'object:modified': () => {
            this._historySaveAction('object:modified')
        },
        'object:skewing': () => {
            this._historySaveAction('object:skewing')
        },
        'object:scaling': () => {
            this._historySaveAction('object:scaling')
        },
        'object:updated': () => {
            this._historySaveAction('object:updated')
        },
    };
};

/**
 * Initialization of the plugin
 */
fabric.Canvas.prototype._historyInit = function () {
    this.historyUndo = [];
    this.historyRedo = [];
    this.historyNextState = this._historyNext();

    this.on(this._historyEvents());
};

/**
 * Remove the custom event listeners
 */
fabric.Canvas.prototype._historyDispose = function () {
    this.off(this._historyEvents());
};

let SAVE_GANG_SHEET_HISTORY_SAVE_TIMER = null
/**
 * It pushes the state of the canvas into history stack
 */
fabric.Canvas.prototype._historySaveAction = function (action) {
    if (this.historyProcessing || this.initializing) return;
    if (SAVE_GANG_SHEET_HISTORY_SAVE_TIMER !== null) {
        window.clearTimeout(SAVE_GANG_SHEET_HISTORY_SAVE_TIMER)
        SAVE_GANG_SHEET_HISTORY_SAVE_TIMER = null
    }

    const canvas = this
    // Debounce save action
    SAVE_GANG_SHEET_HISTORY_SAVE_TIMER = window.setTimeout(() => {
        const json = canvas.historyNextState;
        canvas.historyUndo.push(json);
        canvas.historyNextState = canvas._historyNext();
        canvas.fire('history:append', {json: json});
    }, 100)
};

/**
 * Undo to latest history.
 * Pop the latest state of the history. Re-render.
 * Also, pushes into redo history.
 */
fabric.Canvas.prototype.undo = function (callback) {
    // The undo process will render the new states of the objects
    // Therefore, object:added and object:modified events will triggered again
    // To ignore those events, we are setting a flag.
    this.historyProcessing = true;

    const history = this.historyUndo.pop();
    if (history) {
        // Push the current state to the redo history
        this.historyRedo.push(this._historyNext());
        this.historyNextState = history;
        this._loadHistory(history, 'history:undo', callback);
    } else {
        this.historyProcessing = false;
    }
};

/**
 * Redo to latest undo history.
 */
fabric.Canvas.prototype.redo = function (callback) {
    // The undo process will render the new states of the objects
    // Therefore, object:added and object:modified events will triggered again
    // To ignore those events, we are setting a flag.
    this.historyProcessing = true;
    const history = this.historyRedo.pop();
    if (history) {
        // Every redo action is actually a new action to the undo history
        this.historyUndo.push(this._historyNext());
        this.historyNextState = history;
        this._loadHistory(history, 'history:redo', callback);
    } else {
        this.historyProcessing = false;
    }
};

fabric.Canvas.prototype._loadHistory = function (history, event, callback) {
    var that = this;

    this.loadFromJSON(history, function () {
        that.renderAll();
        that.fire(event);
        that.historyProcessing = false;

        if (callback && typeof callback === 'function') callback();
    });
};

/**
 * Clear undo and redo history stacks
 */
fabric.Canvas.prototype.clearHistory = function () {
    this.historyUndo = [];
    this.historyRedo = [];
    this.fire('history:clear');
};

/**
 * On the history
 */
fabric.Canvas.prototype.onHistory = function () {
    this.historyProcessing = false;

    this._historySaveAction();
};

/**
 * Check if there are actions that can be undone
 */

fabric.Canvas.prototype.canUndo = function () {
    return this.historyUndo.length > 0;
};

/**
 * Check if there are actions that can be redone
 */
fabric.Canvas.prototype.canRedo = function () {
    return this.historyRedo.length > 0;
};

/**
 * Off the history
 */
fabric.Canvas.prototype.offHistory = function () {
    this.historyProcessing = true;
};
