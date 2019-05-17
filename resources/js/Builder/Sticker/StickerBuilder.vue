<script>
import {defineComponent} from "vue";
import SvgIcon from "@jamescoyle/vue-icon";
import SkCanvas from "./SkCanvas.vue";
import StickerSize from "./StickerSize.vue";
import {
    mdiRefresh,
    mdiFitToScreenOutline,
    mdiMagnifyMinusOutline,
    mdiMagnifyPlusOutline,
} from "@mdi/js";

import Spinner from "@/Components/Spinner.vue";

import rerenderMixin from "@/Builder/Mixins/rerenderMixin";
import stickerMixin from "@/Builder/Mixins/stickerMixin";
import snapMixin from "@/Builder/Mixins/snapMixin";
import eventBus from "@/Builder/Utils/eventBus";
import StickerSideBar from "./StickerSideBar.vue";
import StickerRightBar from "./StickerRightBar.vue";
import {getPixelSize} from "@/Builder/Utils/helpers";
import designSubmitMixin from "@/Builder/Mixins/designSubmitMixin";
import StickerControls from "./StickerControls.vue";
import {DESIGN_TYPES} from "@/Builder/Utils/constants";
import StickerMobileBar from "@/Builder/Sticker/StickerMobileBar.vue";
import initBuilderMixin from "../Mixins/initBuilderMixin";

window._stickerCanvasEditor = null;

