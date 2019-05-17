<script>
import {defineComponent} from 'vue'
import Spinner from "@/Components/Spinner.vue";
import builderMixin from "@/Builder/Mixins/builderMixin";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import eventBus from "@/Builder/Utils/eventBus";
import moment from "moment-timezone";
import {MODAL_NAMES} from "@/Builder/Utils/constants.js";
import SvgIcon from "@jamescoyle/vue-icon";
import {
    mdiSquareEditOutline,
    mdiMenuUp,
    mdiMenuDown,
    mdiContentSave,
    mdiFileOutline,
    mdiContentSaveOutline,
    mdiContentSaveEditOutline,
    mdiFormatListBulletedSquare,
    mdiAccountCircleOutline
} from '@mdi/js'

export default defineComponent({
    name: "BuilderMenu",
    components: {SvgIcon, Menu, MenuItem, MenuItems, MenuButton, Spinner},
    mixins: [builderMixin],
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
            mdiAccountCircleOutline
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
        handleSignIn() {
            eventBus.$emit(eventBus.CUSTOMER_LOGIN)
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
    <Menu v-if="permissions.canReorder && currentDesign" v-slot="{open}" as="div" class="relative inline-block text-left w-full xl:w-56">
        <div class="flex items-center space-x-1">

            <MenuButton class="inline-flex w-full justify-center gap-x-1.5 rounded bg-white px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <div class="w-full flex justify-between items-center">
                    <span class="text-sm py-1 whitespace-nowrap overflow-hidden text-ellipsis">{{ currentDesign.name }}</span>
                    <div class="flex items-center justify-center w-4 h-4">
                        <svg-icon v-if="open" type="mdi" :path="mdiMenuUp" size="18"/>
                        <svg-icon v-else type="mdi" :path="mdiMenuDown" size="18"/>
                    </div>
                </div>
            </MenuButton>
            <div
                class="w-7 h-7 shrink-0 flex items-center justify-center rounded hover:bg-gray-100 cursor-pointer border"
                @click="handleSave"
            >
                <svg-icon type="mdi" :path="mdiContentSave" size="18"/>
            </div>
        </div>

        <transition v-if="!loadingDesign" enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95">
            <MenuItems class="absolute left-0 z-50 mt-1 w-full xl:w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div>
                    <MenuItem class="hover:bg-gray-100" @click="handleOpenNewDesign">
                        <div class="flex items-center text-left text-sm cursor-pointer px-4 py-2">
                            <svg-icon type="mdi" :path="mdiFileOutline" size="16" class="mr-2"/>
                            <span>Open New Design</span>
                        </div>
                    </MenuItem>
                    <hr/>
                    <MenuItem v-slot="{disabled}" class="hover:bg-gray-100" :disabled="!hasDesignChange">
                        <div class="flex items-center text-left text-sm cursor-pointer px-4 py-2" @click="handleSave" :class="{'text-gray-300':disabled}">
                            <svg-icon type="mdi" :path="mdiContentSaveOutline" size="16" class="mr-2"/>
                            <span>Save</span>
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{disabled}" class="hover:bg-gray-100" :disabled="!hasDesignChange">
                        <div class="flex items-center text-left text-sm cursor-pointer px-4 py-2" @click="handleSaveAs" :class="{'text-gray-300':disabled}">
                            <svg-icon type="mdi" :path="mdiContentSaveEditOutline" size="16" class="mr-2"/>
                            <span>Save As</span>
                        </div>
                    </MenuItem>
                    <hr/>
                    <MenuItem class="hover:bg-gray-100">
                        <div class="flex items-center text-left text-sm cursor-pointer px-4 py-2" @click="handleViewAllDesigns">
                            <svg-icon type="mdi" :path="mdiFormatListBulletedSquare" size="16" class="mr-2"/>
                            My Designs
                        </div>
                    </MenuItem>
                    <MenuItem class="hover:bg-gray-100">
                        <template v-if="customer">
                            <div v-if="customer.email" @click="handleViewProfile" class="flex items-center text-left text-sm flex cursor-pointer px-4 py-2">
                                <svg-icon type="mdi" :path="mdiAccountCircleOutline" size="16" class="mr-2"/>
                                Profile
                            </div>
                        </template>
                        <div v-else @click="handleSignIn" class="flex items-center text-left text-sm cursor-pointer px-4 py-2">
                            <svg-icon type="mdi" :path="mdiAccountCircleOutline" size="16" class="mr-2"/>
                            Sign In
                        </div>
                    </MenuItem>
                    <hr/>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

<style scoped>

</style>
