{{-- Drag and Drop Image Uploader Component --}}
<div x-data="imageUploader({{ $modelId }}, '{{ $modelType }}', '{{ $collectionName ?? 'images' }}')" class="w-full">
    <div 
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
        :class="{ 'border-blue-500 bg-blue-50': isDragging }"
        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center transition-colors"
    >
        <input 
            type="file" 
            @change="handleFileSelect($event)"
            multiple
            accept="image/*"
            class="hidden"
            :ref="fileInput"
            id="file-input-{{ $modelId }}"
        >
        
        <div x-show="!uploading">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
                <label for="file-input-{{ $modelId }}" class="cursor-pointer">
                    <span class="mt-2 block text-sm font-medium text-gray-900">
                        Drop images here or click to upload
                    </span>
                    <span class="mt-1 block text-xs text-gray-500">
                        PNG, JPG, GIF, WEBP up to 10MB
                    </span>
                </label>
            </div>
        </div>

        <div x-show="uploading" class="flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-3 text-gray-700">Uploading...</span>
        </div>
    </div>

    {{-- Uploaded Images Preview --}}
    <div x-show="uploadedImages.length > 0" class="mt-6">
        <h3 class="text-lg font-semibold mb-4">Uploaded Images</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <template x-for="(image, index) in uploadedImages" :key="image.id">
                <div class="relative group">
                    <img :src="image.thumb_url || image.url" :alt="image.name" class="w-full h-32 object-cover rounded">
                    <button 
                        @click="deleteImage(image.id, index)"
                        class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                        type="button"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Error Messages --}}
    <div x-show="error" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded" x-text="error"></div>

    {{-- Success Message --}}
    <div x-show="success" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded" x-text="success"></div>
</div>

