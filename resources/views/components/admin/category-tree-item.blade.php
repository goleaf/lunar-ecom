@props(['categories', 'level' => 0])

@foreach($categories as $category)
    <div 
        class="category-item flex items-center gap-2 p-2 hover:bg-gray-50 rounded" 
        data-category-id="{{ $category['id'] }}"
        style="padding-left: {{ ($level * 24) + 8 }}px;"
    >
        <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600">☰</span>
        <span class="flex-1">{{ $category['name'] ?? 'Unnamed' }}</span>
        <span class="text-sm text-gray-500">{{ $category['product_count'] ?? 0 }} products</span>
        <div class="flex gap-2">
            <button 
                wire:click="editCategory({{ $category['id'] }})"
                class="px-2 py-1 text-sm text-blue-600 hover:text-blue-800"
            >
                Edit
            </button>
            <button 
                wire:click="deleteCategory({{ $category['id'] }})"
                wire:confirm="Are you sure you want to delete this category?"
                class="px-2 py-1 text-sm text-red-600 hover:text-red-800"
            >
                Delete
            </button>
        </div>
    </div>
    
    @if(isset($category['children']) && count($category['children']) > 0)
        <x-admin.category-tree-item :categories="$category['children']" :level="$level + 1" />
    @endif
@endforeach

