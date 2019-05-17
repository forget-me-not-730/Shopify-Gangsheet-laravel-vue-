<script>
import { defineComponent } from 'vue'
import BuilderMixin from '@/Builder/Mixins/builderMixin'
import Spinner from '@/Components/Spinner.vue'

export default defineComponent({
    name: "GalleryImages",
    mixins: [BuilderMixin],
    components: { Spinner },
    data() {
        return {
            category: '',
            search: null,
            categories: null,
            loading: false,
            currentPage: 1,
            lastPage: 1,
            merchantImages: null,
            observer: null,
        }
    },
    mounted() {
        if (!this.categories) {
            this.getCategories()
        }
        this.observer = new IntersectionObserver(this.handleObserver, {
            root: null,
            rootMargin: '0px',
            threshold: 1.0
        })
        this.observer.observe(document.querySelector('#scroll-trigger'))
    },
    destroyed() {
        if (this.observer) {
            this.observer.disconnect()
        }
    },
    computed: {
        options() {
            return (this.categories || []).map((category) => {
                return {
                    value: category.id,
                    label: category.name,
                    children: category.children.map(item => {
                        return {
                            value: item.id,
                            label: item.name
                        }
                    })
                }
            })
        }
    },
    watch: {
        category() {
            this.currentPage = 1
            this.getImages()
        }
    },
    methods: {
        getCategories() {
            axios.get(route('builder.shop-image-categories', {
                user_id: this.shop.id
            })).then((res) => {
                if (res && res.data) {
                    this.categories = res.data
                }
            }).catch((e) => {
                Toast.error({
                    message: 'Failed to load Categories'
                })
            })
        },
        getImages() {
            if (this.loading) {
                return
            }
            this.loading = true
            axios.get(route('builder.shop-images', {
                shop_id: this.shop.id,
                page: this.currentPage,
                category_id: this.category,
                search: this.search,
                perPage: this.perPage,
            })).then((res) => {
                if (res && res.data) {
                    this.merchantImages = [...(this.merchantImages || []), ...res.data.data]
                    this.currentPage = res.data.current_page
                    this.lastPage = res.data.last_page
                }
                this.loading = false
            }).catch((e) => {
                Toast.error({
                    message: 'Failed to load images'
                })
                this.loading = false
            })
        },
        handleObserver(entries) {
            const target = entries[0]
            if (target.isIntersecting && !this.loading && this.merchantImages && this.current_page < this.last_page) {
                this.currentPage++
                this.getImages()
            }
        },
        onSearch(e) {
            if (this.timer) {
                clearTimeout(this.timer)
            }
            this.timer = setTimeout(() => {
                this.getImages()
            }, 300)
        },
        addImage(image) {
            const newImage = {
                url: image.original_url,
            }
            if (_stickerCanvasEditor) {
                _stickerCanvasEditor.addImage(newImage)
            }
        }
    }
})
</script>

<template>
    <div class="w-full p-2">
        <div class="grid xs:grid-cols-2 gap-1 xs:gap-2">
            <el-tree-select v-model="category" :data="options" :render-after-expand="false" filterable check-strictly clearable placeholder="Select Category..."
                :default-expand-all="true" />
            <input type="text" v-model="search" @input="onSearch" class="w-full border border-gray-300 py-0 rounded" :placeholder="$t('Search')">
        </div>
        <div class="relative min-h-[10rem] mt-2">
            <template v-if="merchantImages">
                <div class="grid md:grid-cols-3 grid-cols-2 gap-2">
                    <div v-for="image in merchantImages" class="aspect-square border cursor-pointer bg-gray-200 border-gray-300 hover:border-primary"
                        :key="image.id">
                        <img :src="image.preview_url" alt="" @click="addImage(image)" class="w-full h-full object-contain" />
                    </div>
                </div>
                <div v-if="!merchantImages.length && !loading" class="py-16 text-inherit text-3xl font-bold">
                    {{ $t('No Images') }}
                </div>
            </template>
            <div v-if="loading" class="absolute top-0 left-0 bg-black bg-opacity-20 h-full w-full flex items-center justify-center">
                <spinner class="!h-6 !w-6" />
            </div>
            <div id="scroll-trigger"></div>
        </div>
    </div>
</template>

<style>
.el-select__input.el-select__input--iOS {
    background-color: transparent !important;
}

.el-select,
.el-input__wrapper,
.select-trigger.el-tooltip__trigger.el-tooltip__trigger {
    background-color: transparent !important;
}
</style>
