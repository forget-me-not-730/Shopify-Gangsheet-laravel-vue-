<script>
import {defineComponent} from 'vue'
import DocumentLayout from '@/Layouts/DocumentLayout.vue'
import MarkdownIt from 'markdown-it'
import MarkdownItHighlightjs from 'markdown-it-highlightjs'
import hljs from 'highlight.js/lib/core'

export default defineComponent({
    name: 'CustomIntegration',
    components: {DocumentLayout},
    props: {
        document: Object
    },
    methods: {
        getHtml() {
            const markdown = new MarkdownIt()
                .use(MarkdownItHighlightjs, {hljs})

            return markdown.render(this.document.content)
        }
    }
})
</script>

<template>
    <document-layout>
        <div v-html="getHtml()"></div>
    </document-layout>
</template>

<style lang="scss">
@import "highlight.js/styles/stackoverflow-dark.min.css";
</style>
