<script>
import {defineComponent} from 'vue'
import Spinner from '@/Components/Spinner.vue'
import stickerMixin from '@/Builder/Mixins/stickerMixin'
import {Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import eventBus from '@/Builder/Utils/eventBus'
import moment from 'moment-timezone'
import {MODAL_NAMES} from '@/Builder/Utils/constants'
import SvgIcon from '@jamescoyle/vue-icon'
import {
    mdiSquareEditOutline,
    mdiMenuUp,
    mdiMenuDown,
    mdiContentSave,
    mdiFileOutline,
    mdiContentSaveOutline,
    mdiContentSaveEditOutline,
    mdiFormatListBulletedSquare,
    mdiAccountCircleOutline,
    mdiSwapHorizontal,
    mdiChevronDown,
    mdiLogout
} from '@mdi/js'
import Avatar from '@/Builder/Components/Avatar.vue'

export default defineComponent({
    name: 'ProfileMenu',
    components: {Avatar, SvgIcon, Menu, MenuItem, MenuItems, MenuButton, Spinner},
    mixins: [stickerMixin],
    data() {
        return {
            mdiSquareEditOutline,
            mdiMenuUp,
            mdiMenuDown,
            mdiContentSave,
            mdiFileOutline,
            mdiContentSaveOutline,
            mdiContentSaveEditOutline,
            mdiFormatListBulletedSquare,
            mdiAccountCircleOutline,
            mdiSwapHorizontal,
            mdiChevronDown,
            mdiLogout
        }
    },
    methods: {
        moment,
        handleOpenDesign(designId) {
            eventBus.$emit(eventBus.CLOSE_MODAL)
            eventBus.$emit(eventBus.OPEN_DESIGN, designId)
        },
        handleViewProfile() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.CUSTOMER_PROFILE
            })
        },
        handleSwitchAccount() {
            eventBus.$emit(eventBus.CUSTOMER_SWITCH_ACCOUNT)
        },
        handleSignIn() {
            eventBus.$emit(eventBus.CUSTOMER_LOGIN)
        },
        handleLogOut() {
            eventBus.$emit(eventBus.CUSTOMER_LOGOUT)
        },
        handleSave() {
            eventBus.$emit(eventBus.SAVE_DRAFT_DESIGN)
        },
        handleSaveAs() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.SAVE_DESIGN,
                onChange: () => {
                    eventBus.$emit(eventBus.SAVE_DRAFT_DESIGN)
                }
            })
        },
        handleOpenNewDesign() {
            eventBus.$emit(eventBus.OPEN_NEW_DESIGN)
        },
        handleViewAllDesigns() {
            eventBus.$emit(eventBus.OPEN_MODAL, {
                name: MODAL_NAMES.CUSTOMER_DESIGNS
            })
        }
    }
})
</script>

<template>
    <div class="flex h-full items-center">
        <Menu v-slot="{open}" as="div" class="relative inline-block text-left w-full">
            <div class="flex items-center space-x-1">
                <MenuButton>
                    <div class="w-full flex justify-between items-center border rounded py-1 px-2">
                        <avatar :name="customer?.name"/>
                        <div v-if="customer" class="flex items-start flex-col text-xs ml-2">
                            <div class="leading-0">{{ customer.name }}</div>
                            <div class="leading-0 text-2xs">{{ customer.email }}</div>
                        </div>
                        <div v-else class="flex items-start flex-col text-xs ml-2">
                            <div class="leading-0 w-20 text-left">Unknown</div>
                            <div @click.stop="handleSignIn" class="leading-0 text-2xs gs-text-primary underline">
                                {{ $t('Sign In') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center ml-2">
                            <span :class="{'rotate-180': open}" class="transition-all">
                                <svg-icon type="mdi" :path="mdiChevronDown" size="20"/>
                            </span>
                        </div>
                    </div>
                </MenuButton>
            </div>

            <transition v-if="!loadingDesign" enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="absolute right-0 z-50 mt-1 w-full xl:w-48 origin-top-right bg-builder rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="p-2">
                        <template v-if="isShopify">
                            <MenuItem class="hover:bg-opacity-20 px-4 py-2">
                                <div v-if="customer">
                                    <div v-if="customer.email" @click="handleSwitchAccount" class="text-left text-sm flex hover:underline cursor-pointer items-center">
                                        <svg-icon type="mdi" :path="mdiSwapHorizontal" size="24" class="mr-2"/>
                                        Switch Account
                                    </div>
                                </div>
                                <div v-else @click="handleSignIn" class="text-left flex items-center text-sm cursor-pointer">
                                    <svg-icon type="mdi" :path="mdiAccountCircleOutline" size="24" class="mr-2"/>
                                    {{ $t('Sign In') }}
                                </div>
                            </MenuItem>
                        </template>
                        <!--                        <MenuItem class="hover:bg-opacity-20  px-4 py-2">-->
                        <!--                            <div class="text-left flex items-center text-sm cursor-pointer hover:underline" @click="handleViewAllDesigns">-->
                        <!--                                <svg-icon type="mdi" :path="mdiFormatListBulletedSquare" size="16" class="mr-2"/>-->
                        <!--                                {{ $t('My Stickers') }}-->
                        <!--                            </div>-->
                        <!--                        </MenuItem>-->
                    </div>
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>

<style scoped>

</style>
