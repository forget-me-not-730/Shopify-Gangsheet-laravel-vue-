<script>
import {defineComponent} from 'vue'
import eventBus from "@/Builder/Utils/eventBus";
import {MODAL_NAMES} from "@/Builder/Utils/constants";
import {getCanvaAuthToken, disconnectCanvaAccount} from "@/Builder/Apis/builderApi";
import CanvaIcon from "@/Builder/Icons/CanvaIcon.vue";
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import Spinner from "@/Builder/Components/Spinner.vue";
import EditIcon from "@/Builder/Icons/EditIcon.vue";
import GsbImage from "@/Builder/Components/GsbImage.vue";
import EyeIcon from "@/Builder/Icons/EyeIcon.vue";
import Dropdown from "@/Builder/Components/Dropdown.vue";
import DotsHorizontalIcon from "@/Builder/Icons/DotsHorizontalIcon.vue";
import ReloadIcon from "@/Builder/Icons/ReloadIcon.vue";
import SearchInput from "@/Builder/Components/SearchInput.vue";
import ImageCounter from "@/Builder/Components/ImageCounter.vue";

export default defineComponent({
    name: "GangSheetCanvaImages",
    components: {ImageCounter, SearchInput, ReloadIcon, DotsHorizontalIcon, Dropdown, EyeIcon, GsbImage, EditIcon, Spinner, CanvaIcon},
    mixins: [gangSheetMixin],
    data() {
        return {
            loading: false,
            isCanvaConneced: false,
            isConnecting: false,
            error: null,
            designs: null,
            continuation: '',
            search: '',
            timer: 0,
            isDisconnecting: false,
            selectedDesign: null,
        }
    },
    watch: {
        canva: {
            deep: true,
            immediate: true,
            handler() {
                if (this.canva.access_token) {
                    this.isCanvaConneced = true
                    this.loadCanvaDesigns()
                }
            },
        },
        search() {
            clearTimeout(this.timer)
            this.timer = setTimeout(() => {
                this.reloadCanvaDesigns()
            }, 1000)
        }
    },
    methods: {
        handleViewCanvaDesigns() {
            const popupWinWidth = 600
            const popupWinHeight = 600
            const left = (screen.width - popupWinWidth) / 2
            const top = (screen.height - popupWinHeight) / 4
            window._canvaWindow = window.open(this.canva.authorize_url,
                'Connect Your Canva Account',
                `popup=yes,height=${popupWinHeight},width=${popupWinWidth},top=${top},left=${left}`
            )
            this.isConnecting = true
            if (window.focus) {
                window._canvaWindow.focus()
            }
            const timer = setInterval(async () => {
                if (window._canvaWindow.closed) {
                    getCanvaAuthToken({
                        user_id: this.shop.id,
                        customer_id: this.customer?.id ?? ''
                    }).then(res => {
                        if (res && res.access_token) {
                            this.canva.access_token = res.access_token
                            this.canva.name = res.name
                            eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.CANVA_DESIGNS})
                        }
                    })
                    clearInterval(timer)
                    window._canvaWindow = null
                    this.isConnecting = false
                }
            }, 1000)
        },
        loadCanvaDesigns() {
            this.loading = true
            const query = this.search ? `title=${this.search}` : ''
            axios.get(route('builder.customer-canva-designs', {
                access_token: this.canva.access_token,
                continuation: this.continuation,
                query: query,
            })).then(res => {
                if (res.data.success) {
                    this.designs = res.data.designs.items
                    this.continuation = res.data.designs.continuation
                } else {
                    this.error = res.data.message
                }
            }).finally(() => {
                this.loading = false
            })
        },
        reloadCanvaDesigns() {
            this.continuation = ''
            this.designs = null
            this.loadCanvaDesigns()
        },
        handleDesignItemClick(design) {
            if (design.thumbnail) {
                this.selectedDesign = design
                eventBus.$emit(eventBus.OPEN_MODAL, {
                    name: MODAL_NAMES.CANVA_EXPORT_DESIGN,
                    data: {
                        design: design
                    }
                })
            }
        },
        handleCancel() {
            if (window._canvaWindow) {
                window._canvaWindow.close()
            }
        },
        handleContinue() {
            if (window._canvaWindow) {
                window._canvaWindow.focus()
            }
        },
        handleOpenDesignEditLink(design) {
            window.open(design.urls.edit_url, '_blank')
        },
        handleOpenDesignViewLink(design) {
            window.open(design.urls.view_url, '_blank')
        },
        async disconnectCanva() {
            this.isDisconnecting = true;
            const params = {
                access_token: this.canva.access_token,
                user_id: this.shop.id,
                customer_id: this.customer?.id ?? ''
            }
            const result = await disconnectCanvaAccount(params);
            this.isDisconnecting = false;
            if (result.success) {
                this.canva.access_token = null;
                this.isCanvaConneced = false;
                this.designs = null;
                this.error = null;
                eventBus.$emit(eventBus.ALERT_LOADING, null);
            } else {
                this.$gsb.error('Failed to disconnect Canva account')
            }
        }
    }
})
</script>

