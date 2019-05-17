<script>
import {defineComponent} from 'vue'
import CloseIcon from "@/Builder/Icons/CloseIcon.vue";
import GsSelect from "@/Builder/Components/GsSelect.vue";
import CloseCircleIcon from "@/Builder/Icons/CloseCircleIcon.vue";
import {Sketch} from '@lk77/vue3-color'
import gangSheetMixin from "@/Builder/Mixins/gangSheetMixin";
import {convertDimension, getPixelSize} from '@/Builder/Utils/helpers'
import Spinner from '@/Components/Spinner.vue'
import ChevronRightIcon from "@/Builder/Icons/ChevronRightIcon.vue";
import ChevronDownIcon from "@/Builder/Icons/ChevronDownIcon.vue";
import {ART_BOARD_TYPES} from "@/Builder/Utils/constants";
import eventBus from "@/Builder/Utils/eventBus";

export default defineComponent({
    name: "NameAndNumbers",
    components: {ChevronDownIcon, ChevronRightIcon, Sketch, Spinner, CloseCircleIcon, GsSelect, CloseIcon},
    mixins: [gangSheetMixin],
    computed: {
        sizeOptions() {
            return {
                name: [
                    {
                        label: `Small (${this.builderSettings.nameAndNumber.size.name.sm} ${this.nameAndNumberUnit})`,
                        value: this.builderSettings.nameAndNumber.size.name.sm,
                    },
                    {
                        label: `Large (${this.builderSettings.nameAndNumber.size.name.lg} ${this.nameAndNumberUnit})`,
                        value: this.builderSettings.nameAndNumber.size.name.lg,
                    }
                ],
                number: [
                    {
                        label: `Small (${this.builderSettings.nameAndNumber.size.number.sm} ${this.nameAndNumberUnit})`,
                        value: this.builderSettings.nameAndNumber.size.number.sm,
                    },
                    {
                        label: `Large (${this.builderSettings.nameAndNumber.size.number.lg} ${this.nameAndNumberUnit})`,
                        value: this.builderSettings.nameAndNumber.size.number.lg,
                    }
                ]
            }
        },
        fontOptions() {
            return this.nameAndNumberFonts.map((font) => ({
                label: font.name,
                value: font.name
            }))
        },
        nameAndNumberUnit() {
            return this.builderSettings.nameAndNumber.unit ?? "inch"
        }
    },
    data() {
        return {
            loading: false,
            nameAndNumbers: [],
            activeNIndex: null,
            objects: []
        }
    },
    mounted() {
        if (window._gangSheetCanvasEditor) {
            this.getNameAndNumbers()
        } else {
            this.$emit('close')
        }

        eventBus.$on(eventBus.CANVAS_INITIALIZED, () => {
            this.getNameAndNumbers()
        })
    },
    methods: {
        addNewNameNumbers(type) {
            this.nameAndNumbers.push({
                type: type,
                setting: {
                    size: this.sizeOptions[type][0],
                    font: this.fontOptions[0],
                    color: '#000000',
                },
                items: [
                    {
                        id: Date.now(),
                        value: '',
                        qty: 1,
                    }
                ]
            })

            this.activeNIndex = this.nameAndNumbers.length - 1
        },
        addNameNumber(nIndex) {
            this.nameAndNumbers[nIndex].items.push({
                id: Date.now(),
                value: '',
                qty: 1,
            })

            this.$nextTick(() => {
                const scrollContainer = this.$refs.scrollContainer;
                if (scrollContainer) {
                    scrollContainer.scrollTo(0, scrollContainer.scrollHeight);
                }
            });
        },
        removeNameNumber(nIndex, index) {
            this.nameAndNumbers[nIndex].items.splice(index, 1)
            if (this.nameAndNumbers[nIndex].items.length === 0) {
                this.nameAndNumbers.splice(nIndex, 1)
            }
        },
        handleUpdateColor(e, nIndex) {
            this.nameAndNumbers[nIndex].setting.color = e.hex
        },
        getNameAndNumbers() {
            this.$gsb.updateCanvasData()
            this.nameAndNumbers = []
            const objects = fabric.util.getAutoNestObjectsFromDesigns(this.workingDesigns)

            const findClosestSize = (sizes, value) => {
                return sizes.reduce(function (prev, curr) {
                    return (Math.abs(curr.value - value) < Math.abs(prev.value - value) ? curr : prev);
                }, sizes[0]);
            }

            for (const object of objects) {
                if (object.type === 'n-text') {
                    const type = object.text.match(/\d+/) ? 'number' : 'name'
                    const font = this.fontOptions.find(f => f.value === object.fontFamily)
                    const size = findClosestSize(this.sizeOptions[type], convertDimension(object.height, this.artBoardUnit, this.nameAndNumberUnit))
                    const color = object.fill
                    const value = object.text

                    const nIndex = this.nameAndNumbers.findIndex(n => n.type === type && n.setting.font.value === font.value && n.setting.size.value === size.value && n.setting.color === color)

                    if (nIndex === -1) {
                        this.nameAndNumbers.push({
                            type: type,
                            setting: {
                                size: size,
                                font: font,
                                color: color,
                            },
                            items: [
                                {
                                    id: Date.now(),
                                    value: value,
                                    qty: object.quantity,
                                }
                            ]
                        })
                    } else {
                        const itemIndex = this.nameAndNumbers[nIndex].items.findIndex(i => i.value === value)
                        if (itemIndex === -1) {
                            this.nameAndNumbers[nIndex].items.push({
                                id: Date.now(),
                                value: value,
                                qty: object.quantity,
                            })
                        } else {
                            this.nameAndNumbers[nIndex].items[itemIndex].qty += object.quantity
                        }
                    }
                } else {
                    this.objects.push(object)
                }
            }
        },
        confirm() {
            if (this.loading) {
                return
            }

            this.$gsb.updateCanvasData()

            this.loading = true
            const canvas = _gangSheetCanvasEditor

            const margin = canvas.getMargin()
            const artBoardMargin = canvas.getArtboardMargin()

            const objects = this.objects.slice()

            for (const nameNumber of this.nameAndNumbers) {
                if (nameNumber.type === 'name') {
                    for (const name of nameNumber.items) {

                        if (name.value.trim() === '') {
                            continue
                        }

                        const {width, height} = fabric.util.measureText(canvas.contextContainer, name.value, nameNumber.setting.font.value)
                        const nameHeight = convertDimension(nameNumber.setting.size.value, this.nameAndNumberUnit, 'px')

                        const scaleY = nameHeight / height
                        const scaleX = nameHeight / height

                        objects.push({
                            id: name.value,
                            type: 'n-text',
                            text: name.value,
                            fontFamily: nameNumber.setting.font.value,
                            fontSize: 40,
                            fill: nameNumber.setting.color,
                            scaleX,
                            scaleY,
                            width: width * scaleX / getPixelSize(this.artBoardUnit),
                            height: height * scaleY / getPixelSize(this.artBoardUnit),
                            quantity: name.qty,
                            backgroundColor: null,
                            fontStyle: "normal",
                            fontWeight: "normal",
                            strokeWidth: 1,
                            textAlign: "left",
                            underline: false
                        })
                    }
                } else {
                    for (const number of nameNumber.items) {

                        if (number.value.replace(/[^0-9]/g, '') === '') {
                            continue
                        }

                        const {width, height} = fabric.util.measureText(canvas.contextContainer, String(number.value), nameNumber.setting.font.value)
                        const numberHeight = convertDimension(nameNumber.setting.size.value, this.nameAndNumberUnit, 'px')

                        const scaleY = numberHeight / height
                        const scaleX = numberHeight / height

                        objects.push({
                            id: String(number.value),
                            type: 'n-text',
                            text: number.value.replace(/[^0-9]/g, ''),
                            fontFamily: nameNumber.setting.font.value,
                            fontSize: 40,
                            fill: nameNumber.setting.color,
                            scaleX,
                            scaleY,
                            width: (width * scaleX) / getPixelSize(this.artBoardUnit),
                            height: (height * scaleY) / getPixelSize(this.artBoardUnit),
                            quantity: number.qty,
                            backgroundColor: null,
                            fontStyle: "normal",
                            fontWeight: "normal",
                            strokeWidth: 1,
                            textAlign: "left",
                            underline: false
                        })
                    }
                }
            }

            const rectangles = fabric.util.getAutoNestRectsFromObjects(objects, margin)

            if (this.product?.art_board_type === ART_BOARD_TYPES.ROLLING_GANG_SHEET) {
                axios.post(route('builder.auto-nest'), {
                    rectangles: rectangles,
                    margin: margin,
                    artboardMargin: artBoardMargin,
                    variants: [
                        {
                            id: this.variant.id,
                            width: this.productSettings.printerWidth,
                            height: this.productSettings.maxHeight,
                            unit: this.artBoardUnit
                        }
                    ]
                }).then(async (res) => {
                    if (res.data.success) {
                        const packs = res.data.packs.filter(p => p.bins.length > 0)
                        const endPack = packs[packs.length - 1]
                        await this.$gsb.createDesignsFromBins(endPack.bins, {margin, artBoardMargin, autoTrim: true})
                    } else {
                        this.$gsb.error('Not able to generate the auto build. Please try again.')
                    }
                }).finally(() => {
                    this.loading = false
                    this.$emit('close')
                })
            } else {
                axios.post(route('builder.auto-nest'), {
                    rectangles: rectangles,
                    margin: margin,
                    artboardMargin: artBoardMargin,
                    variants: this.$gsb.getVariantsForAutoNest(true),
                    visibleVariants: this.$gsb.getSameTypeSizeVariants(this.visibleVariants),
                    hiddenVariants: this.$gsb.getSameTypeSizeVariants(this.hiddenVariants),
                }).then(async (res) => {
                    if (res.data.success) {
                        const packs = res.data.packs.filter(p => p.bins.length > 0)
                        const endPack = packs[packs.length - 1]
                        await this.$gsb.createDesignsFromBins(endPack.bins, {margin, artBoardMargin})
                    } else {
                        this.$gsb.error('Not able to generate the auto build. Please try again.')
                    }
                }).finally(() => {
                    this.loading = false
                    this.$emit('close')
                })
            }
        }
    },
})
</script>

