<script>
import {defineComponent} from "vue";
import Modal from "@/Builder/Modals/Modal.vue";
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import UploadImages from "@/Builder/Sticker/StickerUploadImages.vue";
import ImageSettingPanel from "@/Builder/Sticker/ImageSettingPanel.vue";

export default defineComponent({
  name: "ImageEditModal",
  components: {ImageSettingPanel, CloseIcon, Modal},
  mixins: [],
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
    return {}
  },
  watch: {
    open: {
      immediate: true,
      handler() {
      },
    },
  }
})
</script>

<template>
  <modal :open="open" @close="$emit('close')">
    <div class="w-full h-full flex flex-col justify-end">
      <div class="rounded bg-white flex flex-col h-max w-full">
        <div class="flex justify-between items-center relative px-4 py-2">
          <h6 class="text-lg font-bold">Edit</h6>
          <div class="cursor-pointer" @click="$emit('close')">
            <close-icon/>
          </div>
        </div>
        <hr>
        <div class="grow p-2 overflow-scroll">
          <image-setting-panel />
        </div>
      </div>
    </div>
  </modal>
</template>

<style scoped>

</style>
