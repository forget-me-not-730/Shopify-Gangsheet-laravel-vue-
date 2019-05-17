<template>
    <div class="relative w-full h-full ">
        <img :src="src" class="w-full h-full object-contain" :draggable="true" @load="handleImageLoaded" @error="loading = false" alt="Image"/>
        <div v-if="loading" class="absolute w-full h-full bg-black inset-0 bg-opacity-25 z-50 flex items-center justify-center">
            <spinner/>
        </div>
    </div>
</template>

<script>
import builderMixin from "@/Builder/Mixins/builderMixin";
import Spinner from "@/Components/Spinner.vue";

export default {
    name: "GsbImage",
    components: {Spinner},
    mixins: [builderMixin],
    props: {
        src: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            loading: true
        }
    },
    methods: {
        handleImageLoaded() {
            this.loading = false
        }
    }
}
</script>

<style scoped>

</style>