export default defineComponent({
    name: "StickerBuilder",
    components: {
        StickerMobileBar,
        StickerControls,
        StickerRightBar,
        StickerSideBar,
        Spinner,
        StickerSize,
        SvgIcon,
        SkCanvas,
    },
    mixins: [rerenderMixin, stickerMixin, snapMixin, designSubmitMixin, initBuilderMixin],
    data() {
        return {
            stickerWidth: 0,
            stickerHeight: 0,

            screenWidth: 0,
            screenHeight: 0,

            originalTransform: [1, 0, 0, 1, 0, 0],
            zoom: 0,
            translateY: 0,
            translateX: 0,
            spaceKyePressed: false,
            mousePressed: false,
            objectSelected: false,

            editBoundBorderWidth: 0,
            refreshing: true,
            canvasLoading: false, // Add canvasLoading to data properties

            cutLine: null,

            mdiRefresh,
            mdiFitToScreenOutline,
            mdiMagnifyMinusOutline,
            mdiMagnifyPlusOutline,
        };
    },
    computed: {
        editorBoundStyle() {
            return {
                width: Math.round(this.stickerWidth * this.zoom) + 2 + "px",
                height: Math.round(this.stickerHeight * this.zoom) + 2 + "px",
                transform: `translate(${this.translateX}px, ${this.translateY}px)`,
            };
        },
        hRulerStyle() {
            return {
                width: Math.round(this.cutLine.width * this.zoom) + "px",
                left: `calc(50% - ${Math.round(this.cutLine.width * this.zoom) / 2}px)`,
                transform: `translate(${this.translateX}px, 0)`,
            };
        },
        vRulerStyle() {
            return {
                height: Math.round(this.cutLine.height * this.zoom) + "px",
                top: `calc(50% - ${Math.round(this.cutLine.height * this.zoom) / 2}px)`,
                transform: `translate(0, ${this.translateY}px)`,
            };
        },
        snapLineContainerStyle() {
            return {
                width:
                    Math.round(this.stickerWidth * this.zoom) +
                    this.editBoundBorderWidth * 2 +
                    "px",
                height:
                    Math.round(this.stickerHeight * this.zoom) +
                    this.editBoundBorderWidth * 2 +
                    "px",
                transform: `translate(${this.translateX}px, ${this.translateY}px)`,
            };
        },
        isMoving() {
            return this.spaceKyePressed && this.mousePressed;
        },
    },
    watch: {
        async variant() {
            await this.forceRerender();
        },
    },
    mounted() {
        document.onkeydown = this.handleKeyDown;
        document.onkeyup = this.handleKeyUp;
        document.onmousemove = this.handleMouseMove;
        document.onmouseup = this.handleMouseUp;

        this.handleRefresh();
        eventBus.$on(eventBus.REFRESH_BUILDER, this.handleRefreshBuilder);
        eventBus.$on(eventBus.SAVE_DESIGN, this.handleSaveDesign);
        eventBus.$on(eventBus.SAVE_ADD_CART_DESIGN, this.saveAndAddToCart);
        eventBus.$on(eventBus.CANVAS_LOADED, () => {
            this.loadingDesign = false
        })
        if (this.editMode) {
            this.loadingDesign = true
        }
    },
    unmounted() {
        document.onkeydown = null;
        document.onmousemove = null;

        eventBus.$off(eventBus.REFRESH_BUILDER, this.handleRefreshBuilder);
        eventBus.$off(eventBus.SAVE_DESIGN, this.handleSaveDesign);
        eventBus.$off(eventBus.SAVE_ADD_CART_DESIGN, this.saveAndAddToCart);
        eventBus.$off(eventBus.CANVAS_LOADED, () => {
            this.loadingDesign = false
        })
    },
    methods: {
        async saveAndAddToCart() {
            if (this.savingDesign === false) {
                this.savingDesign = true;
                _stickerCanvasEditor.clearHistory();

                NProgress.start();
                const json = _stickerCanvasEditor.exportJson();
                const thumbnail = await _stickerCanvasEditor.exportThumbnail();
                const res = await this.saveDesign(json, thumbnail, DESIGN_TYPES.STICKER);
                if (window.top === window.self) {
                    this.savingDesign = false;
                    this.updateStickerCanvasData();
                    NProgress.done();

                    window.Toast.success({
                        message: "Design saved successfully.",
                    });
                }

                if (res) {
                    _stickerCanvasEditor.setDesignId(res.data.design_id);
                    _stickerCanvasEditor.clearHistory();

                    const postData = this.getPostMessageData(json);
                    window.parent.postMessage(
                        {
                            action: "gs_add_to_cart",
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
                            admin_edit_url: res.data.admin_edit_url,
                            design_id: res.data.design_id,
                            ...postData,
                        },
                        "*"
                    );
                }
            }
        },
        async handleSaveDesign() {
            this.updateStickerCanvasData();

            if (this.savingDesign === false) {
                this.savingDesign = true;
                _stickerCanvasEditor.clearHistory();
                NProgress.start();

                const json = _stickerCanvasEditor.exportJson();
                const thumbnail = await _stickerCanvasEditor.exportThumbnail();
                const res = await this.saveDesign(json, thumbnail, DESIGN_TYPES.STICKER);
                if (window.top === window.self) {
                    this.savingDesign = false;
                    this.updateStickerCanvasData();
                    NProgress.done();

                    window.Toast.success({
                        message: "Design saved successfully.",
                    });
                }

                if (res) {
                    _stickerCanvasEditor.setDesignId(res.data.design_id);
                    _stickerCanvasEditor.clearHistory();

                    const postData = this.getPostMessageData(json);
                    window.parent.postMessage(
                        {
                            action: "gs_close_builder",
                            preview_url: res.data.preview_url,
                            download_url: res.data.download_url,
                            edit_url: res.data.edit_url,
                            admin_edit_url: res.data.admin_edit_url,
                            design_id: res.data.design_id,
                            ...postData,
                        },
                        "*"
                    );
                }
            }
        },
        handleStickerCanvasInitialized(canvas) {
            window._stickerCanvasEditor = canvas;

            this.refreshing = false;

            this.stickerWidth = canvas.stickerWidth;
            this.stickerHeight = canvas.stickerHeight;

            this.screenWidth = canvas.width;
            this.screenHeight = canvas.height;

            this.zoom = canvas.getZoom();

            this.$nextTick(() => {
                this.updateStickerCanvasData()
                eventBus.$emit(eventBus.CANVAS_INITIALIZED, canvas);
            });

            this.originalTransform = canvas.viewportTransform.slice();
            this.onCanvasTransform(this.originalTransform);

            const handleCutLineUpdate = (cutLine) => {
                if (cutLine) {
                    const cutLineRect = cutLine.getRect();

                    this.cutLine = {
                        width: cutLineRect.width,
                        height: cutLineRect.height,
                        widthLabel: Math.min(
                            this.variant.width,
                            cutLineRect.width / getPixelSize(this.variant.unit)
                        ).toFixed(2),
                        heightLabel: Math.min(
                            this.variant.height,
                            cutLineRect.height / getPixelSize(this.variant.unit)
                        ).toFixed(2),
                    };
                } else {
                    this.cutLine = null;
                }
            };

            handleCutLineUpdate(canvas.getCutLine());

            canvas.on({
                "mouse:down": this.enableSnapLines,
                "mouse:up": this.disableSnapLines,
                "object:snap": () => {
                    this.handleSnap(canvas);
                },
                "selection:created": () => {
                    this.objectSelected = true
                },
                "selection:cleared": () => {
                    this.objectSelected = false
                },
                "cut-line:updated": ({cutLine}) => {
                    handleCutLineUpdate(cutLine);
                },
                "canvas:cleared": () => {
                    this.cutLine = null;
                    this.handleRest();
                },
            });
        },
        handleKeyDown(e) {
            if (e.target.closest("input")) {
                return;
            }

            const key = e.which || e.keyCode;

            switch (key) {
                case 8: // Backspace
                    this.handleDelete();
                    break;
                case 32: // Space
                    this.spaceKyePressed = true;
                    break;
                case 37: // left arrow
                    this.handleMoveByKey("left");
                    break;
                case 38: // up arrow
                    this.handleMoveByKey("up");
                    break;
                case 39: // right arrow
                    this.handleMoveByKey("right");
                    break;
                case 40: // down arrow
                    this.handleMoveByKey("down");
                    break;
                case 46: // Delete
                    this.handleDelete();
                    break;
            }
        },
        handleDelete() {
            _stickerCanvasEditor?.deleteActiveObjects();
        },
        handleMoveByKey(direction) {
            _stickerCanvasEditor?.moveActiveObject(direction);
        },
        handleRefresh() {
            this.updateStickerCanvasData();
            this.refreshing = true;
            if (_stickerCanvasEditor) {
                _stickerCanvasEditor.getObjects().forEach((object) => {
                    if (object.id !== "background" && object.id !== "cut-line") {
                        object.set("selectable", false);
                    }
                });
            }
            this.handleRefreshBuilder();
        },
        async handleRefreshBuilder() {
            await this.forceRerender();
        },
        handleMouseDown(e) {
            this.mousePressed = true;
            this.touchStartX = e.clientX;
            this.touchStartY = e.clientY;
        },
        handleMouseUp() {
            this.mousePressed = false;
        },
        handleKeyUp(e) {
            this.spaceKyePressed = false;

            if (!e.ctrlKey) {
                return;
            }

            if (e.keyCode === 90) {
                this.handleUndo();
            }

            if (e.keyCode === 89) {
                this.handleRedo();
            }
        },
        handleUndo() {
            _stickerCanvasEditor?.undo();
        },
        handleRedo() {
            _stickerCanvasEditor?.redo();
        },
        handleMouseLeave() {
            this.mousePressed = false;
        },
        handleMouseMove(e) {
            const movementX = e.clientX - this.touchStartX;
            const movementY = e.clientY - this.touchStartY;

            if (this.isMoving) {
                e.preventDefault();
                e.stopImmediatePropagation();

                this.moveCanvasTransform(movementX, movementY);
            }

            this.touchStartX = e.clientX;
            this.touchStartY = e.clientY;
        },
        moveCanvasTransform(movementX, movementY) {
            if (_stickerCanvasEditor) {
                const newTransform = _stickerCanvasEditor.viewportTransform.slice();
                newTransform[4] += movementX || 0;
                newTransform[5] += movementY || 0;
                _stickerCanvasEditor.setViewportTransform(newTransform);
                this.onCanvasTransform(newTransform);
            }
        },
        handleZoomChange(point) {
            if (_stickerCanvasEditor) {
                point = point || {
                    x: this.screenWidth / 2,
                    y: this.screenHeight / 2,
                };
                const zoomPoint = new fabric.Point(point.x, point.y);
                _stickerCanvasEditor.zoomToPoint(zoomPoint, this.zoom);
                this.onCanvasTransform(_stickerCanvasEditor.viewportTransform.slice());
            }
        },
        handleScroll(e) {
            if (e.ctrlKey) {
                e.stopPropagation();

                const point = {x: e.layerX, y: e.layerY};

                if (e.deltaY > 0) {
                    this.zoom /= 1.1;
                    this.handleZoomChange(point);
                } else {
                    this.zoom *= 1.1;
                    this.handleZoomChange(point);
                }
            }
        },
        onCanvasTransform(transform) {
            this.translateX =
                transform[4] -
                ((this.originalTransform[0] - transform[0]) * this.screenWidth +
                    this.originalTransform[4] * 2) /
                2;
            this.translateY =
                transform[5] -
                ((this.originalTransform[3] - transform[3]) * this.screenHeight +
                    this.originalTransform[5] * 2) /
                2;
        },
        handleRest() {
            if (_stickerCanvasEditor) {
                const transform = this.originalTransform.slice();
                this.zoom = transform[0];
                _stickerCanvasEditor.setViewportTransform(transform);
                this.onCanvasTransform(transform);
            }
        },
        handleZoomIn(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.zoom *= 1.1;
            this.handleZoomChange();
        },
        handleZoomOut(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.zoom /= 1.1;
            this.handleZoomChange();
        },
    },
});
</script>

