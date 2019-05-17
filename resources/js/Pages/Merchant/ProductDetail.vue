<template>
    <MerchantLayout title="Products">
        <form @submit.prevent="onSubmit">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center justify-center">
                    <div class="w-8 h-8 flex items-center justify-center cursor-pointer mr-2 rounded-lg hover:bg-gray-200" @click.prevent="$inertia.get(route('merchant.product.index'))">
                        <arrow-left-icon/>
                    </div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        <span v-if="product?.id"> {{ product.title }} </span>
                        <span v-else>Create Product</span>
                    </h2>
                </div>

                <div class="flex items-center justify-center space-x-2">
                    <SecondaryButton v-if="product" @click="handleView">
                        View Builder
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">
                        Submit
                    </PrimaryButton>
                </div>
            </div>
            <div class="grid lg:grid-cols-2 gap-4 w-full mt-4 pb-20">
                <div class="flex flex-col rounded-lg shadow bg-white overflow-hidden p-4" v-if="$page.props.auth.user.type !== 'woo'">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left space-y-2">
                        <div class="space-y-1">
                            <InputLabel for="title" value="Title"/>
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="w-full"
                                required
                                @input="onChangeTitle"
                            />
                            <InputError :message="form.errors.title"/>
                        </div>

                        <div class="space-y-1">
                            <InputLabel for="code" value="Code"/>
                            <TextInput
                                id="code"
                                v-model="form.slug"
                                type="text"
                                class="w-full"
                                required
                            />
                            <InputError :message="form.errors.slug"/>
                        </div>

                        <div class="space-y-1">
                            <InputLabel for="redirect_url" value="Redirect URL"/>
                            <TextInput
                                id="redirect_url"
                                v-model="form.redirect_url"
                                type="text"
                                class="w-full"
                                required
                            />
                            <InputError :message="form.errors.redirect_url"/>
                        </div>

                        <div class="space-y-1">
                            <InputLabel for="description" value="Description"/>
                            <TextField
                                id="description"
                                v-model="form.description"
                                type="text"
                                class="w-full"
                            />
                            <InputError :message="form.errors.description"/>
                        </div>

                        <div class="space-y-1">
                            <InputLabel for="status" value="Status"/>
                            <SelectBox v-model="form.status" :options="statuses"/>
                            <InputError :message="form.errors.status"/>
                        </div>

                        <div class="space-y-2 mt-5">
                            <InputLabel for="button" value="Button" class="font-semibold"/>
                            <div class="pl-2 space-y-2">
                                <div class="space-y-1">
                                    <InputLabel for="button_text" value="Label"/>
                                    <TextInput type="text" v-model="form.button_text" id="button_text" class="w-full"/>
                                    <InputError :message="form.errors.button_text"/>
                                </div>
                                <div class="grid grid-cols-2">
                                    <div class="flex items-center">
                                        <input type="color" v-model="form.button_background_color" class="h-8 w-8 mr-2"/>
                                        <InputLabel for="button_background_color" value="Background Color"/>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="color" v-model="form.button_text_color" class="h-8 w-8 mr-2"/>
                                        <InputLabel for="button_background_color" value="Text Color"/>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <InputLabel for="button_preview" value="Preview"/>
                                    <a href="#" class="block p-2 text-center rounded" :style="{ backgroundColor: form.button_background_color, color: form.button_text_color}">
                                        {{ form.button_text }}
                                    </a>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex space-x-2">
                                        <InputLabel for="button_script" value="Script"/>
                                        <button type="button" @click="handleCopy" class="text-sm text-primary">Copy</button>
                                    </div>
                                    <TextField class="w-full" readonly :model-value="buttonScript"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col rounded-lg shadow bg-white overflow-hidden p-4">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left space-y-3">

                        <div class="space-y-1">
                            <InputLabel for="art_board_type" value="Art Board Type" class="font-semibold"/>
                            <SelectBox v-model="form.art_board_type" :options="artBoardTypes"/>
                        </div>

                        <div class="space-y-1">
                            <InputLabel for="sizes" value="Sizes" class="font-semibold mb-2"/>
                            <div v-if="form.art_board_type !== 6" class="mt-2">
                                <table class="w-full text-xs table-fixed">
                                    <thead>
                                    <tr>
                                        <td class="w-16">Size ID</td>
                                        <td>Width</td>
                                        <td>Height</td>
                                        <td>Unit</td>
                                        <td>Price</td>
                                        <td>Max Allowed Files</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <template v-for="(size, index) in form.sizes">
                                        <tr v-if="!size.delete">
                                            <td>
                                                {{ size.id ?? 'new' }}
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" v-model="size.width" class="w-full" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" v-model="size.height" class="w-full" required>
                                            </td>
                                            <td>
                                                <select v-model="size.unit" class="w-full" required>
                                                    <option value="in">in</option>
                                                    <option value="cm">cm</option>
                                                    <option value="mm">mm</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" v-model="size.price" class="w-full" required>
                                            </td>
                                            <td class="h-1">
                                                <div class="flex justify-center items-center w-full h-full">
                                                    <Select size="sm" :model-value="size.max_allowed_files" @select="handleInput($event, size)" :options="[-1, 1, 2]" class="w-full h-full">
                                                        <template #selected="{selected}">
                                                            <span v-if="size.max_allowed_files <= -1">Unlimited</span>
                                                            <span v-else-if="size.max_allowed_files === 1">Single File</span>
                                                            <span v-else>
                                                                    <input @click.prevent.stop="" @input="handleSizeInput($event, size)" required :min="2" :step="1" :value="size.max_allowed_files"
                                                                           type="number"
                                                                           class="h-5 border-none w-20 text-sm focus:ring-0 focus:outline-0 appearance-none"/>
                                                                </span>
                                                        </template>
                                                        <template #option="{option}">
                                                            <span v-if="option === -1">Unlimited</span>
                                                            <span v-else-if="option === 1">Single File</span>
                                                            <span v-else>Custom</span>
                                                        </template>
                                                    </Select>
                                                </div>
                                            </td>
                                            <td class="pl-2">
                                                <div class="flex justify-end space-x-2">
                                                    <button type="button" @click="addSize(index)"
                                                            class="w-8 h-8 rounded-full border border-gray-300 hover:text-white hover:bg-cyan-500">
                                                        <i class="mdi mdi-plus"></i>
                                                    </button>
                                                    <button type="button" @click="deleteSize(index)"
                                                            class="w-8 h-8 rounded-full border border-red-500 text-red-500 hover:text-white hover:bg-red-500">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    </tbody>
                                </table>
                                <InputError :message="form.errors.sizes"/>
                                <div class="text-right">
                                    <PrimaryButton type="button" @click="addSize">Add Size</PrimaryButton>
                                </div>
                            </div>

                            <div v-else>
                                <hr class="my-2"/>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-medium text-sm text-gray-900">Printer Width</span>
                                    <div class="inp-outline mt-1 px-3 py-1.5">
                                        <input type="number" v-model="form.printerWidth" class="w-28 inp-no-style p-0" step="0.1" min="0" :max="sizeMaxWidth" required>
                                        <span class="pl-2">{{ unit }}</span>
                                    </div>
                                </div>
                                <hr class="my-2"/>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-medium text-sm text-gray-900">Start Height</span>
                                    <div class="inp-outline mt-1 px-3 py-1.5">
                                        <input type="number" v-model="form.startHeight" class="w-28 inp-no-style p-0" step="0.1" min="0" :max="sizeMaxHeight" required>
                                        <span class="pl-2">{{ unit }}</span>
                                    </div>
                                </div>
                                <hr class="my-2"/>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-medium text-sm text-gray-900">Min Height</span>
                                    <div class="inp-outline mt-1 px-3 py-1.5">
                                        <input type="number" v-model="form.minHeight" class="w-28 inp-no-style p-0" step="0.1" min="1" :max="sizeMaxHeight" required>
                                        <span class="pl-2">{{ unit }}</span>
                                    </div>
                                </div>
                                <hr class="my-2"/>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-medium text-sm text-gray-900">Max Height</span>
                                    <div class="inp-outline mt-1 px-3 py-1.5">
                                        <input type="number" v-model="form.maxHeight" class="w-28 inp-no-style p-0" step="0.1" :max="sizeMaxHeight" required>
                                        <span class="pl-2">{{ unit }}</span>
                                    </div>
                                </div>
                                <hr class="my-2"/>
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <label class="text-sm font-medium text-gray-900">Pricing</label>
                                    <selector v-model="form.pricing.type" :options="[{label: 'Flat', value: 'flat'}, {label: 'Tiered', value: 'tiered'}]"/>
                                </div>
                                <div v-if="form.pricing.type === 'flat'" class="flex items-center justify-between mt-2">
                                    <span class="text-sm text-gray-900">Price</span>
                                    <div class="inp-outline mt-1 px-3 py-1.5">
                                        <input type="number" v-model="form.pricing.price" class="w-24 inp-no-style p-0" step="0.00001" min="0" required>
                                        <span class="pl-2">$ per. sqr {{ unit }}</span>
                                    </div>
                                </div>
                                <div v-else class="flex items-center justify-center mt-2">
                                    <table class="w-full text-center text-sm table-fixed">
                                        <thead>
                                        <tr>
                                            <td>~Max Height ({{ unit }})</td>
                                            <td>Max Area (sq.{{ unit }})</td>
                                            <td>
                                                Price ($ per.sq {{ unit }})
                                            </td>
                                            <td>
                                                Discount (%)
                                            </td>
                                            <td></td>
                                        </tr>
                                        </thead>
                                        <tr v-for="(priceOption, index) in form.pricing.prices">
                                            <td class="py-px px-1">
                                                <input v-if="index < form.pricing.prices.length - 1" type="number" v-model="priceOption.height" @input="handleHeightChange" step="0.01" min="0"
                                                       :max="form.maxHeight" required
                                                       class="py-1 px-2 w-full"/>
                                                <input v-else type="number" :value="form.maxHeight" :disabled="true" required class="py-1 px-2 w-full"/>
                                            </td>
                                            <td class="py-px px-1">
                                                <input type="number" v-model="priceOption.area" :disabled="index === form.pricing.prices.length - 1" required @input="handleAreaChange"
                                                       class="py-1 px-2 w-full"/>
                                            </td>
                                            <td class="py-px px-1">
                                                <input type="number" v-model="priceOption.price" step="0.00001" min="0" required @input="handlePriceChange(index)" class="py-1 w-full"/>
                                            </td>
                                            <td class="py-px px-1">
                                                <input type="number" v-model="priceOption.discount" step="1" min="0" max="100" :disabled="index === 0" :required="index > 0"
                                                       @input="handleDiscountChange" class="py-1 w-full"/>
                                            </td>
                                            <td class="flex justify-end space-x-2">
                                                <div class="flex space-x-1 px-1">
                                                    <button type="button" @click="addPrice(index)"
                                                            class="w-8 h-8 rounded-full border border-gray-300 hover:text-white hover:bg-cyan-500">
                                                        <i class="mdi mdi-plus"></i>
                                                    </button>
                                                    <button type="button" @click="removePrice(index)" :disabled="index === form.pricing.prices.length - 1"
                                                            class="w-8 h-8 rounded-full border border-red-500 text-red-500 hover:text-white hover:bg-red-500 disabled:bg-gray-300 disabled:text-gray-500 disabled:border-gray-300">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 text-left">
                            <InputLabel for="settings" value="Settings" class="font-semibold"/>

                            <div class="border-b py-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-sm text-gray-900">Disable Upload Images</span>
                                    <toggle v-model="form.disableUploadingImage"/>
                                </div>
                            </div>

                            <div class="border-b py-2">
                                <div class="space-y-1 flex justify-between items-center">
                                    <span class="font-medium text-sm text-gray-900">Enable Print File Name</span>
                                    <toggle v-model="form.printFileName"/>
                                </div>

                                <div v-if="form.printFileName" class="space-y-1 flex justify-between items-center mt-4">
                                    <span class="font-semibold text-xs text-gray-900">File Name Position</span>
                                    <selector v-model="form.printFileNamePosition" :options="['top', 'bottom', 'both']"/>
                                </div>

                                <div v-if="form.printFileName" class="space-y-1 flex justify-between items-center mt-2">
                                    <span class="font-semibold text-xs text-gray-900">File Name Height</span>
                                    <selector v-model="form.printFileNameHeight" :options="[{label: 'Normal', value: 1}, {label: 'Small', value: 0.7}, {label: 'Tight', value: 0.4}]"/>
                                </div>
                            </div>

                            <div class="border-b py-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-sm text-gray-900">Enable Name And Number</span>
                                    <toggle v-model="form.nameAndNumber.enabled"/>
                                </div>

                                <div v-if="form.nameAndNumber.enabled" class="">
                                    <div class="flex items-center justify-between mt-4">
                                        <span class="font-semibold text-xs text-gray-900">Unit</span>
                                        <div
                                            class="mt-1 w-full flex items-center border rounded border-gray-300 text-xs  max-w-max focus-within:border-blue-600 focus-within:ring focus-within:ring-blue-600 focus-within:ring-opacity-20">
                                            <select v-model="form.nameAndNumber.unit" class="w-28 inp-no-style py-1">
                                                <option value="in">in</option>
                                                <option value="cm">cm</option>
                                                <option value="mm">mm</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-4">
                                        <span class="font-semibold text-xs text-gray-900">Name Height Small</span>
                                        <div
                                            class="mt-1 w-full flex items-center border rounded border-gray-300 text-xs  max-w-max focus-within:border-blue-600 focus-within:ring focus-within:ring-blue-600 focus-within:ring-opacity-20">
                                            <input type="number" v-model="form.nameAndNumber.size.name.sm" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                            <span class="pr-2">{{ form.nameAndNumber.unit }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="font-semibold text-xs text-gray-900">Name Height Large</span>
                                        <div
                                            class="mt-1 w-full flex items-center border rounded border-gray-300 text-xs  max-w-max focus-within:border-blue-600 focus-within:ring focus-within:ring-blue-600 focus-within:ring-opacity-20">
                                            <input type="number" v-model="form.nameAndNumber.size.name.lg" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                            <span class="pr-2">{{ form.nameAndNumber.unit }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-4">
                                        <span class="font-semibold text-xs text-gray-900">Number Height Small</span>
                                        <div
                                            class="mt-1 w-full flex items-center border rounded border-gray-300 text-xs  max-w-max focus-within:border-blue-600 focus-within:ring focus-within:ring-blue-600 focus-within:ring-opacity-20">
                                            <input type="number" v-model="form.nameAndNumber.size.number.sm" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                            <span class="pr-2">{{ form.nameAndNumber.unit }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="font-semibold text-xs text-gray-900">Number Height Large</span>
                                        <div
                                            class="mt-1 w-full flex items-center border rounded border-gray-300 text-xs  max-w-max focus-within:border-blue-600 focus-within:ring focus-within:ring-blue-600 focus-within:ring-opacity-20">
                                            <input type="number" v-model="form.nameAndNumber.size.number.lg" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                            <span class="pr-2">{{ form.nameAndNumber.unit }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.art_board_type === 1" class="border-b py-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-sm text-gray-900">Enable Product Pattern</span>
                                    <toggle v-model="productPatternEnabled"/>
                                </div>
                                <div v-if="product?.id && productPatternEnabled" class="flex flex-col items-end">
                                    <div class="mt-4">
                                        <PrimaryButton type="button" @click="editPattern">Edit Pattern</PrimaryButton>
                                    </div>
                                    <div class="flex mt-4">
                                        <span class="font-medium text-sm text-gray-900">Generate gangsheet with pattern: &nbsp;</span>
                                        <toggle v-model="form.productPattern.printPattern"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </MerchantLayout>
</template>

<script>
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import MerchantLayout from "@/Layouts/MerchantLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import SvgIcon from "@jamescoyle/vue-icon";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextField from "@/Components/TextField.vue";
import SelectBox from "@/Components/SelectBox.vue";
import Select from "@/Components/Select.vue";
import Toggle from "@/Components/Toggle.vue";
import Selector from "@/Components/Selector.vue";
import ArrowLeftIcon from "@/Builder/Icons/ArrowLeftIcon.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {mdiPlus, mdiTrashCanOutline} from '@mdi/js'

const ART_BOARD_GANG_SHEET = 1;
const ART_BOARD_ROLLING_GANG_SHEET = 6;
const ART_BOARD_STICKER = 2;
const ART_BOARD_LASER = 3;
const ART_BOARD_BUSINESS_CARD = 4;
const ART_BOARD_BANNER = 5;

const ART_BOARD_TYPES = [
    {label: 'Gang Sheet', value: ART_BOARD_GANG_SHEET},
    {label: 'Rolling Gang Sheet', value: ART_BOARD_ROLLING_GANG_SHEET},
    // {label: 'Sticker', value: ART_BOARD_STICKER},
    // {label: 'Laser', value: ART_BOARD_LASER},
    // {label: 'Business Card', value: ART_BOARD_BUSINESS_CARD},
    // {label: 'Banner', value: ART_BOARD_BANNER},
]

export default {
    components: {SecondaryButton, ArrowLeftIcon, PrimaryButton, SvgIcon, MerchantLayout, Link, InputLabel, TextInput, InputError, TextField, SelectBox, Select, Toggle, Selector},
    props: {
        product: {
            type: Object,
            required: true
        },
    },
    data() {
        return {
            form: this.buildForm(),
            productPatternEnabled: this.product?.settings?.productPattern?.enabled ?? false,
            statuses: [
                {
                    label: 'Active',
                    value: 'active'
                },
                {
                    label: 'Inactive',
                    value: 'inactive'
                },
            ],
            artBoardTypes: ART_BOARD_TYPES,
            sizes: [],
            unit: 'in',

            mdiPlus,
            mdiTrashCanOutline,
        }
    },
    watch: {
        productPatternEnabled() {
            this.form.productPattern.enabled = this.productPatternEnabled
            if (this.productPatternEnabled === false && this.form.productPattern.printPattern) {
                this.form.productPattern.printPattern = false
            }
        }
    },
    methods: {
        buildForm() {
            const product = this.product
            const button = product?.button
            const user = usePage().props.auth.user
            const settings = product?.settings

            let sizeUnit = 'in'

            if (product?.sizes[0]?.unit) {
                sizeUnit = product.sizes[0]?.unit
            }

            this.unit = sizeUnit

            const nameAndNumber = settings?.nameAndNumber ?? {
                enabled: false,
                unit: 'in',
                size: {
                    name: {
                        sm: 1,
                        lg: 2,
                    },
                    number: {
                        sm: 4,
                        lg: 8,
                    }
                },
                fonts: null,
                colors: null,
            }

            if (!nameAndNumber.unit) {
                nameAndNumber.unit = 'in'
            }

            return useForm({
                id: product?.id,
                title: product?.title,
                woo_product_id: product?.woo_product_id,
                description: product?.description,
                slug: product?.slug,
                redirect_url: product?.redirect_url,
                sizes: product?.sizes ?? [],
                button_text: button?.text || 'Build a Gang Sheet',
                button_background_color: button?.background_color || '#000000',
                button_text_color: button?.text_color || '#ffffff',
                status: product?.deleted_at ? 'inactive' : 'active',
                art_board_type: product?.type ?? 1,
                disableUploadingImage: settings?.disableUploadingImage ?? false,
                printFileName: settings?.printFileName ?? user.settings?.printFileName ?? false,
                printFileNamePosition: settings?.printFileNamePosition ?? user.settings?.printFileNamePosition ?? 'top',
                printFileNameHeight: settings?.printFileNameHeight ?? user.settings?.printFileNameHeight ?? 1,
                nameAndNumber,
                productPattern: {
                    enabled: settings?.productPattern?.enabled ?? false,
                    printPattern: settings?.productPattern?.printPattern ?? false,
                },
                printerWidth: settings?.printerWidth ?? 22,
                startHeight: settings?.startHeight ?? 40,
                minHeight: settings?.minHeight ?? 20,
                maxHeight: settings?.maxHeight ?? 360,
                pricing: settings?.pricing ?? {
                    type: "flat",
                    price: '',
                    prices: [
                        {
                            height: settings?.maxHeight ?? 360,
                            price: '',
                            area: (settings?.maxHeight ?? 360) * (settings?.printerWidth ?? 22),
                            discount: 0,
                        }
                    ]
                },
            })
        },
        sizeMaxHeight() {
            if (this.unit === 'in') {
                return 360
            } else if (this.unit === 'mm') {
                return 10000
            } else if (this.unit === 'cm') {
                return 1000
            }

            return 360
        },
        sizeMaxWidth() {
            if (this.unit === 'in') {
                return 40
            } else if (this.unit === 'mm') {
                return 1000
            } else if (this.unit === 'cm') {
                return 100
            }

            return 40
        },
        addSize(index) {
            if (index >= 0) {
                this.form.sizes.splice(index + 1, 0, {
                    max_allowed_files: -1
                })
            } else {
                this.form.sizes.push({
                    max_allowed_files: -1
                })
            }
        },
        editPattern() {
            window.open(
                route('merchant.product.pattern', {product_id: this.product.id}),
                '_blank'
            );
        },
        deleteSize(index) {
            if (this.form.sizes[index].id) {
                this.form.sizes[index].delete = true
            } else {
                this.form.sizes.splice(index, 1)
            }
        },
        removePrice(index) {
            this.form.pricing.prices.splice(index, 1)
        },
        addPrice(index) {
            const newPrice = {
                height: '',
                price: '',
                area: '',
                discount: 0
            }

            if (index === this.form.pricing.prices.length - 1) {
                this.form.pricing.prices.unshift(newPrice)
            } else {
                this.form.pricing.prices.splice(index + 1, 0, newPrice)
            }

        },
        onSubmit() {
            if (this.form.art_board_type === ART_BOARD_ROLLING_GANG_SHEET) {
                this.form.sizes.forEach(size => {
                    size.delete = true
                })
                this.form.sizes.push(...this.getRollingGangSheetVariants());
            } else {
                this.form.sizes = this.getGangSheetVariants();
            }
            this.form.post(route('merchant.product.save'), {
                onSuccess: () => {
                    Toast.success({
                        message: 'Product saved successfully.'
                    })
                    this.form = this.buildForm()
                },
                onError: (errors) => {
                    console.error(errors)
                    Toast.error({
                        message: 'Failed to save product.'
                    })
                }
            })
        },
        onChangeTitle(e) {
            this.form.slug = e.target.value.toLowerCase().replaceAll(' ', '-')
        },
        handleDiscountChange() {
            for (let i = 1; i < this.form.pricing.prices.length; i++) {
                if (this.form.pricing.prices[i].discount) {
                    this.form.pricing.prices[i].price = Number(((this.form.pricing.prices[0].price * (100 - this.form.pricing.prices[i].discount)) / 100).toFixed(6))
                }
            }
        },
        handlePriceChange(index) {
            if (index === 0) {
                this.form.pricing.prices[0].discount = 0
                this.handleDiscountChange()
            } else {
                for (let i = 1; i < this.form.pricing.prices.length; i++) {
                    if (this.form.pricing.prices[i].price) {
                        this.form.pricing.prices[i].discount = Math.round(100 - (this.form.pricing.prices[i].price / this.form.pricing.prices[0].price) * 100)
                    }
                }
            }
        },
        handleHeightChange() {
            for (let i = 0; i < this.form.pricing.prices.length; i++) {
                if (this.form.pricing.prices[i].height) {
                    this.form.pricing.prices[i].area = Number((this.form.pricing.prices[i].height * this.form.printerWidth).toFixed(2))
                }
            }
        },
        handleAreaChange() {
            for (let i = 0; i < this.form.pricing.prices.length; i++) {
                if (this.form.pricing.prices[i].area) {
                    this.form.pricing.prices[i].height = Number((this.form.pricing.prices[i].area / this.form.printerWidth).toFixed(2))
                }
            }
        },
        handleCopy() {
            navigator.clipboard.writeText(this.buttonScript)
                .then(() => {
                    Toast.success({
                        message: 'Copied the button script.'
                    })
                })
                .catch((e) => {
                    console.error(e)
                    Toast.error({
                        message: 'Failed to copy'
                    })
                });
        },
        handleInput(value, size) {
            if (value < 2) {
                size.max_allowed_files = value
            }

            if (value === 2 && size.max_allowed_files < 2) {
                size.max_allowed_files = 2
            }
        },
        handleSizeInput(e, size) {
            if (e.target.value !== '') {
                if (Number(e.target.value) <= 0) {
                    size.max_allowed_files = -1
                }
            }

            if (Number(e.target.value) > 1) {
                size.max_allowed_files = Number(e.target.value)
            }
        },
        getRollingGangSheetVariants() {
            const printerWidth = this.form.printerWidth;
            const maxHeight = this.form.maxHeight;

            const sizes = []
            if (this.form.pricing.type === 'flat') {
                const area = printerWidth * maxHeight;
                const price = this.form.pricing.price * area;

                sizes.push({
                    label: `${printerWidth} ${this.unit} x ~${maxHeight} ${this.unit}`,
                    price: price,
                    width: printerWidth,
                    height: maxHeight,
                    unit: this.unit,
                    max_allowed_files: -1,
                });
            } else if (this.form.pricing.type === 'tiered') {
                this.form.pricing.price = this.form.pricing.prices[0].price;
                this.form.pricing.prices.forEach(priceOption => {
                    const area = printerWidth * priceOption.height;
                    const price = priceOption.price * area;
                    sizes.push({
                        label: `${printerWidth} ${this.unit} x ~${priceOption.height} ${this.unit}`,
                        price: price,
                        width: printerWidth,
                        height: priceOption.height,
                        unit: this.unit,
                        max_allowed_files: -1,
                    });
                });
            }

            return sizes;
        },
        getGangSheetVariants() {
            return this.form.sizes.map(size => {
                return {
                    label: `${size.width} ${size.unit} x ${size.height} ${size.unit}`,
                    ...size
                }
            })
        },
        handleView() {
            const userSlug = this.$page.props.auth.user.slug
            if (userSlug) {
                window.open(
                    `https://${userSlug}.${this.$page.props.appDomain}/builder/create/${this.product.slug}`,
                    '_blank'
                );
            } else {
                window.Toast.warning({
                    message: 'You should fill out company information.'
                })
                router.get(route('merchant.setting.index') + '#company')
            }
        }
    },
    computed: {
        buttonScript() {
            const merchant = this.$page.props.auth.user;

            const url = `https://${merchant.domain ? merchant.domain : merchant.slug + '.' + this.$page.props.appDomain}/builder/create/${this.form.slug}`
            return `<a href="${url}" style="padding: 0.5rem; display: block; text-align: center; border-radius: 0.25rem; background-color: ${this.form.button_background_color}; color: ${this.form.button_text_color}">${this.form.button_text}</a>`
        },
    }
}
</script>
