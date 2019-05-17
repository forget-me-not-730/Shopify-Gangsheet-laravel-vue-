<script>
import { defineComponent } from "vue";
import SvgIcon from "@jamescoyle/vue-icon";
import { mdiChevronRight, mdiChevronDown, mdiDragVertical } from "@mdi/js";
import GallerySubCategory from "@/Components/Merchant/GallerySubCategory.vue";
import GalleryCategoryActions from "@/Components/Merchant/GalleryCategoryActions.vue";
import Spinner from "@/Builder/Components/Spinner.vue";
import GalleryDraggable from "@/Components/Merchant/GalleryDraggable.vue";
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";

export default defineComponent({
  name: "GalleryCategory",
  components: {
    GalleryDraggable,
    Spinner,
    GalleryCategoryActions,
    GallerySubCategory,
    SvgIcon,
  },
  mixins: [ConfirmationMixin],
  props: {
    path: {
      type: String,
      required: true,
    },
    prevCategory: {
      type: Object,
    },
    len: {
      type: Number,
      default: 0,
    },
  },
  data() {
    return {
      mdiChevronRight,
      mdiChevronDown,
      mdiDragVertical,
    };
  },

  mounted() {
    console.log(this.pageData.gallery);
  },

  computed: {
    category() {
      return _.get(this.pageData.gallery, this.path);
    },
    show() {
      if (this.pageData.search) {
        const search = this.pageData.search.toLowerCase();
        if (this.category.name.toLowerCase().includes(search)) {
          return true;
        }

        return this.category.children?.some((child) => {
          return child.name.toLowerCase().includes(search);
        });
      } else {
        return true;
      }
    },
    open() {
      if (this.pageData.search) {
        const search = this.pageData.search.toLowerCase();

        return (
          this.category.open ||
          this.category.children?.some((child) => {
            return child.name.toLowerCase().includes(search);
          })
        );
      } else {
        return this.category.open;
      }
    },
    isActive() {
      return (
        this.pageData.viewData === "gallery" && this.pageData.activePath === this.path
      );
    },
    totalImageCount() {
      const childrenImageCount =
        this.category.children?.reduce((sum, child) => {
          return sum + (child.images_count ?? 0) + this.getChildImageCount(child);
        }, 0) ?? 0;

      return (this.category.images_count ?? 0) + childrenImageCount;
    },
  },
  methods: {
    handleCategoryClick() {
      if (window.isCategoryDragging) {
        return;
      }

      if (this.category.children?.length) {
        if (this.category.open) {
          if (this.pageData.activePath === this.path) {
            this.category.open = false;
          }
        } else {
          this.category.open = true;
        }
      }

      this.pageData.searchImage = "";
      this.pageData.viewData = "gallery";
      this.pageData.activePath = this.path;
    },
    handleDraggableChange(e) {
      this.pageData.activePath = `${this.path}.children[${e.toIndex}]`;
      const category_ids = this.category.children.map(({ id }) => id);
      axios
        .post(route("merchant.image.category.reorder"), { ids: category_ids })
        .then((res) => {
          // ignore
        });
    },
    handleActionClick(e) {
      if (this.category.open) {
        e.preventDefault();
        e.stopImmediatePropagation();
        this.pageData.activePath = this.path;
      }
    },
    handleCategorySelectChange(e) {
      const subCategoryIds = (this.category.children || []).map(({ id }) => id);
      const imageIds = (this.category.images || []).map(({ id }) => id);

      if (e.target.checked) {
        this.pageData.selectedSubcategories.push(...subCategoryIds);
        this.pageData.selectedImages.push(...imageIds);

        this.pageData.selectedSubcategories = [
          ...new Set(this.pageData.selectedSubcategories),
        ];
        this.pageData.selectedImages = [...new Set(this.pageData.selectedImages)];
      } else {
        this.pageData.selectedSubcategories = this.pageData.selectedSubcategories.filter(
          (id) => !subCategoryIds.includes(id)
        );
        this.pageData.selectedImages = this.pageData.selectedImages.filter(
          (id) => !imageIds.includes(id)
        );
      }
    },

    getChildImageCount(category) {
      if (!category.children || category.children.length === 0) {
        return 0;
      }
      return category.children.reduce((sum, child) => {
        return sum + (child.images_count ?? 0) + this.getChildImageCount(child);
      }, 0);
    },
  },
});
</script>

<template>
  <div
    v-if="show"
    class="relative w-full gallery-category-item"
    :data-disable-draggable="category.open"
    :class="[this.pageData.activePath.startsWith(this.path) ? 'z-30' : 'z-20']"
  >
    <div
      class="gallery-category flex w-full items-center pr-2 py-2 cursor-pointer relative"
      :data-category-id="category.id"
      :style="{ paddingLeft: `${len * 20}px` }"
      :class="[isActive ? 'bg-gray-200' : 'hover:bg-gray-100']"
      @click="handleCategoryClick"
    >
      <div
        class="w-5 shrink-0 h-full"
        @click.stop.prevent="category.open = !category.open"
      >
        <svg-icon
          v-if="category.children?.length"
          type="mdi"
          :path="category.open ? mdiChevronDown : mdiChevronRight"
          size="20"
        />
      </div>
      <label @click.stop="">
        <input
          name="category"
          v-model="pageData.selectedCategories"
          @change="handleCategorySelectChange"
          :value="category.id"
          type="checkbox"
        />
      </label>
      <div class="text-sm ml-1 whitespace-nowrap overflow-hidden text-ellipsis">
        {{ category.name }}
      </div>
      <div class="text-xs ml-2 text-gray-400 flex items-center">
        <span>[</span>
        <span>{{ totalImageCount }}</span>
        <span>]</span>
      </div>
      <div
        @click="handleActionClick"
        class="h-full w-10 ml-auto flex items-center z-50 justify-evenly"
        :class="[isActive ? 'bg-gray-200' : 'bg-gray-50 hover:bg-gray-100']"
      >
        <div class="w-5">
          <svg-icon v-if="!category.open" type="mdi" :path="mdiDragVertical" size="16" />
        </div>
        <gallery-category-actions :path="path" />
      </div>
    </div>
    <div v-if="category.children?.length && open" class="w-full">
      <gallery-draggable
        v-model="category.children"
        :disabled="Boolean(pageData.search)"
        @exchange="handleDraggableChange"
      >
        <template #item="{ index }">
          <gallery-category :path="`${path}.children[${index}]`" :len="len + 1" />

          <!-- <gallery-sub-category :path="`${path}.children[${index}]`"/> -->
        </template>
      </gallery-draggable>
    </div>
  </div>
</template>

<style scoped>
.gallery-category-item.draggable-item {
  opacity: 0.8;
}

.gallery-category-item.hide::before {
  display: none;
}
</style>
