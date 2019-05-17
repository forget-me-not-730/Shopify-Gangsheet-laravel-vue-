<script>
import eventBus from "@/Builder/Utils/eventBus";
import {MODAL_NAMES, TOOLS} from "@/Builder/Utils/constants";
import builderMixin from "@/Builder/Mixins/builderMixin";
import GangSheetSideBar from "./GangSheetSideBar.vue";
import CanvaIcon from "@/Builder/Icons/CanvaIcon.vue";
import GoogleDriveIcon from '@/Builder/Icons/GoogleDriveIcon.vue';
import NameAndNumberIcon from "@/Builder/Icons/NameAndNumberIcon.vue";
import ImageIcon from "@/Builder/Icons/ImageIcon.vue";
import GalleryIcon from "@/Builder/Icons/GalleryIcon.vue";
import NameAndNumbers from "@/Builder/Components/NameAndNumbers.vue";
import HomeOutlineIcon from "@/Builder/Icons/HomeOutlineIcon.vue";
import CogOutlineIcon from "@/Builder/Icons/CogOutlineIcon.vue";
import GangSheetUploadImages from "@/Builder/GangSheet/GangSheetUploadImages.vue";
import GangSheetGalleryImages from "@/Builder/GangSheet/GangSheetGalleryImages.vue";
import GangSheetArtBoardSettings from "@/Builder/GangSheet/GangSheetArtBoardSettings.vue";
import CloudUploadOutlineIcon from "@/Builder/Icons/CloudeUploadOutlineIcon.vue";
import CursorDefaultOutlineIcon from "@/Builder/Icons/CursorDefaultOutlineIcon.vue";
import GangSheetCanvaImages from "@/Builder/GangSheet/GangSheetCanvaImages.vue";
import GangSheetGoogleDriveImages from "@/Builder/GangSheet/GangSheetGoogleDriveImages.vue";
import gangSheetSettingMixin from "@/Builder/Mixins/gangSheetSettingMixin";
import {LANGUAGES} from "@/Builder/Utils/constants";

export default {
    name: "ToolBar",
    components: {
        GangSheetCanvaImages,
        GangSheetGoogleDriveImages,
        CursorDefaultOutlineIcon,
        CloudUploadOutlineIcon,
        GangSheetArtBoardSettings,
        GangSheetGalleryImages,
        GangSheetUploadImages,
        CogOutlineIcon,
        HomeOutlineIcon,
        NameAndNumbers,
        GalleryIcon,
        ImageIcon,
        NameAndNumberIcon,
        CanvaIcon,
        GoogleDriveIcon,
        GangSheetSideBar
    },
    mixins: [builderMixin, gangSheetSettingMixin],
    data() {
        return {
            tools: TOOLS,
            languages: LANGUAGES,
            selectedLanguage: LANGUAGES.find(lang => lang.value === 'en'),
            showLanguageDropdown: false
        }
    },
    mounted() {
        if (this.builderSettings.nameAndNumber?.enabled && this.builderSettings.nameAndNumber?.default) {
            this.tool = this.tools.nameAndNumbers
        }
        const artBoardSettings = this.$gsb.getArtBoardSettings()
        this.selectedLanguage = this.languages.find(lang => lang.value === artBoardSettings.lang) || this.selectedLanguage;
        this.setLocale(this.selectedLanguage.value);
        document.addEventListener("click", this.handleClickOutside);
    },
    unmounted() {
        document.removeEventListener("click", this.handleClickOutside);
    },
    methods: {
        initializeSettings(canvas) {
            canvas.on('mouse:down', (e) => {
                if (e.target) {
                    this.tool = this.tools.main
                }
            })
        },
        handleToolClick(tool) {
            this.tool = tool
            switch (tool) {
                case this.tools.image:
                    eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.IMAGE_UPLOAD})
                    break
                case this.tools.main:
                    eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.SELECTOR})
                    break
                case this.tools.nameAndNumbers:
                    eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.NAME_AND_NUMBERS})
                    break
                case this.tools.settings:
                    eventBus.$emit(eventBus.OPEN_MODAL, {name: MODAL_NAMES.GANG_SHEET_SETTINGS})
                    break
            }
        },
        toggleLanguageDropdown() {
            this.showLanguageDropdown = !this.showLanguageDropdown;
        },
        selectLanguage(lang) {
            this.selectedLanguage = lang;
            this.setLocale(lang.value);
            this.showLanguageDropdown = false;
            this.$gsb.setArtBoardSettings('lang', lang.value);
        },
        handleClickOutside(event) {
            const dropdown = this.$refs.languageDropdown;
            const languageButton = this.$refs.languageButton;
            if (
                dropdown && !dropdown.contains(event.target) &&
                languageButton && !languageButton.contains(event.target)
            ) {
                this.showLanguageDropdown = false;
            }
        },
    }
}
</script>

