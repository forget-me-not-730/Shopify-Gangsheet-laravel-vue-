<script>
import { defineComponent } from 'vue'
import Spinner from '@/Components/Spinner.vue'
import wooMixin from '@/Woo/WooMixin'
import { updateStoreSettings } from '@/Woo/Apis/gsbApi'
import BuilderSetting from '@/Woo/Components/BuilderSetting.vue'
import ImageSetting from '@/Woo/Components/ImageSetting.vue'
import ResolutionSetting from '@/Woo/Components/ResolutionSetting.vue'
import EmailsSetting from '@/Woo/Components/EmailsSetting.vue'
import GoogleDriveSetting from '@/Woo/Components/GoogleDriveSetting.vue'
import DropboxSetting from '@/Woo/Components/DropboxSetting.vue'
import AddCreditModal from '@/Woo/Modals/AddCreditModal.vue'
import LoginButton from '@/Woo/Components/LoginButton.vue'
import { upgradeVersion } from '@/Woo/Apis/wooApi'

const FILE_TYPES = [
    { label: 'PNG', value: 'image/png' },
    { label: 'JPG/JPEG', value: 'image/jpg,image/jpeg' },
    {label: 'SVG', value: 'image/svg,image/svg+xml'},
    { label: 'PSD', value: '.psd' },
    { label: 'AI/ESP', value: 'application/postscript' },
    { label: 'PDF', value: 'application/pdf' },
]

export default defineComponent({
    name: 'HomePage',
    components: {
        LoginButton,
        AddCreditModal,
        DropboxSetting,
        GoogleDriveSetting,
        EmailsSetting,
        ResolutionSetting,
        ImageSetting,
        BuilderSetting,
        Spinner
    },
    mixins: [wooMixin],
    data () {
        return {
            file_types: [...FILE_TYPES],
            saving: false,
            upgrading: false,
            openAddCreditModal: false,
            settings: {
                name: '',
                gangSheetFileName: '',
                credits: 0,
                logo: null
            }
        }
    },
    computed: {
        isValidFileName () {
            const name = this.settings.gangSheetFileName
                .replace('{order_name}', '')
                .replace('{customer_name}', '')
                .replace('{order_id}', '')
                .replace('{variant_title}', '')
                .replace('{quantity}', '')
                .replace('{line_item_number}', '')
                .replace('{shipping_method}', '')
                .replace('{sku}', '')

            return /^[a-zA-Z0-9\s_\-()$]+$/g.test(name)
        }
    },
    mounted () {
        const settings = this.shop.settings
        this.settings = {
            name: this.shop.company_name,
            file_types: settings?.file_types ?? [...FILE_TYPES.map(file_type => file_type.value)],
            gangSheetFileName: settings?.gangSheetFileName || '{order_name}-{customer_name}-{order_id}-{variant_title}-{line_item_number}-({quantity})',
            printFileName: settings?.printFileName || false,
            credits: this.shop.credits
        }
    },
    methods: {
        onChangeLogo ($event) {
            let file = $event.target.files[0]
            this.shop.logo_url = URL.createObjectURL(file)
            const reader = new FileReader()
            reader.onload = () => {
                this.settings.logo = reader.result
            }
            reader.readAsDataURL(file)
        },
        handleSave () {

            if (!this.$refs.builder.isValidButtonLabel) {
                return window.Toast.error({
                    message: 'Button label is invalid.'
                })
            }

            this.settings.show_gallery = this.$refs.builder.show_gallery

            this.saving = true

            this.$refs.builder.updateWooSettings()

            updateStoreSettings(this.settings).then(res => {
                if (res.data.success) {
                    window.Toast.success({
                        message: 'Successfully Updated!'
                    })
                } else {
                    window.Toast.error({
                        message: res.data.error
                    })
                }
            }).finally(() => {
                this.saving = false
            })
        },
        handleUpgrade () {
            this.upgrading = true
            upgradeVersion().then((res) => {
                if (res.data.success) {
                    setTimeout(() => {
                        window.location.reload()
                    }, 3000)
                } else {
                    window.location.href = 'https://app.buildagangsheet.com/plugins/gang-sheet-builder_latest.zip'
                    this.upgrading = false
                }
            }).catch(err => {
                console.error(err)
                window.location.href = 'https://app.buildagangsheet.com/plugins/gang-sheet-builder_latest.zip'
                this.upgrading = false
            })
        }
    }
})
</script>

<template>
    <div class="w-full py-2 text-sm">
        <add-credit-modal :open="openAddCreditModal" @close="openAddCreditModal = false"/>
        <div class="flex justify-between mb-4 px-5">
            <div class="flex items-center">
                <h4 class="text-xl font-semibold">Available Credits: ${{ this.settings.credits || 0 }}</h4>
                <button class="btn-primary ml-4" @click="openAddCreditModal = true">Add Credits</button>
            </div>
            <button class="btn-primary" @click="handleSave" :disabled="saving">
                <spinner v-if="saving" class="mr-1"/>
                Save
            </button>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="px-5 border-r border-gray-300 space-y-10">
                <div class="space-y-1">
                    <label>Store Name</label>
                    <input v-model="settings.name" class="inp-default !py-2"/>
                </div>

                <div class="mt-5">
                    <span class="font-semibold">Store Logo</span>
                    <div class="flex space-x-5 items-center pl-5 mt-2">
                        <img v-if="shop.logo_url" :src="shop.logo_url" alt="LOGO" class="h-20">
                        <label>
                            <input type="file" @input="onChangeLogo"/>
                        </label>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="font-semibold">Supported File Types</label>
                    <div class="grid sm:grid-cols-4 lg:grid-cols-6 mt-2 gap-4">
                        <label v-for="file_type in file_types" class="space-x-2 flex items-center cursor-pointer">
                            <input type="checkbox" v-model="settings.file_types" name="file_types" :value="file_type.value"
                                   class="border-gray-300 text-primary focus:border-primary focus:ring focus:ring-offset-0 focus:ring-primary focus:ring-opacity-10">
                            <span>{{ file_type.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="font-semibold">Gang Sheet File Name Format</label>
                    <input
                        type="text" v-model="settings.gangSheetFileName" required name="gangSheetFileName"
                        class="inp-default !text-xs !py-2 mt-1"
                        :class="[isValidFileName ? '': 'border-red-500 focus:border-red-500 focus:ring-red-500']"
                    />
                    <div class="flex items-center mt-1 text-xs text-orange-600">
                        Available replacements are {order_name}, {customer_name}, {order_id}, {variant_title}, {line_item_number}, {shipping_method}, {quantity}, {sku}
                    </div>
                    <label class="space-x-2 flex items-center mt-2">
                        <input type="checkbox" v-model="settings.printFileName" :checked="settings.printFileName"
                               class="border-gray-300 text-primary focus:border-primary focus:ring focus:ring-offset-0 focus:ring-primary focus:ring-opacity-10">
                        <span>Print File Name on top of Gang Sheet</span>
                    </label>
                </div>
            </div>
            <div class="p-5 flex flex-col">
                <builder-setting ref="builder"/>

                <LoginButton class="mt-5 w-max" title="Update More" page_url="/merchant/settings#settings"/>

                <div v-if="gs_latest_version === undefined || gs_latest_version !== gs_version" class="mt-auto">
                    There is a new version of plugin, please upgrade.
                    <br/>
                    Latest Version: {{ gs_latest_version || '1.2.0' }}
                    <br/>
                    <button class="btn-primary mt-3" :disabled="upgrading" @click="handleUpgrade">
                        <spinner v-if="upgrading" class="mr-1"/>
                        Download & Upgrade Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
