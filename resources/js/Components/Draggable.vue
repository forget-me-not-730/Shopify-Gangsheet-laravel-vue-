<script>
import {defineComponent} from 'vue'
import RerenderMixin from '@/Mixins/rerenderMixin.js'

export default defineComponent({
    name: 'Draggable',
    props: {
        modelValue: Array,
        required: true,
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
                this.forceRerender().then(() => {
                    this.$nextTick(() => {
                        this.initializeDraggable()
                    })
                })
            }
        }
    },
    mounted() {
        this.initializeDraggable()
    },
    methods: {
        initializeDraggable() {
            let clientX = 0
            let clientY = 0
            let targetItem = null

            const draggableItems = Array.from(this.$el.children).splice(0, this.items.length)
            const draggableOverlay = this.$el.querySelector('.draggable-overlay')

            function resetItem(item) {
                item.style.setProperty('--offset-x', '0px')
                item.style.setProperty('--offset-y', '0px')
                item.style.setProperty('--scale-x', '1')
                item.style.setProperty('--scale-y', '1')
                item.classList.remove('draggable-item')
                item.classList.remove('draggable-target')
            }

            const handleMouseMove = (event) => {
                const draggingItem = this.$el.querySelector('.draggable-item')
                if (draggingItem) {
                    const offsetX = event.clientX - clientX
                    const offsetY = event.clientY - clientY
                    draggingItem.style.setProperty('--offset-x', `${offsetX}px`)
                    draggingItem.style.setProperty('--offset-y', `${offsetY}px`)

                    const draggingItemRect = draggingItem.getBoundingClientRect()
                    draggingItemRect.centerX = draggingItemRect.x + draggingItemRect.width / 2
                    draggingItemRect.centerY = draggingItemRect.y + draggingItemRect.height / 2

                    const oldTargetItem = targetItem

                    let closestDistance = Number.MAX_VALUE
                    let closestItem = null
                    for (const item of draggableItems) {
                        const distance = Math.sqrt(
                            Math.pow(draggingItemRect.centerX - item.boundingRect.centerX, 2) +
                            Math.pow(draggingItemRect.centerY - item.boundingRect.centerY, 2)
                        )

                        if (distance < closestDistance) {
                            closestDistance = distance
                            closestItem = item
                        }
                    }

                    if (closestItem !== draggingItem) {
                        targetItem = closestItem
                        if (!targetItem.classList.contains('draggable-target')) {
                            targetItem.classList.add('draggable-target')
                        }
                    }

                    let overlayRect = draggingItem.boundingRect
                    if (targetItem) {
                        targetItem.style.setProperty('--offset-x', `${draggingItem.boundingRect.centerX - targetItem.boundingRect.centerX}px`)
                        targetItem.style.setProperty('--offset-y', `${draggingItem.boundingRect.centerY - targetItem.boundingRect.centerY}px`)

                        targetItem.style.setProperty('--scale-x', draggingItem.boundingRect.width / targetItem.boundingRect.width)
                        targetItem.style.setProperty('--scale-y', draggingItem.boundingRect.height / targetItem.boundingRect.height)

                        draggingItem.style.setProperty('--scale-x', targetItem.boundingRect.width / draggingItem.boundingRect.width)
                        draggingItem.style.setProperty('--scale-y', targetItem.boundingRect.height / draggingItem.boundingRect.height)

                        overlayRect = targetItem.boundingRect
                    }

                    draggableOverlay.style.width = overlayRect.width + 'px'
                    draggableOverlay.style.height = overlayRect.height + 'px'
                    draggableOverlay.style.left = overlayRect.x + 'px'
                    draggableOverlay.style.top = overlayRect.y + 'px'
                    draggableOverlay.style.display = 'block'

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
                        const tempItem = this.items[draggingIndex]
                        this.items[draggingIndex] = this.items[targetIndex]
                        this.items[targetIndex] = tempItem
                        this.items = [...this.items]
                    }
                }

                for (const item of draggableItems) {
                    resetItem(item)
                }

                draggableOverlay.style.display = 'none'
                targetItem = null
                document.removeEventListener('mousemove', handleMouseMove)
            }

            for (let i = 0; i < draggableItems.length; i++) {
                const draggableItem = draggableItems[i]
                draggableItem.dataIndex = i

                const boundingRect = draggableItem.getBoundingClientRect()
                boundingRect.centerX = boundingRect.x + boundingRect.width / 2
                boundingRect.centerY = boundingRect.y + boundingRect.height / 2
                draggableItem.boundingRect = boundingRect

                resetItem(draggableItem)

                draggableItem.onmousedown = function (e) {
                    this.classList.add('draggable-item')
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
        <div class="draggable-overlay"></div>
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
    --offset-x: 0px;
    --offset-y: 0px;
    --scale-x: 1;
    --scale-y: 1;
    transform: translate(var(--offset-x), var(--offset-y)) scale(var(--scale-x), var(--scale-y));
}

.draggable-target {
    transition: all 0.2s;
}

.draggable-overlay {
    position: fixed;
    display: none;
    transition: all 0.3s;
    background-color: #33333312;
}
</style>
