<template>
    <div class="w-full">
        <label class="text-xs font-semibold">Tags</label>
        <div v-selector="tagNames" class="w-full">
            <div class="inp-default flex p-0 overflow-hidden mt-1">
                <div v-for="(tag, index) in tags"
                     class="flex text-gray-600 whitespace-nowrap text-xs items-center rounded bg-gray-200 px-1 m-1">
                    <span class="capitalize">{{ tag }}</span>
                    <svg-icon type="mdi" :path="mdiCloseCircleOutline" size="16" class="mt-px ml-1 cursor-pointer" @click="handleRemoveTag(index)"/>
                </div>
                <div class="flex-1">
                    <input type="text" :disabled="disabled" class="w-full text-xs border-0 py-0 px-1 h-8 focus:ring-0" @keydown="handleTagEnter" @change="handleAddTag"/>
                </div>
            </div>
        </div>
        <span class="text-xs text-gray-400">Type and press Enter to add multiple tags.</span>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiCloseCircleOutline} from '@mdi/js'

export default defineComponent({
    name: "TagsInput",
    components: {SvgIcon},
    props: {
        modelValue: {
            type: Array,
            required: true
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            mdiCloseCircleOutline
        }
    },
    methods: {
        handleTagEnter(e) {
            const key = e.which || e.keyCode
            if (key === 13) {
                e.preventDefault()
                this.handleAddTag(e)
            }
        },
        handleAddTag(e) {
            const tag = e.target.value.trim()
            if (tag && !this.tags.filter(t => t.toLowerCase() === tag.toLowerCase()).length) {
                this.tags.push(tag)
            }
            e.target.value = ''
        },
        handleRemoveTag(index) {
            this.tags.splice(index, 1)
        },
    },
    computed: {
        tagNames() {
            return this.pageData.tags.map(tag => tag.name)
        },
        tags: {
            get() {
                return this.modelValue
            },
            set(value) {
                this.$emit('update:modelValue', value)
            }
        }
    }
})
</script>
