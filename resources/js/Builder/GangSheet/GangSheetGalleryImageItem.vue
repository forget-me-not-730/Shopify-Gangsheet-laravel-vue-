<template>
    <div class="aspect-square flex items-center relative cursor-pointer bg-gray-400 bg-opacity-20" style="--cell-size: 8px;" @click="importImage">
        <gsb-image :src="image.preview_url" class="w-full h-full object-contain" alt=""/>
        <div v-if="loading" class="absolute w-full h-full bg-black bg-opacity-50 flex flex-col items-center justify-center">
            <spinner/>
        </div>
        <div class="btn-builder px-0 rounded-sm text-white h-4 min-w-[16px] absolute bottom-0 right-0 justify-center items-center flex text-xs">
            <image-counter :url="image.url"/>
        </div>
    </div>
</template>

<script>
import builderMixin from '@/Builder/Mixins/builderMixin'
import ImageCounter from '@/Builder/Components/ImageCounter.vue'
import Spinner from "@/Components/Spinner.vue";
import GsbImage from "@/Builder/Components/GsbImage.vue";

export default {
    name: 'GangSheetGalleryImageItem',
    components: {GsbImage, Spinner, ImageCounter},
    mixins: [builderMixin],
    props: {
        image: {
            type: [Object],
            required: true
        }
    },
    data() {
        return {
            loading: true,
        }
    },
    mounted() {
        if (!fabric.util.isSvg(this.image.original_url)) {
            if (!this.image.width || !this.image.height) {
                const lazyLoad = (entries, observer) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.handleImageLoad()
                            observer.unobserve(entry.target);
                        }
                    });
                };

                const observer = new IntersectionObserver(lazyLoad, {
                    root: null, // Use the viewport as the root
                    rootMargin: '0px', // No margin
                    threshold: 0.2, // Trigger when 10% of the element is in the viewport
                });

                observer.observe(this.$el);
            } else {
                this.loading = false
            }
        } else {
            this.loading = false
        }
    },
    methods: {
        handleImageLoad() {
            const image = new Image()
            image.onload = () => {
                this.image.width = image.width
                this.image.height = image.height
                this.loading = false
            }
            image.src = this.image.url
        },
        importImage() {
            if (!this.loading && this.image.url) {
                this.$gsb.addGalleryImageToStoreImages(this.image)
                this.$emit('close')
            }
        }
    }
}
</script>

<style scoped>
</style>