<template>
    <div class="w-full h-full py-0 sm:py-5 relative flex flex-col">
        <div
            v-if="loadingDesign"
            class="absolute inset-0 z-50 bg-black bg-opacity-10 flex items-center justify-center pointer-events-none"
        >
            <div
                class="shadow w-36 bg-gray-100 bg-opacity-70 flex flex-col items-center justify-center rounded h-20 space-y-2"
            >
                <span class="text-sm">Loading...</span>
                <spinner class="!w-6 !h-6"/>
            </div>
        </div>

        <div class="justify-center flex flex-col flex-1 h-px space-y-2 space-x-0 sm:space-x-2 sm:space-y-0 px-2 mb-16 sm:mb-0 sm:px-0 sm:flex-row">
            <sticker-mobile-bar/>
            <sticker-side-bar/>
            <div
                class="flex-1 flex-col w-full sm:w-px h-full max-w-[800px] p-4 bg-white rounded border border-gray-200 flex items-center justify-center"
            >
                <div class="hidden sm:block overflow-x-auto h-[40px] border-b">
                    <sticker-controls v-if="objectSelected"/>
                </div>
                <div
                    @mousedown="handleMouseDown"
                    @mouseleave="handleMouseLeave"
                    class="editor-root w-full h-full overflow-hidden relative"
                >
                    <template v-if="cutLine">
                        <div
                            class="absolute top-2 z-30 h-px bg-gray-400 flex items-center justify-between"
                            :style="hRulerStyle"
                        >
                            <div class="h-2 w-px bg-gray-400"></div>
                            <span class="text-xs bg-white px-2"
                            >{{ cutLine.widthLabel }} {{ this.variant.unit }}</span
                            >
                            <div class="h-2 w-px bg-gray-400"></div>
                        </div>

                        <div
                            class="absolute left-2 z-30 w-px bg-gray-400 flex flex-col items-center justify-between"
                            :style="vRulerStyle"
                        >
                            <div class="w-2 h-px bg-gray-400"></div>
                            <span class="text-xs bg-white py-2 vertical-lr rotate-180"
                            >{{ cutLine.heightLabel }} {{ this.variant.unit }}</span
                            >
                            <div class="w-2 h-px bg-gray-400"></div>
                        </div>
                    </template>

                    <div class="absolute left-4 bottom-2 z-30 flex space-x-2">
                        <div
                            class="cursor-pointer btn-builder px-0 py-0 rounded-full shadow-lg w-6 h-6"
                            @click="handleRefresh"
                        >
                            <svg-icon
                                type="mdi"
                                :path="mdiRefresh"
                                size="14"
                                :class="{ 'animate-spin': refreshing }"
                            />
                        </div>
                    </div>

                    <div
                        class="absolute w-max bottom-2 flex items-center right-4 z-30 rounded max-sm:text-xs"
                    >
                        <div
                            title="Fit to screen"
                            class="btn-builder max-sm:rounded-sm rounded mr-2 py-0.5 px-1 cursor-pointer"
                            @click="handleRest()"
                        >
                            <svg-icon type="mdi" size="14" :path="mdiFitToScreenOutline"/>
                        </div>
                        <div
                            class="max-sm:w-5 max-sm:h-5 w-6 h-[22px] bg-builder border rounded flex items-center justify-center cursor-pointer"
                            @click="handleZoomOut"
                        >
                            <svg-icon type="mdi" :path="mdiMagnifyMinusOutline" size="16"/>
                        </div>
                        <span class="px-1 w-14 text-center text-xs"
                        >{{ (zoom * 100).toFixed(2) }}%</span
                        >
                        <div
                            class="max-sm:w-5 max-sm:h-5 w-6 h-[22px] bg-builder border rounded flex items-center justify-center cursor-pointer"
                            @click="handleZoomIn"
                        >
                            <svg-icon type="mdi" :path="mdiMagnifyPlusOutline" size="16"/>
                        </div>
                    </div>

                    <div
                        v-if="renderComponent"
                        ref="editorRef"
                        class="editor-wrap w-full h-full relative bg-gray-100"
                        @wheel.prevent="handleScroll"
                    >
                        <div v-if="showArtBoardOutline"
                             class="editor-bound relative transparent-pattern" :style="editorBoundStyle"/>

                        <div
                            class="flex items-center justify-center absolute left-0 top-0 w-full h-full"
                        >
                            <sk-canvas
                                :options="{
                                      stickerOutlineWidth: productSettings?.stickerOutlineWidth ?? 1,
                                      stickerOutlineColor: productSettings?.stickerOutlineColor ?? '#000000',
                                    }"
                                :variant="variant"
                                :compact="0.9"
                                :json-data="currentSticker"
                                @initialized="handleStickerCanvasInitialized"
                            />
                        </div>

                        <div
                            class="snap-line-container absolute pointer-events-none"
                            :style="snapLineContainerStyle"
                        >
                            <template v-if="snapEnabled">
                                <div
                                    v-for="(positionX, index) in snapXPositions"
                                    :key="index"
                                    class="absolute w-px h-[5000px] -top-[2000px] bg-info z-50"
                                    :style="{ left: positionX * zoom + 'px' }"
                                ></div>
                                <div
                                    v-for="(positionY, index) in snapYPositions"
                                    :key="index"
                                    class="absolute h-px w-[5000px] -left-[2000px] bg-info z-50"
                                    :style="{ top: positionY * zoom + 'px' }"
                                ></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <sticker-right-bar/>
        </div>
    </div>
</template>

<style>
.editor-bound {
    outline: none !important;
}
</style>
