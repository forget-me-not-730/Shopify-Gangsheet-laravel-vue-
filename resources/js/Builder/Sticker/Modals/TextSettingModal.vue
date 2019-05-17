<script>
import {defineComponent} from "vue";
import Modal from "@/Builder/Modals/Modal.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import FontSettingPanel from "@/Builder/Sticker/FontSettingPanel.vue";
import stickerMixin from "@/Builder/Mixins/stickerMixin";
import {mdiFormatText} from "@mdi/js";
import SvgIcon from "@jamescoyle/vue-icon";

export default defineComponent({
  name: "TextSettingModal",
  components: {FontSettingPanel, CloseIcon, Modal, SvgIcon},
  mixins: [stickerMixin],
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    data: {
      type: [Object, null],
      default: null,
    },
  },
  data() {
    return {
      mdiFormatText,
    }
  },
  watch: {
    open: {
      immediate: true,
      handler() {
      },
    },
  },
  methods: {
    handleAddText() {
      if (_stickerCanvasEditor) {
        _stickerCanvasEditor.addText('Sticker', this.defaultFont)
        this.$emit('close')
      }
    }
  }
})
</script>

<template>
  <modal :open="open" @close="$emit('close')">
    <div class="w-full h-full flex flex-col justify-end">
      <div class="rounded bg-white flex flex-col h-max w-full">
        <div class="flex justify-between items-center relative px-4 py-2">
          <h6 class="text-lg font-bold">Text Editor</h6>
          <div class="cursor-pointer" @click="$emit('close')">
            <close-icon/>
          </div>
        </div>
        <hr>
        <div class="grow p-2 overflow-scroll">
          <div @click="handleAddText" class="btn-builder btn-sm w-1/2 mx-auto mt-4">
              <svg-icon type="mdi" :path="mdiFormatText" size="18"></svg-icon>
              <small>{{ $t('Add New Text') }}</small>
          </div>
          <font-setting-panel />
        </div>
      </div>
    </div>
  </modal>
</template>

<style scoped>

</style>