<template>
    <div class="h-full flex-center">
        <div v-if="isConnecting" class="fixed bg-black bg-opacity-75 h-screen w-screen top-0 left-0 z-[90] flex-center-col space-y-4">
            <p>
                Connecting your Canva account ...
            </p>
            <div class="flex space-x-4">
                <button class="underline" @click="handleCancel">{{ $t('Cancel') }}</button>
                <button class="btn-builder" @click="handleContinue">{{ $t('Continue') }}</button>
            </div>
        </div>

        <spinner v-if="canva.loading"/>

        <template v-else>
            <div v-if="!isCanvaConneced" class="space-y-6 w-full flex-center-col">
                <p class="text-lg text-center">
                    Canva wants to <br/> connect your account
                </p>
                <canva-icon size="72"/>
                <button class="btn-builder w-56" @click="handleViewCanvaDesigns" :disabled="isConnecting">
                    <spinner v-if="isConnecting" class="mr-2"/>
                    Connect
                </button>
            </div>

            <div v-else class="w-full h-full flex flex-col">
                <div class="my-2 w-full flex items-center px-2">
                    <search-input v-model="search" class="w-full"/>
                    <button
                        class="cursor-pointer h-full aspect-square rounded-lg ml-1 flex items-center justify-center hover:bg-gray-300 hover:bg-opacity-50 border border-gray-300 disabled:cursor-not-allowed"
                        :disabled="loading"
                        @click="reloadCanvaDesigns">
                        <reload-icon size="20"/>
                    </button>
                </div>
                <div v-if="isCanvaConneced" class="flex mb-1 justify-end px-2">
                    <span class="text-xs">
                        Connected as <span class="gs-text-primary font-bold">{{ canva.name }}</span>, <span class="underline cursor-pointer text-blue-500 hover:text-blue-700" @click="disconnectCanva">Disconnect</span>
                    </span>
                </div>
                <div v-if="designs" class="flex-1 h-px overflow-y-auto tiny-scroll-bar p-1">
                    <div class="w-full max-h-full grid grid-cols-2 gap-2">
                        <template v-for="design in designs">
                            <div v-if="design.page_count" :key="design.id" class="rounded cursor-pointer relative h-max">
                                <div class="aspect-square w-full bg-gray-300 bg-opacity-50 border border-gray-300 relative overflow-hidden rounded hover:gs-border-primary"
                                     @click="handleDesignItemClick(design)">
                                    <gsb-image v-if="design.thumbnail?.url" :src="design.thumbnail.url"/>
                                    <div v-else class="flex-center-col w-full h-full">
                                        <canva-icon :size="72" class="opacity-10"/>
                                        <span class="text-gray-400">No Content Saved</span>
                                    </div>
                                    <div class="absolute flex-center justify-between w-full top-0 z-50">
                                        <span class="flex-center text-xs gs-bg-primary px-1">{{ design.page_count }} pages</span>

                                        <div class="z-50 gs-bg-primary">
                                            <Dropdown width="20">
                                                <template #trigger>
                                                    <dots-horizontal-icon size="18"/>
                                                </template>
                                                <template #content>
                                                    <div @click="handleOpenDesignEditLink(design)" class="w-full flex items-center text-xs px-3 py-2 hover:gs-text-primary">
                                                        <edit-icon :size="16" class="mr-2"/>
                                                        Edit
                                                    </div>
                                                    <hr/>
                                                    <div @click="handleOpenDesignViewLink(design)" class="w-full items-center flex text-xs px-3 py-2 hover:gs-text-primary">
                                                        <eye-icon :size="16" class="mr-2"/>
                                                        View
                                                    </div>
                                                </template>
                                            </Dropdown>
                                        </div>
                                    </div>
                                    <div class="btn-builder px-0 rounded-sm text-white h-4 min-w-[16px] absolute bottom-0 right-0 justify-center items-center flex text-xs">
                                        <image-counter :canva_id="design.id"/>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div v-if="loading" class="absolute" :class="[designs ? 'bottom-2 right-2' : 'top-1/2 w-full']">
                    <div class="flex-center text-xs">
                        <spinner class="mr-2"/>
                        Loading designs ...
                    </div>
                </div>
                <div v-if="isDisconnecting" class="fixed bg-black bg-opacity-75 h-screen w-screen top-0 left-0 z-[90] flex-center-col space-y-4">
                    <p>
                        Disconnecting your Canva account ...
                    </p>
                    <spinner/>
                </div>
            </div>
        </template>
    </div>
</template>
