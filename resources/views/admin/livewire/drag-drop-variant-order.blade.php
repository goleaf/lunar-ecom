<div>
    <div class="mb-4">
        <h3 class="text-lg font-semibold">Variant Ordering</h3>
        <p class="text-sm text-gray-600">Drag and drop to reorder variants</p>
    </div>

    <div 
        x-data="{
            items: @js($variants),
            draggedItem: null,
            draggedOver: null,
            init() {
                this.$watch('items', (value) => {
                    @this.updateOrder(value.map(item => item.id));
                });
            }
        }"
        class="space-y-2"
    >
        <template x-for="(item, index) in items" :key="item.id">
            <div 
                draggable="true"
                @dragstart="draggedItem = index"
                @dragover.prevent="draggedOver = index"
                @dragleave="draggedOver = null"
                @drop.prevent="
                    if (draggedItem !== null && draggedOver !== null) {
                        const temp = items[draggedItem];
                        items[draggedItem] = items[draggedOver];
                        items[draggedOver] = temp;
                        draggedItem = null;
                        draggedOver = null;
                    }
                "
                @dragend="draggedItem = null; draggedOver = null"
                :class="{
                    'bg-blue-50 border-blue-300': draggedOver === index,
                    'opacity-50': draggedItem === index
                }"
                class="flex items-center space-x-4 p-4 border rounded-lg cursor-move hover:bg-gray-50"
            >
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                </svg>
                
                <div class="flex-1">
                    <div class="font-medium" x-text="item.sku"></div>
                    <div class="text-sm text-gray-500" x-text="item.name"></div>
                    <div class="text-xs text-gray-400" x-text="item.options"></div>
                </div>
                
                <div class="text-sm text-gray-500">
                    Position: <span x-text="index + 1"></span>
                </div>
            </div>
        </template>
    </div>
</div>