<template>
    <div class="relative w-full h-full md:h-[calc(100vh-60px)]">
        <div ref="scrollContainer" class="h-full w-full overflow-y-auto tiny-scroll-bar pb-20">
            <div class="flex items-center justify-between">
                <div class="p-3 text-base font-bold">{{ $t('Name And Numbers') }}</div>

                <div class="cursor-pointer mr-3 md:hidden" @click="$emit('close')">
                    <close-icon/>
                </div>
            </div>

            <hr/>

            <div v-for="(nameNumber, nIndex) in nameAndNumbers">
                <div class="px-2 py-4 text-sm flex justify-between items-center cursor-pointer gs-bg-primary-20" @click="activeNIndex = activeNIndex === nIndex ? null : nIndex">
                    <div class="flex items-center">
                        <span class="uppercase font-semibold">({{ nameNumber.items.length }}) {{ nameNumber.type }}s</span>
                        <div class="ml-1 space-x-1 text-xs flex items-center">
                            <span></span>
                            <div class="h-4 w-4 border border-gray-300" :style="{backgroundColor: nameNumber.setting.color}"></div>
                            <span>{{ nameNumber.setting.size.label }}</span>
                            <span>-</span>
                            <span>{{ nameNumber.setting.font.value }}</span>
                        </div>
                    </div>
                    <div>
                        <chevron-down-icon v-if="activeNIndex === nIndex" size="20"/>
                        <chevron-right-icon v-else size="20"/>
                    </div>
                </div>
                <hr/>
                <div v-if="activeNIndex === nIndex" class="p-2 border-b">
                    <div class="flex flex-col gap-1 text-sm">
                        <div class="grid grid-cols-3 items-center justify-between gap-2">
                            <span>{{ $t('Size') }}</span>
                            <gs-select v-model="nameNumber.setting.size" :options="sizeOptions[nameNumber.type]" class="col-span-2"/>
                        </div>
                        <div class="grid grid-cols-3 items-center justify-between gap-2">
                            <span>{{ $t('Font') }}</span>
                            <gs-select v-model="nameNumber.setting.font" :options="fontOptions" class="col-span-2" :style="{fontFamily: nameNumber.setting.font.value}"/>
                        </div>
                        <div class="grid grid-cols-3 items-start justify-between gap-2">
                            <span class="pt-1">{{ $t('Color') }}</span>
                            <div class="col-span-2">
                                <input class="inp-builder w-full" type="text" v-model="nameNumber.setting.color" placeholder="Select a Color"/>
                                <div class="border border-gray-300 rounded-lg px-2 mt-2">
                                    <Sketch v-model="nameNumber.setting.color" @update:modelValue="handleUpdateColor($event, nIndex)" class="custom-color-picker disable-alpha" :disable-alpha="true"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="grid grid-cols-8 gap-2">
                            <div class="col-span-5">
                                <span class="text-sm capitalize">{{ nameNumber.type }}s</span>
                            </div>
                            <div class="col-span-3">
                                <span class="text-sm">Qty</span>
                            </div>
                        </div>
                        <div v-for="(item, index) in nameNumber.items" :key="item.id" class="grid grid-cols-8 gap-2">
                            <div class="col-span-5">
                                <input type="text" v-model="item.value" class="inp-builder w-full">
                            </div>
                            <div class="col-span-3 flex justify-between items-center">
                                <input type="number" min="0" v-model="item.qty" class="inp-builder w-20 pr-0 pl-1"/>
                                <button @click="removeNameNumber(nIndex, index)">
                                    <close-circle-icon/>
                                </button>
                            </div>
                        </div>
                        <button class="btn-builder h-8" :disabled="loading" @click="addNameNumber(nIndex)">{{ $t('Add Another') }}</button>
                    </div>
                </div>
            </div>

            <div class="flex space-x-4 p-2 mt-5">
                <button class="btn-builder h-8 flex-1" :disabled="loading" @click="addNewNameNumbers('name')">
                    {{ $t('Add Names') }}
                </button>
                <button class="btn-builder h-8 flex-1" :disabled="loading" @click="addNewNameNumbers('number')">
                    {{ $t('Add Numbers') }}
                </button>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 z-10 border-t border-gray-300 bg-builder w-full h-16 px-4 flex justify-center items-center">
            <button :disabled="loading" class="btn-secondary w-full" @click="confirm()">
                <spinner v-if="loading" class="mr-2"/>
                {{ $t('Confirm') }}
            </button>
        </div>
    </div>
</template>
