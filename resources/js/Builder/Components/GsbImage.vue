<template>
    <div class="relative w-full h-full" :class="{'border border-gray-300 overflow-hidden' : !show || loading}">
        <img v-if="src && show && !error" :src="src" class="w-full h-full object-contain" :draggable="true" @load="handleImageLoaded" @error="onError" alt="Image"/>
        <div v-if="loading || !show" class="absolute inset-0 flex-center">
            <div class="w-full h-full absolute bg-gray-300 bg-opacity-50"></div>
            <spinner/>
        </div>
    </div>
</template>

<script>
import builderMixin from '@/Builder/Mixins/builderMixin'
import Spinner from '@/Components/Spinner.vue'

export default {
    name: 'GsbImage',
    components: {Spinner},
    mixins: [builderMixin],
    props: {
        src: {
            type: [String, null],
            required: true
        }
    },
    data() {
        return {
            loading: true,
            show: false,
            error: false
        }
    },
    mounted() {
        const lazyLoad = (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.show = true
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
    },
    watch: {
        src() {
            this.loading = true
            this.error = false
        }
    },
    methods: {
        handleImageLoaded() {
            this.loading = false
        },
        onError() {
            this.loading = false
            this.error = true
        }
    }
}
</script>

<style scoped>

</style>
