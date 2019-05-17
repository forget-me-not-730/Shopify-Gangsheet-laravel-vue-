import {getterAndMutator} from "@/Builder/Mixins/mixin";
import builderMixin from "@/Builder/Mixins/builderMixin";

export default {
    mixins: [builderMixin],
    computed: {
        workingStickerIndex: getterAndMutator('workingDesignIndex'),
        workingStickers: getterAndMutator('workingDesigns'),
        currentSticker: {
            get() {
                return this.workingStickers[this.workingStickerIndex]
            },
            set(value) {
                this.workingStickers[this.workingStickerIndex] = value
            }
        },
        showArtBoardOutline: getterAndMutator('showArtBoardOutline'),
        showImageOutline: getterAndMutator('showImageOutline')
    },
    methods: {
        updateStickerCanvasData() {
            if (_stickerCanvasEditor) {
                const json = _stickerCanvasEditor.exportJson()
                if (!this.currentSticker) {
                    json.quantity = this.quantity
                }
                this.currentSticker = json
            }
        }
    }
}
