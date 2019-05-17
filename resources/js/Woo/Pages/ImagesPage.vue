<script>
import { defineComponent } from 'vue'
import LoginButton from '@/Woo/Components/LoginButton.vue'

export default defineComponent({
    name: 'ImagesPage',
    components: { LoginButton },
})
</script>

<template>
    <div class="p-5">
        <p>Please login to bags account to upload gallery images.</p>

        <LoginButton
            class="mt-5"
            title="Upload Images"
            page_url="/merchant/images#create"
        />
    </div>
</template>

<style scoped>

</style>
