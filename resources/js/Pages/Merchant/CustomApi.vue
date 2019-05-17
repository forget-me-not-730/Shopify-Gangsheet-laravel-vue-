<script>
import {defineComponent} from 'vue'
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import confirmationMixin from '@/Mixins/ConfirmationMixin'
import CreateApiTokenModal from '@/Components/Modals/CreateApiTokenModal.vue'
import {router} from '@inertiajs/vue3'
import MarkdownIt from 'markdown-it'
import MarkdownItHighlightjs from 'markdown-it-highlightjs'
import hljs from 'highlight.js/lib/core'
import DocumentLayout from '@/Layouts/DocumentLayout.vue'
import SvgIcon from "@jamescoyle/vue-icon";
import {mdiCheck, mdiContentCopy} from "@mdi/js";

export default defineComponent({
    name: 'CustomStoreDashboard',
    components: {SvgIcon, DocumentLayout, CreateApiTokenModal, MerchantLayout},
    mixins: [confirmationMixin],
    props: {
        merchant: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            isShopIdCopied: false,
            isShopTokenCopied: false,
            openCreateTokenModal: false,
            isSavingWebhooks: false,
            webhookGangSheetCompleted: this.$props.merchant.settings?.webhookGangSheetCompleted ?? '',

            apiContents: {
                generateDesign: {
                    url: '/docs/design-generation.md',
                    content: null
                },
                getDesign: {
                    url: '/docs/design-get.md',
                    content: null
                }
            },

            mdiContentCopy,
            mdiCheck
        }
    },
    mounted() {
        Object.keys(this.apiContents).forEach(key => {
            fetch(this.apiContents[key].url)
                .then(response => response.text())
                .then(data => {
                    const markdown = new MarkdownIt()
                        .use(MarkdownItHighlightjs, {hljs})

                    this.apiContents[key].content = markdown.render(data)
                })
        })
    },
    methods: {
        handleApiTokenCreateSuccess() {
            this.openCreateTokenModal = false
            router.reload({
                only: ['merchant']
            })
        },
        deleteToken(tokenId) {
            axios.post(route('merchant.delete-token'), {
                token_id: tokenId
            }).then(response => {
                if (response.data.success) {
                    window.Toast.success({
                        message: response.data.message
                    })
                    this.merchant.tokens = this.merchant.tokens.filter(token => token.id !== tokenId)
                } else {
                    window.Toast.error({
                        message: response.data.error
                    })
                }
            }).catch(error => {
                console.log(error.response.data)
            })

        },
        handleDeleteApiToken(tokenId) {
            this.confirmation = {
                title: 'Delete',
                description: `
                        Please make sure you delete API token.
                        You will not be able to use this API token.
                    `,
                onConfirm: () => {
                    this.deleteToken(tokenId)
                },
                onCancel: () => {
                }
            }
        },
        handleSaveWebhooks() {
            this.isSavingWebhooks = true
            NProgress.start()
            axios.put(route('merchant.save-webhooks'), {
                webhookGangSheetCompleted: this.webhookGangSheetCompleted
            }).then(response => {
                if (response.data.success) {
                    window.Toast.success({
                        message: 'Webhooks saved successfully'
                    })
                } else {
                    window.Toast.error({
                        message: 'Can not save webhooks'
                    })
                }
            }).catch(error => {
                console.log(error)
            }).finally(() => {
                this.isSavingWebhooks = false
                NProgress.done()
            })
        },
        handleCopyShopId() {
            navigator.clipboard.writeText(this.$page.props.auth.user.shop_uuid).then(() => {
                this.isShopIdCopied = true
                setTimeout(() => {
                    this.isShopIdCopied = false
                }, 800)
            })
        },
        handleCopyShopToken() {
            navigator.clipboard.writeText(this.$page.props.auth.user.settings.plainTextToken).then(() => {
                this.isShopTokenCopied = true
                setTimeout(() => {
                    this.isShopTokenCopied = false
                }, 800)
            })
        }
    }
})
</script>

