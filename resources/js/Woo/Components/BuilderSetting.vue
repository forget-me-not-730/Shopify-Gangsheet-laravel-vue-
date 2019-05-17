<script>
import { defineComponent } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import wooMixin from '@/Woo/WooMixin'
import { updateShopSettings } from '@/Woo/Apis/wooApi'

export default defineComponent({
    name: 'BuilderSetting',
    components: { DisclosureButton, DisclosurePanel, Disclosure },
    mixins: [wooMixin],
    data () {
        return {
            show_gallery: false,
            options: {
                btn_text: 'Build a Gang Sheet',
                btn_bg_color: '#000000',
                btn_fg_color: '#ffffff'
            }
        }
    },
    computed: {
        isValidButtonLabel () {
            return this.options.btn_text && this.options.btn_text.length <= 30
        }
    },
    mounted () {
        this.show_gallery = Boolean(this.shop.show_gallery)
        if (window.gs_options?.btn_text) {
            this.options.btn_text = window.gs_options.btn_text
        }
        if (window.gs_options?.btn_bg_color) {
            this.options.btn_bg_color = window.gs_options.btn_bg_color
        }
        if (window.gs_options?.btn_fg_color) {
            this.options.btn_fg_color = window.gs_options.btn_fg_color
        }
    },
    methods: {
        updateWooSettings () {
            updateShopSettings({ options: this.options }).then(res => {
                console.log(res)
            })
        }
    }
})
</script>

<template>
    <Disclosure v-slot="{ open }">
        <DisclosureButton class="w-full border-b items-center justify-between flex border-gray-200 font-semibold text-gray-700 py-2 px-4 relative cursor-pointer">
            <span><i class="mdi mdi-pencil-ruler mr-2"></i>Builder Setting</span>
            <i class="mdi" :class="[open ? 'mdi-chevron-up': 'mdi-chevron-down']"></i>
        </DisclosureButton>
        <DisclosurePanel class="text-gray-500 p-4 border-b space-y-3">
            <div>
                <label class="font-semibold">Button Label</label>
                <input
                    type="text" v-model="options.btn_text" required name="gangSheetFileName"
                    class="inp-default !text-xs !py-2 mt-1"
                    :class="[isValidButtonLabel ? 'border-gray-300 focus:ring-blue-500': 'border-red-500 focus:border-red-500 focus:ring-red-500']"
                />
            </div>

            <div>
                <label class="flex items-center space-x-2 w-max">
                    <span class="font-medium">Button Background Color</span>
                    <input type="color" v-model="options.btn_bg_color" required class="h-10 w-20" style="border: none"/>
                </label>
            </div>
            <div>
                <label class="flex items-center space-x-2 w-max">
                    <span class="font-medium">Button Text Color</span>
                    <input type="color" v-model="options.btn_fg_color" required class="h-10 w-20" style="border: none"/>
                </label>
            </div>

            <div>
                <span class="font-semibold">Image Gallery</span>
                <label class="space-x-2 flex items-center mt-2">
                    <input type="checkbox" v-model="show_gallery" :checked="show_gallery"
                           class="border-gray-300 text-primary focus:border-primary focus:ring focus:ring-offset-0 focus:ring-primary focus:ring-opacity-10">
                    <span>Show Gallery</span>
                </label>
            </div>
        </DisclosurePanel>
    </Disclosure>
</template>

<style scoped>

</style>