<template>
    <div class="max-md:w-full md:max-w-max gs-bg-side-bar">
        <div class="md:hidden max-md:w-full flex items-center justify-around shadow z-10 border-t">
            <div class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r"
                 :class="{'gs-bg-primary max-md:border-l-gray-300 max-md:border-r-gray-300 md:border-t-gray-300 md:border-b-gray-200': tool === tools.main}"
                 @click="handleToolClick(tools.main)">
                <cursor-default-outline-icon size="16"/>
                <small class="text-xs">{{ $t('Select') }}</small>
            </div>

            <div class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r"
                 :class="{'gs-bg-primary max-md:border-l-gray-300 max-md:border-r-gray-300 md:border-t-gray-300 md:border-b-gray-200': tool === tools.image}"
                 @click="handleToolClick(tools.image)">
                <cloud-upload-outline-icon size="16"/>
                <small class="text-xs">{{ $t('Add Image') }}</small>
            </div>

            <div v-if="builderSettings.nameAndNumber?.enabled"
                 class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r truncate"
                 :class="{'gs-bg-primary max-md:border-l-gray-300 max-md:border-r-gray-300 md:border-t-gray-300 md:border-b-gray-200': tool === tools.nameAndNumbers}"
                 @click="handleToolClick(tools.nameAndNumbers)">
                <name-and-number-icon size="18"/>
                <small class="text-xs">{{ $t('Name & Numbers') }}</small>
            </div>

            <div class="flex flex-col items-center cursor-pointer py-2 w-full max-md:h-full border-r-gray-300 border-r"
                 :class="{'gs-bg-primary max-md:border-l-gray-300 max-md:border-r-gray-300 md:border-t-gray-300 md:border-b-gray-200': tool === tools.settings}"
                 @click="handleToolClick(tools.settings)">
                <cog-outline-icon size="16"/>
                <small class="text-xs">{{ $t('Settings') }}</small>
            </div>

        </div>

        <div class="flex h-full max-md:hidden">
            <div class="w-[60px] h-full border-r gs-text-primary relative">
                <div class="flex-center-col py-1.5 cursor-pointer m-0.5 rounded border border-transparent hover:gs-border-primary"
                     :class="[tool === tools.main ? 'gs-bg-primary text-white' : '']"
                     @click="tool = tools.main">
                    <home-outline-icon/>
                    <span class="text-xs">Home</span>
                </div>

                <hr/>

                <template v-if="builderSettings.nameAndNumber?.enabled">
                    <div class="flex-center-col py-3 cursor-pointer m-0.5 rounded border border-transparent hover:gs-border-primary"
                         :class="[tool === tools.nameAndNumbers ? 'gs-bg-primary text-white' : '']"
                         @click="tool = tools.nameAndNumbers">
                        <name-and-number-icon :size="36"/>
                    </div>

                    <hr/>
                </template>

                <div v-if="!builderSettings.disableUploadingImage" class="flex-center-col py-1.5 cursor-pointer m-0.5 rounded border border-transparent hover:gs-border-primary"
                     :class="[tool === tools.uploads ? 'gs-bg-primary text-white' : '']"
                     @click="tool = tools.uploads">
                    <image-icon/>
                    <span class="text-xs">Uploads</span>
                </div>

                <hr/>

                <template v-if="builderSettings.showGallery">
                    <div class="flex-center-col py-1.5 cursor-pointer m-0.5 rounded border border-transparent hover:gs-border-primary"
                         :class="[tool === tools.gallery ? 'gs-bg-primary text-white' : '']"
                         @click="tool = tools.gallery">
                        <gallery-icon/>
                        <span class="text-xs">Gallery</span>
                    </div>

                    <hr/>
                </template>

                <template v-if="builderSettings.enableCanva">
                    <div class="flex-center-col py-1.5 cursor-pointer m-0.5 rounded border border-transparent hover:gs-border-primary"
                         :class="[tool === tools.canva ? 'gs-bg-primary text-white' : '']"
                         @click="tool = tools.canva">
                        <canva-icon size="24"/>
                        <span class="text-xs">Canva</span>
                    </div>

                    <hr/>
                </template>

                <template v-if="builderSettings.enableGoogleDrive && isDev">
                    <div class="flex-center-col py-1.5 cursor-pointer m-0.5 rounded border border-transparent hover:gs-border-primary"
                         :class="[tool === tools.google ? 'gs-bg-primary text-white' : '']"
                         @click="tool = tools.google">
                        <google-drive-icon size="20"/>
                        <span class="text-xs">g-Drive</span>
                    </div>
                    <hr/>
                </template>

                <div class="flex-center-col py-1.5 cursor-pointer m-0.5 rounded border border-transparent hover:gs-border-primary"
                     :class="[tool === tools.settings ? 'gs-bg-primary text-white' : '']"
                     @click="tool = tools.settings">
                    <cog-outline-icon/>
                    <span class="text-xs">Settings</span>
                </div>

                <hr/>

                <div class="absolute bottom-4 w-full px-1">
                    <div
                        ref="languageButton"
                        class="flex items-center justify-between w-full py-0.5 px-1 border border-gray-300 cursor-pointer text-sm"
                        @click="toggleLanguageDropdown">
                        <div class="text-2xs uppercase mr-px">{{ selectedLanguage.value }}</div>
                        <img :src="`/assets/svgs/flags/${selectedLanguage.value}.svg`" alt="" class="w-5 h-5">
                    </div>

                    <div
                        ref="languageDropdown"
                        v-if="showLanguageDropdown"
                        :class="{
                            'absolute bg-builder bottom-full z-10 mb-1 border border-gray-300 w-max text-sm transition-all': true,
                            'left-0': !showLanguageDropdown,
                        }">
                        <div
                            v-for="lang in languages"
                            :key="lang.value"
                            class="flex items-center px-2 py-1 cursor-pointer text-xs hover:bg-gray-300 hover:bg-opacity-50 transition-all"
                            @click="selectLanguage(lang)"
                            :class="{'bg-blue-300 bg-opacity-50': lang.value === selectedLanguage.value}">
                            <img :src="`/assets/svgs/flags/${lang.value}.svg`" alt="" class="w-5 h-5 mr-3">
                            <span>{{ lang.label }}</span>
                        </div>
                    </div>
                </div>

            </div>
            <div class="flex flex-col relative w-[320px] h-full max-xl:pt-[50px] border-r overflow-y-auto">
                <div v-if="tool === tools.main" class="h-full">
                    <gang-sheet-side-bar/>
                </div>

                <name-and-numbers v-if="tool === tools.nameAndNumbers"/>

                <div :class="[tool === tools.uploads ? 'h-full' : 'hidden']">
                    <gang-sheet-upload-images/>
                </div>

                <div :class="[tool === tools.gallery ? 'h-full' : 'hidden']">
                    <gang-sheet-gallery-images/>
                </div>

                <gang-sheet-canva-images v-if="tool === tools.canva"/>

                <gang-sheet-google-drive-images v-if="tool === tools.google"/>

                <gang-sheet-art-board-settings v-if="tool === tools.settings"/>


                <div class="absolute bottom-1 left-2 text-xs max-md:hidden">
                    Powered by <a href="https://www.thedripapps.com" target="_blank" class="gs-text-primary">Drip Apps</a>
                </div>
            </div>
        </div>
    </div>
</template>
