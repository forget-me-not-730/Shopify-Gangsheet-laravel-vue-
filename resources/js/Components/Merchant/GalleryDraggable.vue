<script>
import {defineComponent} from 'vue'
import RerenderMixin from '@/Mixins/rerenderMixin.js'

export default defineComponent({
    name: 'GalleryDraggable',
    props: {
        modelValue: {
            type: Array,
            required: true
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    mixins: [RerenderMixin],
    computed: {
        items: {
            get() {
                return this.modelValue
            },
            set(value) {
                this.$emit('update:modelValue', value)
            }
        }
    },
    watch: {
        'items.length': {
            handler() {
                this.reinitializeDraggable()
            }
        }
    },
    mounted() {
        this.initializeDraggable()
    },
    methods: {
        reinitializeDraggable() {
            this.forceRerender().then(() => {
                this.$nextTick(() => {
                    this.initializeDraggable()
                })
            })
        },
        initializeDraggable() {
            let clientX = 0
            let clientY = 0
            let targetItem = null

            const draggableItems = Array.from(this.$el.children).splice(0, this.items.length)

            function resetItem(item) {
                item.style.setProperty('--offset-y', '0px')
                item.style.borderBottom = 'none'
                item.style.borderTop = 'none'
                item.classList.remove('draggable-item')
                item.classList.remove('draggable-target')
            }

            const handleMouseMove = (event) => {
                const draggingItem = this.$el.querySelector('.draggable-item')
                if (draggingItem) {
                    const offsetX = event.clientX - clientX
                    const offsetY = event.clientY - clientY

                    if (draggingItem.dataset.disableDraggable === 'true') {
                        return
                    }

                    if (Math.abs(offsetX) < 8 && Math.abs(offsetY) < 8) {
                        return
                    }

                    window.isCategoryDragging = true

                    draggingItem.onclick = (e) => {
                        e.preventDefault()
                        e.stopImmediatePropagation()
                    }

                    draggingItem.style.setProperty('--offset-y', `${offsetY}px`)

                    const draggingItemRect = draggingItem.getBoundingClientRect()
                    draggingItemRect.centerY = draggingItemRect.y + draggingItemRect.height / 2

                    const oldTargetItem = targetItem

                    let closestDistance = 30
                    let closestItem = null
                    for (const item of draggableItems) {
                        if (item === draggingItem) {
                            continue
                        }

                        const itemBoundingRect = item.getBoundingClientRect()
                        itemBoundingRect.centerY = itemBoundingRect.y + itemBoundingRect.height / 2

                        const distance = Math.abs(draggingItemRect.centerY - itemBoundingRect.centerY)
                        if (distance < closestDistance) {
                            closestItem = item
                        }
                    }

                    if (closestItem && closestItem !== draggingItem) {
                        if (closestItem.dataset.disableDraggable !== 'true') {
                            targetItem = closestItem
                            if (!targetItem.classList.contains('draggable-target')) {
                                targetItem.classList.add('draggable-target')
                            }
                        }
                    } else {
                        targetItem = null
                    }

                    if (targetItem) {
                        if (offsetY > 0) {
                            targetItem.style.borderBottom = '2px solid #007a5c'
                        } else {
                            targetItem.style.borderTop = '2px solid #007a5c'
                        }
                    }

                    if (oldTargetItem && oldTargetItem !== targetItem) {
                        resetItem(oldTargetItem)
                    }
                }
            }

            const handleMouseUp = () => {
                const draggingItem = this.$el.querySelector('.draggable-item')

                if (draggingItem && targetItem) {
                    const draggingIndex = draggingItem.dataIndex
                    const targetIndex = targetItem.dataIndex

                    if (draggingIndex !== targetIndex) {
                        const item = this.items.splice(draggingIndex, 1)[0];
                        this.items.splice(targetIndex, 0, item);
                        this.items = [...this.items]
                        this.$emit('exchange', {
                            fromIndex: draggingIndex,
                            toIndex: targetIndex,
                        })
                    }
                }

                for (const item of draggableItems) {
                    resetItem(item)
                }

                targetItem = null
                document.removeEventListener('mousemove', handleMouseMove)

                setTimeout(() => {
                    window.isCategoryDragging = false
                }, 500);
            }

            for (let i = 0; i < draggableItems.length; i++) {
                const draggableItem = draggableItems[i]
                draggableItem.dataIndex = i

                resetItem(draggableItem)

                const $this = this
                draggableItem.onmousedown = function (e) {
                    if ($this.disabled) {
                        return
                    }

                    if (draggableItem.dataset.disableDraggable === 'true') {
                        return
                    }

                    draggableItem.classList.add('draggable-item')
                    clientX = e.clientX
                    clientY = e.clientY
                    document.addEventListener('mousemove', handleMouseMove)
                }

                draggableItem.ondragstart = function (e) {
                    e.preventDefault()
                }
            }

            document.addEventListener('mouseup', handleMouseUp)
        }
    }
})
</script>

<template>
    <div class="flex flex-wrap w-full draggable relative">
        <template v-if="renderComponent">
            <slot v-for="(item, index) in items" name="item" :item="item" :index="index"/>
        </template>
        <slot name="add-item"/>
    </div>
</template>

<style lang="scss">
.draggable-item {
    transform-origin: center;
    cursor: grabbing;
    position: relative;
    background-color: white;
    z-index: 999;
}

.draggable-item,
.draggable-target {
    --offset-y: 0px;
    transform: translate(0px, var(--offset-y));
}
</style>
