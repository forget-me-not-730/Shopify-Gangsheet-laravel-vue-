<script>
import { defineComponent } from "vue";
import SvgIcon from "@jamescoyle/vue-icon";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import { mdiDotsVertical } from "@mdi/js";
import PromptMixin from "@/Mixins/PromptMixin";
import ConfirmationMixin from "@/Mixins/ConfirmationMixin";
import GalleryMixin from "@/Components/Merchant/GalleryMixin";

export default defineComponent({
  name: "GalleryCategoryActions",
  components: { MenuItem, MenuItems, MenuButton, Menu, SvgIcon },
  mixins: [PromptMixin, ConfirmationMixin, GalleryMixin],
  props: {
    path: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      mdiDotsVertical,
    };
  },
  computed: {
    category() {
      return _.get(this.pageData.gallery, this.path);
    },
    canDelete() {
      return (
        !Boolean(this.category.images_count) && !Boolean(this.category.children?.length)
      );
    },
  },
  methods: {
    handleAddCategory() {
      this.prompt = {
        title: "Add Subcategory",
        id: null,
        placeholder: "Enter category name",
        validator: this.validatorCategoryName,
        action: "Add",
        onConfirm: async (inputValue, imageValue, enableColorOverlay) => {
          await axios
            .post(route("merchant.image.category.create"), {
              category_id: this.category.id,
              name: inputValue,
              image: imageValue,
              color_overlay: enableColorOverlay,
            })
            .then((res) => {

              if (res.data.success) {
                if (!this.category.children) {
                  this.category.children = [res.data.category];
                } else {
                  this.category.children.push(res.data.category);
                }
                this.category.open = true;
              }
            });
        },
      };
    },
    handleCategoryEdit() {
      this.prompt = {
        title: "Edit Category",
        id: this.category.id,
        placeholder: "Enter category name",
        action: "Update",
        value: this.category.name,
        image_url: this.category.image_url,
        enableColorOverlay: this.category.color_overlay,
        onConfirm: async (
          inputValue,
          imageValue,
          enableColorOverlay,
          hasOldImage,
          oldImageUrl
        ) => {
          try {
            await axios
              .post(
                route("merchant.image.category.update", {
                  category_id: this.category.id,
                }),
                {
                  name: inputValue,
                  image: imageValue,
                  color_overlay: enableColorOverlay,
                  hasOldImage: oldImageUrl ? !hasOldImage : hasOldImage,
                }
              )
              .then((res) => {
                if (res.data.success) {
                  this.category.name = inputValue;
                  this.category.color_overlay = enableColorOverlay;
                  window.Toast.success({
                    message: "Category updated successfully.",
                  });
                } else {
                  window.Toast.error({
                    message: "Failed to update Category.",
                  });
                }
              })
              .catch((error) => {
                console.error("Error updating category:", error);
              });
          } catch (error) {
            console.error("Error in onConfirm:", error);
          }
        },
      };
    },
    handleCategoryDelete() {
      if (this.canDelete) {
        this.confirmation = {
          title: "Remove Category?",
          description: `Are you really sure you want to remove ${this.category.name} category?`,
          action: "Remove",
          type: "danger",
          onConfirm: async () => {
            await axios
              .delete(
                route("merchant.image.category.delete", { category_id: this.category.id })
              )
              .then((res) => {
                if (res.data.success) {
                  const parentPath = this.path.split(".").slice(0, -1).join(".");

                  _.unset(this.pageData.gallery, this.path);

                  if (parentPath) {
                    const parent = _.get(this.pageData.gallery, parentPath);
                    parent.children = _.compact(parent.children);
                  } else {
                    this.pageData.gallery = _.compact(this.pageData.gallery);
                  }

                  if (this.pageData.activePath === this.path) {
                    if (!_.get(this.pageData.gallery, this.path)) {
                      const regex = /\[(\d+)]$/g;
                      const index = regex.exec(this.path)[1];
                      if (index > 0) {
                        this.pageData.activePath = this.path.replace(
                          regex,
                          `[${index - 1}]`
                        );
                      } else {
                        this.pageData.activePath = parentPath;
                      }
                    }

                    this.loadActiveCategoryImages();
                  }

                  window.Toast.success({
                    message: "Category deleted successfully.",
                  });
                } else {
                  window.Toast.error({
                    message: "Failed to delete Category.",
                  });
                }
              });
          },
        };
      }
    },
    handleAddImages() {
      this.pageData.viewData = "gallery";
      this.pageData.uploadImage = true;
    },
  },
});
</script>

<template>
  <Menu as="div" class="relative inline-block text-left mx-px my-px z-50">
    <div class="w-full h-full flex items-center justify-center">
      <menu-button>
        <svg-icon type="mdi" :path="mdiDotsVertical" size="20" />
      </menu-button>
    </div>
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <menu-items
        class="absolute right-0 z-60 mt-1 origin-top-right rounded-md bg-builder shadow-lg ring-1 ring-black bg-white ring-opacity-5 focus:outline-none"
      >
        <div class="py-1 z-50 w-36">
          <menu-item>
            <div
              class="cursor-pointer block whitespace-nowrap px-2 py-1.5 text-sm text-gray-700"
              @click="handleCategoryEdit"
            >
              Edit
            </div>
          </menu-item>
          <menu-item >
            <div
              class="cursor-pointer block whitespace-nowrap px-2 py-1.5 text-sm text-gray-700"
              @click="handleAddCategory"
            >
              Add Subcategory
            </div>
          </menu-item>
          <!-- v-if="!category.parent_id" -->
          <menu-item >
            <div
              class="cursor-pointer block whitespace-nowrap px-2 py-1.5 text-sm text-gray-700"
              @click="handleAddImages"
            >
              Add Images
            </div>
          </menu-item>
          <hr />
          <menu-item :disabled="!canDelete">
            <div
              class="cursor-pointer block whitespace-nowrap px-2 py-1.5 text-sm text-red-700"
              :class="{ '!text-gray-300': !canDelete }"
              @click="handleCategoryDelete"
            >
              Delete
            </div>
          </menu-item>
        </div>
      </menu-items>
    </transition>
  </Menu>
</template>
