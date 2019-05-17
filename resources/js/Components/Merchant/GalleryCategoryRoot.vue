<script>
import { defineComponent } from "vue";
import SvgIcon from "@jamescoyle/vue-icon";
import { mdiHome } from "@mdi/js";
import GallerySubCategory from "@/Components/Merchant/GallerySubCategory.vue";
import GalleryCategoryActions from "@/Components/Merchant/GalleryCategoryActions.vue";
import Spinner from "@/Builder/Components/Spinner.vue";
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";

export default defineComponent({
  name: "GalleryCategoryRoot",
  components: { Spinner, GalleryCategoryActions, GallerySubCategory, SvgIcon },
  mixins: [ConfirmationMixin],
  data() {
    return {
      open: true,
      mdiHome,
    };
  },
  computed: {
    isActive() {
      return this.pageData.viewData === "gallery" && this.pageData.activePath === "";
    },
  },
  methods: {
    handleCategoryClick() {
      this.pageData.searchImage = "";
      this.pageData.viewData = "gallery";
      this.pageData.activePath = "";
    },
  },
});
</script>

<template>
  <div class="w-full gallery-category-root" :class="'z-30'">
    <div
      class="flex w-full items-center pl-2 pr-4 py-2 cursor-pointer relative"
      data-category-id="root"
      :class="{ 'bg-gray-200': isActive }"
      @click="handleCategoryClick"
    >
      <div class="w-5 shrink-0 h-full">
        <svg-icon type="mdi" :path="mdiHome" size="20" />
      </div>
      <div class="text-sm ml-1 whitespace-nowrap overflow-hidden text-ellipsis">
        All Categories
      </div>
    </div>
  </div>
</template>
