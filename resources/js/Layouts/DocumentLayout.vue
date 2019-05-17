<script>
import {defineComponent} from 'vue'
import {Head} from '@inertiajs/vue3'

export default defineComponent({
    name: 'DocumentLayout',
    components: {Head}
})
</script>

<template>
    <div class="w-full p-5 bg-gray-900 text-gray-100 h-min pb-20">
        <Head>
            <title>Document</title>
        </Head>

        <slot/>
    </div>
</template>

<style lang="scss">

h1, h2, h3, h4, h5, h6 {
    font-weight: 700;
    margin-top: 1em;
    margin-bottom: 0.5em;
}

h1 {
    font-size: 36px;
}

h2 {
    font-size: 32px;
}

h3 {
    font-size: 28px;
}

h4 {
    font-size: 24px;
}

h5 {
    font-size: 20px;
}

h6 {
    font-size: 18px;
}

pre {
    margin-top: 1rem;
}

</style>
