<script>
import {useForm} from "@inertiajs/vue3";
import {defineComponent} from 'vue'
import Spinner from "@/Components/Spinner.vue";
import Select from "@/Components/Select.vue";
import {cacheProduct, updateProduct} from '@/Woo/Apis/gsbApi';
import wooMixin from '@/Woo/WooMixin'
import LoginButton from '@/Woo/Components/LoginButton.vue'
import Selector from "@/Components/Selector.vue";
import ChevronDownIcon from "@/Builder/Icons/ChevronDownIcon.vue";
import ChevronRightIcon from "@/Builder/Icons/ChevronRightIcon.vue";
import Toggle from "@/Components/Toggle.vue";

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

export default defineComponent({
    name: "ProductDetail",
    components: {Spinner, Select, LoginButton, Selector, ChevronDownIcon, ChevronRightIcon, Toggle},
    props: {
        product: {
            type: Object,
            required: true,
        }
    },
    emits: ["update:product"],
    data() {
        return {
            submitting: false,
            form: null,
            sizes: [],
            units: ['in', 'cm', 'mm'],
            isLabel: false,
            artBoardTypes: ART_BOARD_TYPES,
            artBoardType: ART_BOARD_TYPES[0],

            showSettings: false
        }
    },
    mixins: [wooMixin],
    mounted() {
        this.buildForm();
        if (this.gs_version && this.compareVersions(this.gs_version, '1.3.8') > 0) {
            this.isLabel = true
        }
    },
    methods: {
        buildForm() {
            let sizeUnit = 'in'
            const settings = this.product?.settings
            const btnLabel = this.product.btn_label ?? "Build a Gang Sheet"

            this.sizes = this.getSizesFromVariants();

            if (this.product.variants?.[0]?.unit) {
                sizeUnit = this.$props.product.variants[0].unit
            }

            if (this.product?.type) {
                this.artBoardType = ART_BOARD_TYPES.find(type => type.value == this.product?.type);
            }

            const nameAndNumber = settings?.nameAndNumber ?? {
                enabled: false,
                unit: sizeUnit,
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
                nameAndNumber.unit = sizeUnit
            }

            this.form = useForm({
                id: this.product?.id,
                woo_product_id: this.product?.woo_product_id,
                title: this.product?.title,
                slug: this.product?.slug,
                description: this.product?.description,
                variants: this.product?.variants ?? [],
                unit: sizeUnit,
                deleted_variants: [],
                btnLabel: btnLabel,
                art_board_type: this.product?.type ?? 1,
                disableUploadingImage: settings?.disableUploadingImage ?? false,
                printFileName: settings?.printFileName ?? false,
                printFileNamePosition: settings?.printFileNamePosition ?? 'top',
                printFileNameHeight: settings?.printFileNameHeight ?? 1,
                nameAndNumber: nameAndNumber,
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
        compareVersions(v1, v2) {
            const v1Parts = v1.split('.').map(Number);
            const v2Parts = v2.split('.').map(Number);

            for (let i = 0; i < Math.max(v1Parts.length, v2Parts.length); i++) {
                const v1Part = v1Parts[i] || 0;
                const v2Part = v2Parts[i] || 0;

                if (v1Part > v2Part) return 1;
                if (v1Part < v2Part) return -1;
            }

            return 0;
        },
        getSizesFromVariants() {
            const variants = this.product.variants

            if (variants.length === 0) {
                return [
                    {
                        title: '',
                        width: '',
                        height: '',
                        unit: this.form.unit,
                        variants: [
                            {
                                id: '',
                                title: '',
                                price: '',
                                maxAllowedFileCount: -1,
                            }
                        ]
                    }
                ]
            }

            return variants.reduce((group, variant) => {
                group.push({
                    title: variant.title || variant.label || '',
                    width: variant.width,
                    height: variant.height,
                    unit: variant.unit,
                    variants: [
                        {
                            ...variant,
                            title: variant.title || '',
                            maxAllowedFileCount: variant.maxAllowedFileCount ?? -1,
                        }
                    ]
                })

                return group
            }, [])
        },
        removeVariant(index) {
            if (this.form.variants[index]?.id) {
                this.form.deleted_variants.push(this.form.variants[index].id)
            }
            this.sizes.splice(index, 1)
        },
        addSize(index) {
            const newSize = {
                title: '',
                width: '',
                height: '',
                unit: this.form.unit,
                variants: [
                    {
                        id: '',
                        title: '',
                        price: '',
                        maxAllowedFileCount: -1,
                    }
                ]
            }

            if (typeof index === 'number') {
                this.sizes.splice(index + 1, 0, newSize)
            } else {
                this.sizes.push(newSize)
            }
        },
        getRollingGangSheetVariants() {
            const printerWidth = this.form.printerWidth;
            const maxHeight = this.form.maxHeight;

            const variants = []
            if (this.form.pricing.type === 'flat') {
                const area = printerWidth * maxHeight;
                const price = this.form.pricing.price * area;
                const title = `${printerWidth}${this.form.unit} x ~${maxHeight}${this.form.unit}`

                variants.push({
                    title: title,
                    price: price,
                    width: printerWidth,
                    height: maxHeight,
                    unit: this.form.unit,
                    maxAllowedFileCount: -1,
                });
            } else if (this.form.pricing.type === 'tiered') {
                this.form.pricing.price = this.form.pricing.prices[0].price;
                this.form.pricing.prices.forEach(priceOption => {
                    const area = printerWidth * priceOption.height;
                    const price = priceOption.price * area;
                    const compareAtPrice = this.form.pricing.price * area;
                    const title = `${printerWidth}${this.form.unit} x ~${priceOption.height}${this.form.unit}`

                    variants.push({
                        title: title,
                        compareAtPrice: compareAtPrice,
                        price: price,
                        width: printerWidth,
                        height: priceOption.height,
                        unit: this.form.unit,
                        maxAllowedFileCount: -1,
                    });
                });
            }

            return variants;
        },
        getGangSheetVariants() {
            return this.sizes.reduce((variants, size) => {
                for (const variant of size.variants) {
                    const title = `${size.width}${size.unit} x ${size.height}${size.unit}`

                    variants.push({
                        ...variant,
                        title: size.title ?? title,
                        width: size.width,
                        height: size.height,
                        unit: size.unit,
                    })
                }

                return variants
            }, [])
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
        handleDiscountChange() {
            for (let i = 1; i < this.form.pricing.prices.length; i++) {
                if (this.form.pricing.prices[i].discount) {
                    this.form.pricing.prices[i].price = Number(((this.form.pricing.prices[0].price * (100 - this.form.pricing.prices[i].discount)) / 100).toFixed(5))
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
        async handleSubmit(e) {
            e.preventDefault();
            this.submitting = true;

            try {
                let variants = this.sizes;

                if (this.artBoardType.value === 6) {
                    this.form.deleted_variants = []
                    this.product.variants.forEach(variant => {
                        if (variant.id) {
                            this.form.deleted_variants.push(variant.id)
                        }
                    })
                    variants = this.getRollingGangSheetVariants();
                } else {
                    variants = this.getGangSheetVariants();
                }

                this.form.art_board_type = this.artBoardType.value
                this.form.variants = variants

                const response = await updateProduct(this.form)

                if (!response.data.success) {
                    throw new Error(response.data.error);
                } else {
                    window.Toast.success({ message: 'Successfully updated.' });
                }

            } catch (error) {
                window.Toast.error({ message: error.message });
            } finally {
                this.submitting = false;
            }
        },

        async handleProductCache() {
            const response = await cacheProduct(this.product.id);
            if (!response.data.success) {
                throw new Error(response.data.error);
            }
            this.product.variants = response.data.product.variants;
        },

        handleInput(value, size) {
            if (value < 2) {
                size.maxAllowedFileCount = value
            }

            if (value === 2 && size.maxAllowedFileCount < 2) {
                size.maxAllowedFileCount = 2
            }
        },
        handleSizeInput(e, size) {
            if (e.target.value !== '') {
                if (Number(e.target.value) <= 0) {
                    size.maxAllowedFileCount = -1
                }
            }

            if (Number(e.target.value) > 1) {
                size.maxAllowedFileCount = Number(e.target.value)
            }
        },
        getTitle(size) {
            if (size.title) {
                return size.title
            }

            if (size.width && size.height) {
                return `${size.width}${size.unit} x ${size.height}${size.unit}`
            }

            return '';
        },

        handleToggleLabel(e) {
            if (e.target.checked) {
                this.form.btnLabel = this.product?.btnLabel || 'Build a Gang Sheet'
            } else {
                this.form.btnLabel = null
            }
        },
        sizeMax(unit) {
            if (unit === 'in') {
                return 360
            } else if (unit === 'mm') {
                return 10000
            } else if (unit === 'cm') {
                return 1000
            }

            return 360
        },
    }
})
</script>

<template>
    <form @submit="handleSubmit" class="mx-auto space-y-5 p-5 " :class="{'pointer-events-none': submitting}">
        <div class="grid grid-cols-6 gap-2">
            <div class="col-span-4">
                <div class="shadow-md p-4 rounded-xl border">
                    <div v-if="artBoardType.value !== 6">
                        <div class="flex justify-between">
                            <h3 class="text-lg font-medium mb-2">Size Variants</h3>
                            <div class="space-x-2 mt-2 flex justify-end">
                                <button type="button" class="btn-primary-outline" @click="addSize">+Add Size</button>
                            </div>
                        </div>
                        <div class=" ">
                            <table class="w-full text-center table-auto text-sm">
                                <thead>
                                <tr>
                                    <td class="p-1 w-64 text-left">Title</td>
                                    <td class="p-1 w-32">Width</td>
                                    <td class="p-1 w-32">Height</td>
                                    <td class="p-1 w-32">Price</td>
                                    <td class="p-1 w-24">Unit</td>
                                    <td class="p-1 w-32">
                                        Max Allowed Files
                                    </td>
                                    <td class="p-1 w-24">Action</td>
                                </tr>
                                </thead>
                                <template v-for="(size, index) in sizes">
                                        <tr v-for="(variant, vIndex) in size.variants">
                                            <td class="w-56">
                                                <input type="text" :value="getTitle(size)" @input="size.title = $event.target.value" required class="inp-default"/>
                                            </td>
                                            <td class="p-1">
                                                <input type="number" v-model="size.width" step="0.01" min="0" :max="sizeMax(size.unit)" required class="inp-default"/>
                                            </td>
                                            <td class="p-1">
                                                <input type="number" v-model="size.height" step="0.01" min="0" :max="sizeMax(size.unit)" required class="inp-default"/>
                                            </td>
                                            <td class="p-1">
                                                <input type="number" v-model="variant.price" step="0.01" required class="inp-default"/>
                                            </td>
                                            <td class="p-1">
                                                <Select size="sm" v-model="size.unit" :options="units" required/>
                                            </td>
                                            <td>
                                                <div class="flex justify-center items-center w-full h-full">
                                                    <Select size="sm" :model-value="variant.maxAllowedFileCount" @select="handleInput($event, size)" :options="[-1, 1, 2]" class="w-32">
                                                        <template #selected="{selected}">
                                                            <span v-if="variant.maxAllowedFileCount <= -1">Unlimited</span>
                                                            <span v-else-if="size.maxAllowedFileCount === 1">Single File</span>
                                                            <span v-else>
                                                                <input @click.prevent.stop="" @input="handleSizeInput($event, size)" required :min="2" :step="1" :value="size.maxAllowedFileCount" type="number"
                                                                    class="h-5 w-20 inp-no-style appearance-none"/>
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
                                            <td>
                                                <button type="button" class="text-danger w-8 h-8 rounded-full border border-danger hover:text-white hover:bg-danger" @click="removeVariant(index)">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                            </table>
                        </div>
                    </div>
                    <div v-else>
                        <h3 class="text-lg font-medium mb-2">Size Variants</h3>
                        <hr class="my-2"/>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-900">Printer Width</span>
                            <div class="inp-outline px-3">
                                <input type="number" v-model="form.printerWidth" class="w-28 inp-no-style p-0" step="0.1" min="0" :max="sizeMax(form.unit)" required>
                                <span class="pl-2">{{ form.unit }}</span>
                            </div>
                        </div>
                        <hr class="my-2"/>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-900">Start Height</span>
                            <div class="inp-outline px-3">
                                <input type="number" v-model="form.startHeight" class="w-28 inp-no-style p-0" step="0.1" min="0" :max="sizeMax(form.unit)" required>
                                <span class="pl-2">{{ form.unit }}</span>
                            </div>
                        </div>
                        <hr class="my-2"/>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-900">Min Height</span>
                            <div class="inp-outline px-3">
                                <input type="number" v-model="form.minHeight" class="w-28 inp-no-style p-0" step="0.1" min="1" :max="sizeMax(form.unit)" required>
                                <span class="pl-2">{{ form.unit }}</span>
                            </div>
                        </div>
                        <hr class="my-2"/>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-900">Max Height</span>
                            <div class="inp-outline px-3">
                                <input type="number" v-model="form.maxHeight" class="w-28 inp-no-style p-0" step="0.1" :max="sizeMax(form.unit)" required>
                                <span class="pl-2">{{ form.unit }}</span>
                            </div>
                        </div>
                        <hr class="my-2"/>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <label class="text-sm font-medium text-gray-900">Pricing</label>
                            <selector v-model="form.pricing.type" :options="[{label: 'Flat', value: 'flat'}, {label: 'Tiered', value: 'tiered'}]"/>
                        </div>
                        <div v-if="form.pricing.type === 'flat'" class="flex items-center justify-between mt-2">
                            <span class="text-sm text-gray-900">Price</span>
                            <div class="inp-outline px-3">
                                <input type="number" v-model="form.pricing.price" class="w-24 inp-no-style p-0" step="0.00001" min="0" required>
                                <span class="pl-2">$ per. sqr {{ form.unit }}</span>
                            </div>
                        </div>
                        <div v-else class="flex items-center justify-center mt-2">
                            <table class="w-full text-center text-sm table-fixed">
                                <thead>
                                <tr>
                                    <td>~Max Height ({{ form.unit }})</td>
                                    <td>Max Area (sq.{{ form.unit }})</td>
                                    <td>
                                        Price ($ per.sq {{ form.unit }})
                                    </td>
                                    <td>
                                        Discount (%)
                                    </td>
                                    <td></td>
                                </tr>
                                </thead>
                                <tr v-for="(priceOption, index) in form.pricing.prices">
                                    <td class="py-px px-1">
                                        <input type="number" v-model="priceOption.height" @input="handleHeightChange" step="0.01" min="0"
                                            required class="py-1 px-2 w-full"/>
                                    </td>
                                    <td class="py-px px-1">
                                        <input type="number" v-model="priceOption.area" required step="0.01" @input="handleAreaChange"
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
                <div class="mt-2 shadow-md p-4 rounded-xl border">
                    <div class="flex justify-between cursor-pointer" @click="showSettings = !showSettings">
                        <h3 class="text-lg font-medium mb-2">Product Settings</h3>
                        <chevron-down-icon v-if="showSettings" class="w-4 h-4"/>
                        <chevron-right-icon v-else class="w-4 h-4"/>
                    </div>
                    <div v-if="showSettings">
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
                                        class="mt-1 inp-outline">
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
                                        class="mt-1 inp-outline">
                                        <input type="number" v-model="form.nameAndNumber.size.name.sm" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                        <span class="pr-2">{{form.nameAndNumber.unit}}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-semibold text-xs text-gray-900">Name Height Large</span>
                                    <div
                                        class="mt-1 inp-outline">
                                        <input type="number" v-model="form.nameAndNumber.size.name.lg" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                        <span class="pr-2">{{form.nameAndNumber.unit}}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="font-semibold text-xs text-gray-900">Number Height Small</span>
                                    <div
                                        class="mt-1 inp-outline">
                                        <input type="number" v-model="form.nameAndNumber.size.number.sm" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                        <span class="pr-2">{{form.nameAndNumber.unit}}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-semibold text-xs text-gray-900">Number Height Large</span>
                                    <div
                                        class="mt-1 inp-outline">
                                        <input type="number" v-model="form.nameAndNumber.size.number.lg" class="w-28 inp-no-style py-1" step="0.1" min="0">
                                        <span class="pr-2">{{form.nameAndNumber.unit}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-2 space-y-4">
                <div class="shadow-md rounded-xl border p-4 space-y-4">

                    <div class="space-y-2 py-2">
                        <span class="text-sm font-medium">Title</span>
                        <input type="text" v-model="product.title" required class="inp-default"/>
                    </div>

                    <div class="space-y-2 py-2">
                        <span class="text-sm font-medium">Art Board Type</span>
                        <Select v-model="artBoardType" :options="artBoardTypes" required>
                            <template #selected="{selected}">
                                <span class="py-0.5 text-sm">{{ selected.label }}</span>
                            </template>
                            <template #option="{option}">
                                <span class="text-sm">{{ option.label }}</span>
                            </template>
                        </Select>
                    </div>

                    <div v-if="artBoardType.value === 6" class="space-y-2 py-2">
                        <span class="text-sm font-medium">Size Unit</span>
                        <Select v-model="form.unit" :options="units" required/>
                    </div>

                    <div v-if="isLabel" class="bg-white space-y-4">
                        <div>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" class="checkbox-primary" :checked="form.btnLabel !== null"
                                       @change="handleToggleLabel"/>
                                <span class="text-sm font-medium">Use Custom Button Label</span>
                            </label>
                            <input v-if="form.btnLabel !== null" type="text" v-model="form.btnLabel" required
                                   class="inp-primary w-full mt-2"/>
                        </div>
                    </div>

                    <div class="flex justify-end border-t py-2 space-x-5">
                        <button type="button" @click="$emit('back')" class="btn-outline">
                            <i class="mdi mdi-arrow-left"></i> Back
                        </button>
                        <a v-if="product" :href="`/wp-admin/post.php?post=${product.woo_product_id}&action=edit`" target="_top" class="btn-primary-outline">
                            Update More
                        </a>
                        <button type="submit" class="btn-primary" :disabled="submitting">
                            <spinner v-if="submitting" class="mr-2"/>
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<style scoped>

</style>
