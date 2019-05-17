<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import MerchantLayout from "@/Layouts/MerchantLayout.vue";

export default defineComponent({
    name: "GallerySharingPage",
    components: {MerchantLayout, SvgIcon},
    props: {
        galleryStore: {
            type: Object,
            required: true
        }
    },
})
</script>

<template>
    <merchant-layout title="Gallery">
        <div class="w-full py-3 px-6 min-h-full">
            <div class="flex items-center justify-center flex-col p-20">
                <h3 class="text-base font-bold">Shared Gallery</h3>
                <p class="max-w-md text-center mt-3">
                    This store is using the shared gallery images with store <b>{{ galleryStore.shop_name }}</b>.
                    <br/>
                    Contact the support team to stop sharing the gallery.
                </p>
            </div>
        </div>
    </merchant-layout>
</template>

<style scoped>

</style>
