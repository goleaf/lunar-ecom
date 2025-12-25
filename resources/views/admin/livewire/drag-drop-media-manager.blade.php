<div>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Product Images
            </label>
            
            <!-- Drop Zone -->
            <div x-data="{ 
                isDragging: false,
                handleDrop(e) {
                    e.preventDefault();
                    this.isDragging = false;
                    const files = Array.from(e.dataTransfer.files);
                    @this.upload('uploadedFiles', files);
                },
                handleDragOver(e) {
                    e.preventDefault();
                    this.isDragging = true;
                },
                handleDragLeave() {
                    this.isDragging = false;
                }
            }"
            @drop="handleDrop"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            :class="{ 'border-blue-500 bg-blue-50': isDragging }"
            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                <input type="file" wire:model="uploadedFiles" multiple accept="image/*" 
                       class="hidden" id="file-upload">
                <label for="file-upload" class="cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">
                        <span class="font-medium">Drag and drop</span> images here, or click to select
                    </p>
                </label>
            </div>
        </div>

        <!-- Media Grid -->
        <div class="grid grid-cols-4 gap-4">
            @foreach($media as $item)
                <div class="relative group">
                    <img src="{{ $item['url'] }}" alt="{{ $item['name'] }}" 
                         class="w-full h-32 object-cover rounded-lg">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-opacity rounded-lg flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 space-x-2">
                            @if($primaryMediaId !== $item['id'])
                                <button wire:click="setPrimary({{ $item['id'] }})" 
                                        class="px-3 py-1 bg-blue-600 text-white rounded text-sm">
                                    Set Primary
                                </button>
                            @endif
                            <button wire:click="deleteMedia({{ $item['id'] }})" 
                                    class="px-3 py-1 bg-red-600 text-white rounded text-sm">
                                Delete
                            </button>
                        </div>
                    </div>
                    @if($primaryMediaId === $item['id'])
                        <div class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded text-xs">
                            Primary
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

