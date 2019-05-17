<template>
    <form @submit.prevent="handleSubmit" class="max-w-7xl mx-auto my-5">
        <div class="rounded-lg bg-white shadow p-2  md:p-5">
            <div class="max-md:flex-col flex">
                <div class="flex max-md:justify-between md:flex-col max-md:w-full md:w-48">
                    <gbs-select class="w-48 md:hidden" :options="tabs" v-model="activeTab">
                        <template #selected="{selected}">
                            <span class="font-medium text-gray-900">{{ selected.title }}</span>
                        </template>
                        <template #option="{option}">
                            <span class="font-medium text-gray-900">{{ option.title }}</span>
                        </template>
                    </gbs-select>

                    <div class="flex flex-col max-md:hidden md:space-y-4 text-sm font-medium text-gray-500" id="tab-control" data-tabs-toggle="#tab-content"
                         role="tablist">
                        <div
                            v-for="(tab, index) in tabs"
                            :key="index"
                            :class="['p-2 max-md:w-28 shrink-0 cursor-pointer flex max-md:justify-center items-center rounded bg-gray-50 md:w-full',
                                     {'text-white bg-primary': activeTab.id === tab.id} ]"
                            @click="activeTab = tab"
                        >
                            <svg-icon type="mdi" :path="tab.icon" size="16" class="max-md:hidden w-8 justify-center"/>
                            {{ tab.title }}
                        </div>
                    </div>

                    <button type="submit"
                            @click="handleSubmit"
                            class="rounded max-md:h-[35px] mt-1 max-md:w-20 text-sm flex p-2 md:mt-10 items-center justify-center w-full bg-primary text-white disabled:cursor-not-allowed disabled:bg-gray-400"
                            :disabled="form.processing">
                        <spinner v-if="form.processing" class="mr-1"/>
                        Save All
                    </button>
                </div>
                <div class="flex-1 max-md:mt-5 md:pl-4 pb-[100px]">
                    <div class="tab-content">
                        <div v-if="activeTab.id === 'gang-sheet'" class="text-gray-500 space-y-3">
                            <div class="border-b p-2">
                                <label class="text-sm font-medium text-gray-900">Gang Sheet File Name Format</label>
                                <input
                                    type="text" v-model="form.gangSheetFileName" required name="gangSheetFileName"
                                    class="mt-1 py-2 w-full flex items-center border rounded border-gray-300 text-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20"
                                    :class="[{'border-red-500 focus:border-red-500 focus:ring-red-500': !isValidFileName}]"
                                />
                                <span v-if="form.errors.gangSheetFileName" class="text-danger">{{ form.errors.gangSheetFileName }}</span>
                                <div class="flex items-start justify-start mt-1 text-xs text-waning px-1">
                                    Available replacements are {customer_name}, {order_id}, {variant_title}, {line_item_number}, {shipping_method}, {design_name}, {quantity}
                                </div>
                            </div>
                            <div v-if="user.type === 'woo'" class="border-b p-2">
                                <label class="text-sm font-medium text-gray-900">Edit Request Email</label>
                                <input
                                    type="text" v-model="form.shop_email" required name="gangSheetFileName"
                                    class="mt-1 py-2 w-full flex items-center border rounded border-gray-300 text-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20"/>
                            </div>

                            <div class="flex justify-between items-center border-b p-2">
                                <span class="text-sm font-medium text-gray-900">Print File Name</span>
                                <toggle v-model="form.printFileName"/>
                            </div>

                            <div v-if="form.printFileName" class="flex justify-between items-center border-b p-2">
                                <label class="text-sm font-medium text-gray-900">File Name Position</label>
                                <selector v-model="form.printFileNamePosition" :options="['top', 'bottom', 'both']"/>
                            </div>

                            <div v-if="form.printFileName" class="flex justify-between items-center border-b p-2">
                                <label class="text-sm font-medium text-gray-900">File Name Height</label>
                                <selector v-model="form.printFileNameHeight" :options="[{label: 'Normal', value: 1}, {label: 'Small', value: 0.7}, {label: 'Tight', value: 0.4}]"/>
                            </div>

                            <div class="flex justify-between items-center border-b p-2">
                                <label class="text-sm font-medium text-gray-900">Gang Sheet File Type</label>
                                <selector v-model="form.gangSheetFileExtension" :options="[{label: 'PNG', value: '.png'}, {label: 'PDF', value: '.pdf'}, {label: 'TIFF', value: '.tiff'}]"/>
                            </div>

                            <div class="flex justify-between items-center border-b p-2">
                                <span class="text-sm font-medium text-gray-900">Trim empty space at the bottom</span>
                                <toggle v-model="form.autoTrimGangSheet"/>
                            </div>

                            <div class="border-b p-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900">Upload to Dropbox</span>
                                    <toggle v-model="form.useUploadDropbox"/>
                                </div>

                                <div class="mt-2" v-if="form.useUploadDropbox">
                                    <div v-if="connectedDropbox">
                                        <label class="text-sm font-medium text-gray-900">Connected Account</label>
                                        <div v-if="dropboxToken" class="flex items-center mt-2">
                                            <span class="bg-orange-600 w-8 h-8 text-xl flex items-center justify-center rounded-full text-white mr-2">
                                                {{ dropboxToken.name[0] }}
                                            </span>
                                            <div class="flex flex-col">
                                                <span class="text-gray-900">{{ dropboxToken.email }}</span>
                                                <span>{{ dropboxToken.name }}</span>
                                            </div>
                                            <button
                                                :disabled="disConnectingDropbox"
                                                class="flex items-center bg-primary rounded-full hover:bg-primary px-4 py-1.5 text-white disabled:cursor-not-allowed disabled:bg-gray-400 ml-5"
                                                @click.prevent="handleDisconnectDropbox"
                                            >
                                                <spinner v-if="disConnectingDropbox" class="mr-2"/>
                                                Disconnect
                                            </button>
                                        </div>
                                        <div v-else class="text-xs text-red-500">
                                            Something went to wrong.
                                        </div>

                                        <div class="mt-5">
                                            <label class="text-sm font-medium text-gray-900">Gang Sheet Folder Path</label>
                                            <input
                                                type="text"
                                                v-model="dropboxFolderName" required
                                                name="googleDriveFolderName"
                                                class="block mt-1 w-full rounded border-gray-300 text-xs py-1 focus:ring focus:ring-opacity-20"
                                                :class="[isValidDropboxFolderName ? 'focus:ring-blue-600 focus:border-blue-600': 'border-red-500 focus:border-red-500 focus:ring-red-500']"
                                            />
                                        </div>
                                    </div>
                                    <button
                                        v-else
                                        @click.prevent="handleConnectDropbox"
                                        :disabled="connectingDropbox"
                                        class="flex items-center bg-primary rounded-full hover:bg-primary px-4 py-1.5 text-white disabled:cursor-not-allowed disabled:bg-gray-400">
                                        <spinner v-if="connectingDropbox" class="mr-2"/>
                                        Connect Dropbox
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab.id === 'builder'" class="text-gray-500 space-y-3">
                            <div class="w-full space-y-4">
                                <label class="font-semibold text-base text-gray-900">Artwork Uploads</label>
                                <div class="flex justify-between items-center border-b p-2 flex-wrap">
                                    <label class="text-sm font-medium text-gray-900">Supported Upload File Types</label>
                                    <div class="flex text-xs flex-wrap max-w-full mt-2">
                                        <label v-for="file_type in file_types" class="space-x-2 flex items-center cursor-pointer w-20">
                                            <input type="checkbox" v-model="form.file_types" name="file_types" :value="file_type.value"
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-50 focus:ring-2">
                                            <span>{{ file_type.label }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm text-gray-900">Auto resize image to meet 300 dpi</span>
                                    <toggle v-model="form.ensureOptimalResolution"/>
                                </div>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm font-medium text-gray-900">Enable Canva</span>
                                    <toggle v-model="form.enableCanva"/>
                                </div>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm font-medium text-gray-900">Enable background warning</span>
                                    <toggle v-model="form.enableImageBackgroundWarning"/>
                                </div>
                            </div>

                            <div class="w-full space-y-4">
                                <div class="font-semibold text-base text-gray-900">Spacing</div>

                                <div class="border-b p-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">Enable Image Margin As Default</span>
                                        <toggle v-model="form.turnOnMargin"/>
                                    </div>

                                    <div v-if="form.turnOnMargin" class="flex justify-between items-center mt-2">
                                        <label class="flex items-center text-sm font-medium text-gray-900 mr-5">Default Image Margin Size</label>
                                        <div
                                            class="mt-1 w-full flex items-center border rounded border-gray-300 text-xs  max-w-max focus-within:border-blue-600 focus-within:ring focus-within:ring-blue-600 focus-within:ring-opacity-20">
                                            <input type="number" v-model="form.defaultMarginSize" :checked="form.defaultMarginSize" class="w-28 inp-no-style py-1" step="0.1" min="0"
                                                   name="defaultMarginSize">
                                            <select v-model="form.defaultMarginUnit" class="border-0 text-sm">
                                                <option class="px-1">in</option>
                                                <option class="px-1">mm</option>
                                                <option class="px-1">cm</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-b p-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">Enable Artboard Margin As Default</span>
                                        <toggle v-model="form.turnOnArtboardMargin"/>
                                    </div>

                                    <div v-if="form.turnOnArtboardMargin" class="flex justify-between items-center mt-2">
                                        <label class="flex items-center text-sm font-medium text-gray-900 mr-5">Default Artboard Margin Size</label>
                                        <div
                                            class="mt-1 w-full flex items-center border rounded border-gray-300 text-xs  max-w-max focus-within:border-blue-600 focus-within:ring focus-within:ring-blue-600 focus-within:ring-opacity-20">
                                            <input type="number" v-model="form.defaultArtboardMarginSize" :checked="form.defaultArtboardMarginSize" class="w-28 inp-no-style py-1" step="0.1" min="0"
                                                   name="defaultArtboardMarginSize">
                                            <select v-model="form.defaultArtboardMarginUnit" class="border-0 text-sm">
                                                <option class="px-1">in</option>
                                                <option class="px-1">mm</option>
                                                <option class="px-1">cm</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full space-y-4">
                                <label class="font-semibold text-base text-gray-900">Tools</label>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm font-medium text-gray-900">Disable Background Remove Tool</span>
                                    <toggle v-model="form.disableBackgroundRemoveTool"/>
                                </div>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm font-medium text-gray-900">Disable Text Feature</span>
                                    <toggle v-model="form.disableTextFeature"/>
                                </div>

                                <div class="border-b p-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">Use Custom Text Colors</span>
                                        <toggle v-model="form.useCustomTextColors"/>
                                    </div>

                                    <div class="mt-2" v-if="form.useCustomTextColors">
                                        <div class="text-xs flex items-center mt-1">
                                            <svg-icon type="mdi" class="mr-1" :path="mdiInformationOutline" :size="14"/>
                                            Drag the color items to reorder them.
                                        </div>

                                        <draggable :modelValue="form.customTextColors">
                                            <template #item="{ item, index }">
                                                <div class="p-0.5">
                                                    <div class="flex items-center justify-center aspect-square w-8 border rounded cursor-pointer relative my-px" :style="{backgroundColor: item}">
                                                        <div class="absolute w-3 h-3 top-[-4px] right-[-2px] border flex items-center justify-center rounded-full bg-red-500"
                                                             @click.prevent.stop="removeCustomTextColor(index)">
                                                            <svg-icon type="mdi" :path="mdiClose" :size="12" class="text-white"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #add-item>
                                                <div class="p-0.5">
                                                    <div class="flex items-center justify-center aspect-square w-8 border rounded cursor-pointer relative"
                                                         @click.prevent.stop="showCustomTextColorPicker"
                                                         @mouseleave="hideCustomTextColorPicker">
                                                        <svg-icon type="mdi" :path="mdiPlus" :size="16"/>
                                                        <div v-if="showTextColorPicker" class="absolute w-max z-50 top-[calc(100%+2px)]">
                                                            <color-picker @colorAdded="addCustomTextColor"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </draggable>
                                    </div>
                                </div>

                                <div class="border-b p-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">Use Custom Image Overlay Colors</span>
                                        <toggle v-model="form.useCustomImageOverlayColors"/>
                                    </div>

                                    <div class="mt-2" v-if="form.useCustomImageOverlayColors">
                                        <div class="text-xs flex items-center mt-1">
                                            <svg-icon type="mdi" class="mr-1" :path="mdiInformationOutline" :size="14"/>
                                            Drag the color items to reorder them.
                                        </div>

                                        <draggable :modelValue="form.customImageOverlayColors">
                                            <template #item="{ item, index }">
                                                <div class="p-0.5">
                                                    <div class="flex items-center justify-center aspect-square w-8 border rounded cursor-pointer relative my-px" :style="{backgroundColor: item}">
                                                        <div class="absolute w-3 h-3 top-[-4px] right-[-2px] border flex items-center justify-center rounded-full bg-red-500"
                                                             @click.prevent.stop="removeCustomImageOverlayColor(index)">
                                                            <svg-icon type="mdi" :path="mdiClose" :size="12" class="text-white"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #add-item>
                                                <div class="p-0.5">
                                                    <div class="flex items-center justify-center aspect-square w-8 border rounded cursor-pointer relative"
                                                         @click.prevent.stop="showCustomImageOverlayColorPicker"
                                                         @mouseleave="hideCustomImageOverlayColorPicker">
                                                        <svg-icon type="mdi" :path="mdiPlus" :size="16"/>
                                                        <div v-if="showImageOverlayColorPicker" class="absolute w-max z-50 top-[calc(100%+2px)]">
                                                            <color-picker @colorAdded="addCustomImageOverlayColor"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </draggable>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm font-medium text-gray-900">Auto Build</span>
                                    <toggle v-model="form.allowedAutoBuild"/>
                                </div>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm font-medium text-gray-900">Enable Flipping Over Image/Text</span>
                                    <toggle v-model="form.enableFlipping"/>
                                </div>
                            </div>

                            <div class="w-full space-y-4">
                                <label class="font-semibold text-base text-gray-900">Others</label>

                                <div class="flex justify-between items-center border-b p-2">
                                    <span class="text-sm font-medium text-gray-900">Collect Shipping Addresses on Checkout</span>
                                    <toggle v-model="form.collectShippingAddress"/>
                                </div>

                                <div v-if="user.type === 'custom'" class="space-y-3">
                                    <div class="flex justify-between items-center border-b p-2">
                                        <span class="text-sm font-medium text-gray-900">Enable Custom Add New Design</span>
                                        <toggle v-model="form.enableAddNewDesign"/>
                                    </div>

                                    <div class="flex justify-between items-center border-b p-2">
                                        <span class="text-sm font-medium text-gray-900">Enable Custom Quantity</span>
                                        <toggle v-model="form.enableQuantity"/>
                                    </div>

                                    <div class="border-b p-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-900">Design Confirmation Button Label</span>
                                            <input type="text" v-model="form.confirmationButtonLabel" class="block mt-1 w-64 rounded border-gray-300 text-xs py-1 focus:ring focus:ring-opacity-20"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-b p-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">Enable Agree Modal</span>
                                        <toggle v-model="form.agree_check_flag"/>
                                    </div>

                                    <div v-if="form.agree_check_flag" class="space-y-1 mt-2">
                                        <textarea type="text" v-model="form.agree_label" rows="3" required
                                                  class="w-full rounded-md border-gray-300 text-sm py-1 mt-2 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20"></textarea>
                                        <span v-if="form.errors.agree_label" class="text-danger">{{ form.errors.agree_label }}</span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center border-b p-2 relative">
                                    <label class="text-sm font-medium text-gray-900">Default Language</label>
                                    <gbs-select class="w-48 z-50" :options="languages" v-model="language">
                                        <template #selected="{selected}">
                                            <span class="font-medium text-gray-900">{{ selected.label }}</span>
                                        </template>
                                        <template #option="{option}">
                                            <span class="font-medium text-gray-900">{{ option.label }}</span>
                                        </template>
                                    </gbs-select>
                                </div>

                                <div class="p-2 border-b">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">Enable Live Chat</span>
                                        <toggle v-model="form.enableChat"/>
                                    </div>

                                    <div v-if="form.enableChat" class="mt-2">
                                        <span class="text-xs text-gray-500">
                                            This will allow your customers to chat with you while they are designing. You can add your chat script below.
                                        </span>
                                        <div class="flex w-full">
                                            <textarea type="text" v-model="form.chatScript" rows="4" required
                                                      class="w-full mt-1 rounded-md border-gray-300 text-xs py-1 focus:border-blue-600 focus:ring focus:ring-blue-600 focus:ring-opacity-20"></textarea>
                                            <span v-if="form.errors.chatScript"
                                                  class="text-danger">{{ form.errors.chatScript }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab.id === 'gallery'" class="text-gray-500 space-y-3">

                            <div class="flex justify-between items-center border-b p-2">
                                <span class="text-sm font-medium text-gray-900">Show Gallery</span>
                                <toggle v-model="form.show_gallery"/>
                            </div>

                            <div class="flex justify-between items-center border-b p-2">
                                <span class="text-sm font-medium text-gray-900">Enable Color Overlay</span>
                                <toggle v-model="form.enableColorOverlay"/>
                            </div>

                            <div v-if="form.enableColorOverlay" class="flex justify-between items-center p-2">
                                <span class="font-semibold text-xs text-gray-900"></span>
                                <div class="space-x-4">
                                    <label class="text-sm">
                                        <input v-model="form.colorOverlayAllowed" type="radio" value="all">
                                        All
                                    </label>
                                    <label class="text-sm">
                                        <input v-model="form.colorOverlayAllowed" type="radio" value="category">
                                        Selected Categories Only
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.show_gallery" class="flex justify-between items-center border-b p-2">
                                <span class="text-sm font-medium text-gray-900">Category View Mode</span>
                                <selector v-model="form.galleryMode" :options="['dropdown', 'folder']"/>
                            </div>

                            <div class="h-8 flex items-center justify-between w-full mt-5">
                                <label class="text-sm font-medium text-gray-900">Watermark Opacity</label>
                                <button
                                    v-if="isOpacityChanged"
                                    @click="handleWatermarkOpacityApply"
                                    :disabled="watermark_applying || watermark_processing"
                                    class="flex items-center bg-primary rounded-full hover:bg-primary px-4 py-1.5 text-white disabled:cursor-not-allowed disabled:bg-gray-400">
                                    <spinner v-if="watermark_applying" class="mr-2"/>
                                    Apply
                                </button>
                            </div>
                            <div v-if="watermark_processing" class="flex items-center my-2">
                                <div class="flex items-center  rounded w-full text-sm" role="alert">
                                    <div class="flex bg-gray-300 text-info rounded-full items-center px-3 py-1 mr-3">
                                    <span class="relative flex h-3 w-3 mr-1">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-sky-500"></span>
                                    </span>
                                        Running
                                    </div>
                                    <p v-if="total">{{ processed }} of {{ total }} are processed.</p>
                                </div>

                            </div>
                            <div class="flex items-center mt-2 mb-2">
                                <input v-model="form.watermark_opacity" class="w-16 h-8 mr-2 rounded border focus:ring-0 focus:outline-0 px-2 text-xs"/>
                                <div class="flex-1 px-1">
                                    <input v-model="form.watermark_opacity" type="range" :min="0" :max="1" :step="0.01"/>
                                </div>
                            </div>

                            <label class="pt-4">Preview</label>
                            <div class="w-full relative mt-1 h-32 flex items-center justify-center">
                                <img src="/assets/images/sample.jpg" alt="preview image" class="h-full w-full object-cover"/>
                                <div class="absolute font-oswald text-3xl" :style="{color: `rgba(255, 255, 255, ${form.watermark_opacity})`}">
                                    {{ user.company_name }}
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab.id === 'appearance'" class="text-gray-500">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-semibold text-gray-900">Show Welcome Popup</span>
                                <toggle v-model="form.showStartModal"/>
                            </div>

                            <template v-if="form.showStartModal">
                                <div class="flex space-x-4 my-5">
                                    <label class="flex items-center">
                                        <input type="radio" v-model="form.startModalView" value="list" name="startModalView"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-50 focus:ring-2">
                                        <span class="ml-2 text-sm font-medium text-gray-900">List View</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" v-model="form.startModalView" value="gallery" name="startModalView"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-50 focus:ring-2">
                                        <span class="ml-2 text-sm font-medium text-gray-900">Gallery View</span>
                                    </label>
                                </div>

                                <div v-if="form.startModalView === 'list'" class="mt-5 w-full max-w-xl">
                                    <draggable v-model="form.startModals" class="w-full flex-col">
                                        <template #item="{item, index}">
                                            <div
                                                class="border rounded flex items-center justify-between cursor-pointer px-2 py-1 my-1">
                                                <div class="flex items-center">
                                                    <div v-tooltip="getStartModelId(item.id)">
                                                        <svg-icon type="mdi" :path="mdiInformationOutline" :size="18"/>
                                                    </div>
                                                    <svg-icon type="mdi" :path="mdiDrag" :size="18"/>
                                                </div>
                                                <div class="w-full px-0.5 text-xs py-1 text-gray-900 border border-transparent focus:border-gray-300 focus:cursor-text" contenteditable="true"
                                                     v-html="item.label"
                                                     @mousemove.stop.prevent=""
                                                     @focusout="form.startModals[index].label = $event.target.textContent"></div>
                                                <toggle v-model="item.enabled" class="!text-2xs"/>
                                            </div>
                                        </template>
                                    </draggable>
                                </div>
                                <div v-else class="mt-5">
                                    <draggable v-model="form.startModals" class="grid md:grid-cols-5 gap-2">
                                        <template #item="{item, index}">
                                            <div class="flex flex-col cursor-pointer">
                                                <div
                                                    class="aspect-square border rounded bg-gray-200 relative">
                                                    <image-view v-model="item.image" class="overflow-hidden"/>
                                                    <div class="absolute top-0 left-0 h-6 p-1 flex items-center justify-center bg-white rounded">
                                                        <div v-tooltip="getStartModelId(item.id)">
                                                            <svg-icon type="mdi" :path="mdiInformationOutline" :size="18"/>
                                                        </div>
                                                        <svg-icon type="mdi" :path="mdiDrag" :size="18"/>
                                                    </div>
                                                    <div
                                                        class="absolute top-0 right-0 h-6 flex items-center justify-center bg-white rounded">
                                                        <toggle v-model="item.enabled" class="!text-2xs"/>
                                                    </div>
                                                </div>
                                                <div class="mt-1 flex items-center space-between">
                                                    <div class="w-full px-0.5 text-xs text-gray-900" contenteditable="true"
                                                         v-html="item.label"
                                                         @focusout="form.startModals[index].label = $event.target.textContent">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </draggable>
                                </div>
                            </template>

                            <div class="w-48 mt-4">
                                <label class="text-xs font-semibold text-gray-900">Builder Font Name</label>
                                <gbs-select class="mt-1" :options="fonts" v-model="form.builderFont">
                                    <template #selected="{selected}">
                                        <span :style="{fontFamily: selected}">{{ selected }}</span>
                                    </template>
                                    <template #option="{option}">
                                        <span :style="{fontFamily: option}">{{ option }}</span>
                                    </template>
                                </gbs-select>
                            </div>

                            <div class="mt-4">
                                <label class="text-xs font-semibold text-gray-900">Themes</label>
                                <div class="grid grid-cols-3 gap-2 mt-1 text-xs">
                                    <label class="flex items-center space-x-2">
                                        <input type="color" v-model="form.builderBgColor" required class="h-8 w-8"/>
                                        <span class="font-medium text-gray-900">Main Background Color</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="color" v-model="form.builderTopBgColor" required class="h-8 w-8"/>
                                        <span class="font-medium text-gray-900">Top Bar Background Color</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="color" v-model="form.builderSideBgColor" required class="h-8 w-8"/>
                                        <span class="font-medium text-gray-900">Side Bar Background Color</span>
                                    </label>

                                    <label class="flex items-center space-x-2">
                                        <input type="color" v-model="form.builderPrimaryColor" required class="h-8 w-8"/>
                                        <span class="font-medium text-gray-900">Primary Color</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="color" v-model="form.builderSecondaryColor" required class="h-8 w-8"/>
                                        <span class="font-medium text-gray-900">Secondary Color</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="color" v-model="form.builderFgColor" required class="h-8 w-8"/>
                                        <span class="font-medium text-gray-900">Text Color</span>
                                    </label>
                                </div>

                                <div class="mt-4">
                                    <label class="text-xs text-gray-900">Presets</label>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <div v-for="theme in themePresets" class="border flex w-max cursor-pointer" @click="handleThemePresetClick(theme)"
                                             :class="{'border-primary': isActivePreset(theme)}">
                                            <div class="h-6 w-4" :style="{backgroundColor: theme[0]}"></div>
                                            <div class="h-6 w-4 border-l" :style="{backgroundColor: theme[1]}"></div>
                                            <div class="h-6 w-4 border-l" :style="{backgroundColor: theme[2]}"></div>
                                            <div class="h-6 w-4 border-l" :style="{backgroundColor: theme[3]}"></div>
                                            <div class="h-6 w-4 border-l" :style="{backgroundColor: theme[4]}"></div>
                                            <div class="h-6 w-4 border-l" :style="{backgroundColor: theme[5]}"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script>
import {useForm, usePage} from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Checkbox from '@/Components/Checkbox.vue'
import {Disclosure, DisclosureButton, DisclosurePanel} from '@headlessui/vue'
import Spinner from '@/Components/Spinner.vue'
import {DEFAULT_AGREE_LABEL, DEFAULT_CONFIRMATION_LABEL} from '@/Utils/constants.js'
import Draggable from '@/Components/Draggable.vue'
import ColorPicker from '@/Components/ColorPicker.vue'
import DragIcon from '@/Icons/DragIcon.vue'
import ImageView from '@/Components/ImageView.vue'
import GbsSelect from '@/Components/Select.vue'
import SvgIcon from '@jamescoyle/vue-icon'
import {mdiPencilRuler, mdiViewDashboard, mdiImage, mdiPlus, mdiClose, mdiInformationOutline, mdiDrag} from '@mdi/js'
import Toggle from "@/Components/Toggle.vue";
import Selector from "@/Components/Selector.vue";
import {LANGUAGES} from "@/Builder/Utils/constants";

const FILE_TYPES = [
    {label: 'PNG', value: 'image/png'},
    {label: 'JPG/JPEG', value: 'image/jpg,image/jpeg'},
    {label: 'SVG', value: 'image/svg,image/svg+xml'},
    {label: 'PSD', value: '.psd'},
    {label: 'AI/ESP', value: 'application/postscript'},
    {label: 'PDF', value: 'application/pdf'},
]

const SETTING_TAGS = [
    {
        id: 'gang-sheet',
        title: 'Gang Sheet',
        icon: mdiImage
    },
    {
        id: 'builder',
        title: 'Builder',
        icon: mdiPencilRuler
    },
    {
        id: 'gallery',
        title: 'Image Gallery',
        icon: mdiImage
    },
    {
        id: 'appearance',
        title: 'Appearance',
        icon: mdiViewDashboard
    }
]

const THEME_PRESETS = [
    // main, top-bar, sidebar, primary, secondary, text
    ['#ffffff', '#ffffff', '#ffffff', '#0f172a', '#019aff', '#333333'],
    ['#ffffff', '#ffffff', '#ffffff', '#019aff', '#fb9133', '#222222'],
    ['#1e003f', '#0d001c', '#0d001c', '#a007ff', '#ce00bd', '#eeeeee'],
    ['#0f172a', '#020811', '#020811', '#005efc', '#005efc', '#eeeeee'],
]

const FONTS = [
    'Agdasima',
    'Barlow Condensed',
    'Inter',
    'Josefin Sans',
    'Lato',
    'Noto Sans',
    'Oswald',
    'PT Sans Narrow',
    'Roboto'
]

export default {
    name: 'SettingBuilderTab',
    components: {Selector, Toggle, ColorPicker, GbsSelect, ImageView, DragIcon, Draggable, Spinner, DisclosurePanel, DisclosureButton, Disclosure, Checkbox, PrimaryButton, SvgIcon},
    setup() {
        const user = usePage().props.auth.user
        return {
            form: useForm({
                galleryMode: user.settings?.galleryMode ?? 'dropdown',
                show_gallery: Boolean(user.show_gallery),
                enableColorOverlay: user.settings?.enableColorOverlay ?? false,
                colorOverlayAllowed: user.settings?.colorOverlayAllowed ?? 'all',
                watermark_opacity: user.settings?.watermark_opacity ?? 0.4,
                file_types: user.settings?.file_types ?? [...FILE_TYPES.map(file_type => file_type.value)],
                disableBackgroundRemoveTool: user.settings?.disableBackgroundRemoveTool ?? false,
                disableTextFeature: user.settings?.disableTextFeature ?? false,
                allowedAutoBuild: user.settings?.allowedAutoBuild ?? true,
                agree_check_flag: user.settings?.agree_check_flag ?? false,
                agree_label: user.settings?.agree_label || DEFAULT_AGREE_LABEL,
                gangSheetFileName: user.settings?.gangSheetFileName || '{customer_name}-{order_id}-{variant_title}-{line_item_number}-{design_name}-({quantity})',
                language: user.settings?.language ?? 'en',
                startModalView: user.settings?.startModalView ?? 'list',
                gangSheetFileExtension: user.settings?.gangSheetFileExtension ?? '.png',
                turnOnMargin: Boolean(user.settings?.turnOnMargin ?? true),
                defaultMarginSize: user.settings?.defaultMarginSize ?? 0.125,
                defaultMarginUnit: user.settings?.defaultMarginUnit ?? 'in',
                turnOnArtboardMargin: Boolean(user.settings?.turnOnArtboardMargin ?? true),
                defaultArtboardMarginSize: user.settings?.defaultArtboardMarginSize ?? user.settings?.defaultMarginSize ?? 0.125,
                defaultArtboardMarginUnit: user.settings?.defaultArtboardMarginUnit ?? 'in',
                printFileName: Boolean(user.settings?.printFileName ?? false),
                autoTrimGangSheet: Boolean(user.settings?.autoTrimGangSheet ?? false),
                collectShippingAddress: Boolean(user.settings?.collectShippingAddress ?? true),
                printFileNamePosition: user.settings?.printFileNamePosition ?? 'top',
                printFileNameHeight: user.settings?.printFileNameHeight ?? 1,
                startModals: (() => {
                    const defaultStartModals = [
                        {
                            id: 1,
                            image: '',
                            label: 'Start a Brand New Gang Sheet',
                            enabled: true
                        }, {
                            id: 2,
                            image: '',
                            label: 'Open a Previously Ordered Gang Sheet',
                            enabled: true
                        }, {
                            id: 3,
                            image: '',
                            label: 'Auto Build',
                            enabled: true
                        }, {
                            id: 4,
                            image: '',
                            label: 'Open Working Gang Sheet',
                            enabled: true
                        }, {
                            id: 5,
                            image: '',
                            label: 'Name and Number',
                            enabled: false
                        }
                    ];

                    const existingStartModals = (user.settings?.startModals ?? []).map(item => {
                        item.id = Number(item.id);
                        item.enabled = Boolean(item.enabled)
                        return item;
                    });

                    const missingItems = defaultStartModals.filter(defaultItem =>
                        !existingStartModals.some(existingItem => existingItem.id === defaultItem.id)
                    );

                    return [...existingStartModals, ...missingItems];
                })(),
                useCustomTextColors: user.settings?.useCustomTextColors ?? false,
                customTextColors: user.settings?.customTextColors ?? [],
                useCustomImageOverlayColors: user.settings?.useCustomImageOverlayColors ?? false,
                enableAddNewDesign: user.settings?.enableAddNewDesign ?? true,
                enableQuantity: user.settings?.enableQuantity ?? true,
                confirmationButtonLabel: user.settings?.confirmationButtonLabel ?? DEFAULT_CONFIRMATION_LABEL,
                customImageOverlayColors: user.settings?.customImageOverlayColors ?? [],
                useUploadDropbox: user.settings?.useUploadDropbox ?? false,
                dropboxFolderName: user.settings?.dropboxFolderName || '',
                enableChat: user.settings?.enableChat ?? false,
                chatScript: user.settings?.chatScript || '',
                shop_email: user.settings?.shop_email || user.email || '',
                enableCanva: Boolean(user.settings?.enableCanva ?? false),
                enableImageBackgroundWarning: Boolean(user.settings?.enableImageBackgroundWarning ?? false),
                ensureOptimalResolution: Boolean(user.settings?.ensureOptimalResolution ?? true),
                builderBgColor: user.settings?.builderBgColor ?? THEME_PRESETS[0][0],
                builderTopBgColor: user.settings?.builderTopBgColor ?? THEME_PRESETS[0][1],
                builderSideBgColor: user.settings?.builderSideBgColor ?? THEME_PRESETS[0][2],
                builderPrimaryColor: user.settings?.builderPrimaryColor ?? THEME_PRESETS[0][3],
                builderSecondaryColor: user.settings?.builderSecondaryColor ?? THEME_PRESETS[0][4],
                builderFgColor: user.settings?.builderFgColor ?? THEME_PRESETS[0][5],
                showStartModal: user.settings?.showStartModal ?? true,
                builderFont: user.settings?.builderFont ?? 'Oswald',
                enableFlipping: Boolean(user.settings?.enableFlipping ?? true),
            })
        }
    },
    data() {
        const user = this.$page.props.auth.user

        return {
            showTextColorPicker: false,
            showImageOverlayColorPicker: false,
            total: 0,
            processed: 0,
            watermark_processing: false,
            watermark_applying: false,
            file_types: [...FILE_TYPES],
            interval: 0,
            user,
            openTab: null,
            activeTab: SETTING_TAGS[0],
            tabs: SETTING_TAGS,
            languages: LANGUAGES,
            fonts: FONTS,
            themePresets: THEME_PRESETS,

            connectedDropbox: false,
            dropboxToken: null,
            connectingDropbox: true,
            disConnectingDropbox: false,
            dropboxFolderName: '',
            dropboxAuthUrl: '',

            mdiClose,
            mdiPlus,
            mdiInformationOutline,
            mdiDrag
        }
    },
    created() {
        this.watermark_processing = this.user.settings?.watermark_processing ?? false
        if (this.watermark_processing) {
            this.interval = setInterval(this.checkWatermarkProcessing.bind(this), 2000)
        }
    },
    mounted() {
        this.getDropboxStatus()
    },
    computed: {
        isOpacityChanged() {
            if (!this.user.settings) {
                return true
            }

            if (!this.user.settings.hasOwnProperty('watermark_version')) {
                return true
            }

            return (this.user.settings?.watermark_opacity ?? 0.5) !== this.form.watermark_opacity
        },
        isValidFileName() {
            const name = this.form.gangSheetFileName
                .replace('{order_name}', '')
                .replace('{customer_name}', '')
                .replace('{order_id}', '')
                .replace('{variant_title}', '')
                .replace('{quantity}', '')
                .replace('{design_name}', '')
                .replace('{line_item_number}', '')
                .replace('{shipping_method}', '')

            return /^[a-zA-Z0-9\s_\-()$]+$/g.test(name)
        },
        language: {
            get() {
                return this.languages.find(l => l.value === this.form.language)
            },
            set(v) {
                this.form.language = v?.value ?? 'en'
            }
        },
        isValidDropboxFolderName() {
            if (this.connectedDropbox && this.form.useUploadDropbox) {
                const name = this.dropboxFolderName
                    .replace('{order_id}', '')
                    .replace('{product_id}', '')
                    .replace('{order_name}', '')

                if (!(/^[a-zA-Z0-9\s#_\/\-()$]+$/g.test(name))) {
                    return false
                }
                this.form.dropboxFolderName = this.dropboxFolderName
            }
            return true
        }
    },
    methods: {
        checkWatermarkProcessing() {
            axios.get(route('merchant.setting.check-watermark-opacity-status')).then((res) => {
                this.watermark_processing = res.data.running
                this.total = res.data.total
                this.processed = res.data.processed
                if (!this.watermark_processing) {
                    clearInterval(this.interval)
                }
            })
        },
        handleWatermarkOpacityApply(e) {
            e.preventDefault()
            this.watermark_applying = true
            axios.post(route('merchant.setting.apply-watermark-opacity'), {
                watermark_opacity: this.form.watermark_opacity
            }).then(() => {
                this.watermark_applying = false
                this.watermark_processing = true
                if (!this.user.settings) {
                    this.user.settings = {}
                }
                this.user.settings.watermark_opacity = this.form.watermark_opacity
                this.user.settings.watermark_version = 1
                this.interval = setInterval(this.checkWatermarkProcessing.bind(this), 2000)
                window.Toast.success({
                    message: 'Successfully updated!'
                })
            })
        },
        handleSubmit() {
            if (!this.isValidFileName) {
                window.Toast.error({
                    message: 'File Name is not valid.'
                })
                return
            }

            if (!this.isValidDropboxFolderName) {
                window.Toast.error({
                    message: 'Dropbox Folder Name is not valid.'
                })
                return
            }

            this.form.post(route('merchant.setting.builder'), {
                onSuccess: () => {
                    window.Toast.success({
                        message: 'Successfully updated.'
                    })
                }
            })
        },
        addCustomTextColor(nwColor) {
            if (!this.form.customTextColors.includes(nwColor.hex)) this.form.customTextColors.push(nwColor.hex)
        },
        removeCustomTextColor(index) {
            this.form.customTextColors.splice(index, 1)
        },
        showCustomTextColorPicker() {
            this.showTextColorPicker = true
        },
        hideCustomTextColorPicker() {
            this.showTextColorPicker = false
        },
        addCustomImageOverlayColor(nwColor) {
            if (!this.form.customImageOverlayColors.includes(nwColor.hex)) this.form.customImageOverlayColors.push(nwColor.hex)
        },
        removeCustomImageOverlayColor(index) {
            this.form.customImageOverlayColors.splice(index, 1)
        },
        showCustomImageOverlayColorPicker() {
            this.showImageOverlayColorPicker = true
        },
        hideCustomImageOverlayColorPicker() {
            this.showImageOverlayColorPicker = false
        },
        getDropboxStatus() {
            window.axios.get(route('merchant.setting.dropbox')).then(res => {
                if (res.data.success) {
                    this.dropboxAuthUrl = res.data.auth_url
                    this.dropboxToken = res.data.token
                    this.connectedDropbox = res.data.connected
                    this.dropboxFolderName = res.data.folderName
                }
            }).finally(() => {
                this.connectingDropbox = false
                this.disConnectingDropbox = false
            })
        },
        handleDisconnectDropbox() {
            this.disConnectingDropbox = true
            axios.post(route('merchant.setting.revoke-dropbox')).then(() => {
                this.getDropboxStatus()
            })
        },
        handleConnectDropbox() {
            const popupWinWidth = 600
            const popupWinHeight = 600
            const left = (screen.width - popupWinWidth) / 2;
            const top = (screen.height - popupWinHeight) / 4;
            this.connectingDropbox = true
            const newWindow = window.open(
                this.dropboxAuthUrl,
                'Connect Dropbox',
                `popup=yes,height=${popupWinHeight},width=${popupWinWidth},top=${top},left=${left}`
            );
            if (window.focus) {
                newWindow.focus()
            }
            const timer = setInterval(async () => {
                if (newWindow.closed) {
                    clearInterval(timer);
                    this.getDropboxStatus()
                }
            }, 1000);
        },
        handleThemePresetClick(preset) {
            this.form.builderBgColor = preset[0]
            this.form.builderTopBgColor = preset[1]
            this.form.builderSideBgColor = preset[2]
            this.form.builderPrimaryColor = preset[3]
            this.form.builderSecondaryColor = preset[4]
            this.form.builderFgColor = preset[5]
        },
        isActivePreset(preset) {
            return this.form.builderBgColor === preset[0] &&
                this.form.builderTopBgColor === preset[1] &&
                this.form.builderSideBgColor === preset[2] &&
                this.form.builderPrimaryColor === preset[3] &&
                this.form.builderSecondaryColor === preset[4] &&
                this.form.builderFgColor === preset[5]
        },
        getStartModelId(id) {
            switch (id) {
                case 1:
                    return 'Start New Design'
                case 2:
                    return 'Open Past Designs'
                case 3:
                    return 'Start Auto Build'
                case 4:
                    return 'Open Saved Design'
                case 5:
                    return 'Open Name & Numbers'
            }
        }
    }
}
</script>