<template>
    <MerchantLayout title="Custom API">

        <create-api-token-modal v-if="openCreateTokenModal" @success="handleApiTokenCreateSuccess" @close="openCreateTokenModal = false"/>

        <div class="grid md:grid-cols-2 gap-4 mx-auto mt-5">
            <div class="rounded shadow p-5 border border-gray-200">
                <div v-if="$page.props.auth.user.type === 'custom'" class="w-full">
                    <h5 class="font-bold text-xl">Custom Integration</h5>
                    <p class="pt-2">
                        You can integrate our gang sheet builder into your custom website instead of using Bags products. <br/>
                        With this feature, you can manage products and orders directly on your site, using our builder solely to create gang sheets. <br/>
                        We offer an API to facilitate gang sheet management. <br/>
                        Check <a href="https://demo.buildagangsheet.com" target="_blank" class="text-blue-500 hover:underline">Demo Website</a> and
                        <a href="https://app.buildagangsheet.com/doc/custom-integration" target="_blank" class="text-blue-500 hover:underline">API Documentation</a> for more information.
                    </p>
                </div>

                <h5 class="text-base font-bold">Shop Credentials</h5>
                <div class="space-y-4 mt-4">
                    <div>
                        <label class="pl-1">Shop ID</label>
                        <div class="border px-2 rounded py-2 mt-1 flex items-center justify-between">
                            {{ $page.props.auth.user.shop_uuid }}
                            <button
                                @click="handleCopyShopId"
                                class="w-5 h-5 flex items-center justify-center relative"
                            >
                                <svg-icon v-if="isShopIdCopied" type="mdi" :path="mdiCheck" size="16"/>
                                <svg-icon v-else type="mdi" :path="mdiContentCopy" size="16"/>
                            </button>
                        </div>
                        <div v-if="$page.props.admin_id && $page.props.auth.user.settings?.plainTextToken" class="border px-2 rounded py-2 mt-1 flex items-center justify-between">
                            {{ $page.props.auth.user.settings.plainTextToken }}
                            <button
                                @click="handleCopyShopToken"
                                class="w-5 h-5 flex items-center justify-center relative"
                            >
                                <svg-icon v-if="isShopIdCopied" type="mdi" :path="mdiCheck" size="16"/>
                                <svg-icon v-else type="mdi" :path="mdiContentCopy" size="16"/>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="pl-1">API Tokens</label>
                        <div>
                            <div v-for="token of merchant.tokens" class="w-full border border-gray-300 rounded-xl px-4 py-2 mt-1">
                                <div class="flex justify-between items-center">
                                    <div class="flex flex-col">
                                        <span class="text-base">{{ token.name }}</span>
                                        <span class="text-xs text-gray-500">{{ new Date(token.created_at).toLocaleDateString() }}</span>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-danger" @click="handleDeleteApiToken(token.id)">Delete</button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex mt-3">
                                <button class="btn btn-sm btn-primary" @click="openCreateTokenModal = true">Create New Token</button>
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="text-base font-bold mt-6">Webhooks</h5>
                <div class="space-y-4 mt-4">
                    <div>
                        <label class="text-xs whitespace-nowrap">Gang Sheet Generation Completed</label>
                        <input class="rounded border-gray-300 focus:border-primary focus:ring-primary shadow-sm px-4 mt-1 w-full" v-model="webhookGangSheetCompleted"/>
                    </div>
                </div>
                <div class="flex mt-3">
                    <button class="btn btn-sm btn-primary" :disabled="isSavingWebhooks" @click="handleSaveWebhooks">
                        Save Webhooks
                    </button>
                </div>
            </div>

            <div class="rounded shadow p-5 space-y-5 border border-gray-200">
                <h5 class="text-base font-bold">APIs</h5>
                <div>
                    <label class="font-semibold">Generate Gang Sheet</label>
                    <div v-if="apiContents.generateDesign.content" v-html="apiContents.generateDesign.content"></div>
                </div>
                <div>
                    <label class="font-semibold">Get Gang Sheet</label>
                    <p class="text-yellow-500">** Calling this API will reduce your credits **</p>
                    <div v-if="apiContents.getDesign.content" v-html="apiContents.getDesign.content"></div>
                </div>
            </div>
        </div>
    </MerchantLayout>
</template>

<style lang="scss">
@import "highlight.js/styles/stackoverflow-dark.min.css";
</style>
