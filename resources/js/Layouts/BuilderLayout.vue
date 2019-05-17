<template>
    <Head ><title>{{ title }}</title></Head>
    <slot />
</template>

<script>
import { Head } from '@inertiajs/vue3';

export default {
    name: "BuilderLayout",
    components: {Head},
    props: {
        title: String
    },
}
</script>

<style>
    html,body, #app {
        height: 1px;
        min-height: 100%;
    }

    #app {
        height: 100%;
        max-height: 100%;
    }
</style>
