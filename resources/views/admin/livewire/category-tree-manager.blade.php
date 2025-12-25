<div>
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Category Tree</h3>
        <button 
            wire:click="$set('showForm', true); $set('editingCategory', null); resetForm()"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
            Add Category
        </button>
    </div>

    @if($showForm)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <form wire:submit.prevent="{{ $editingCategory ? 'updateCategory' : 'createCategory' }}">
                {{ $this->form }}
                <div class="flex gap-2 mt-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        {{ $editingCategory ? 'Update' : 'Create' }}
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('showForm', false); resetForm()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow">
        <div id="category-tree" class="p-4">
            @if(count($categories) > 0)
                <x-admin.category-tree-item 
                    :categories="$categories" 
                    :level="0"
                />
            @else
                <p class="text-gray-500 text-center py-8">No categories found. Create your first category to get started.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize drag-and-drop for category tree
        const treeElement = document.getElementById('category-tree');
        if (treeElement) {
            new Sortable(treeElement, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    // Handle reordering
                    @this.call('reorderCategories', evt.oldIndex, evt.newIndex, evt.item.dataset.categoryId);
                }
            });
        }
    });
</script>
@endpush

