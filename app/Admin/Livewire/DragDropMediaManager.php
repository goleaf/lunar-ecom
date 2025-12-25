<?php

namespace App\Admin\Livewire;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Drag & Drop Media Manager for products.
 */
class DragDropMediaManager extends Component
{
    use WithFileUploads;

    public Product $product;
    public array $media = [];
    public $uploadedFiles = [];
    public ?int $primaryMediaId = null;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadMedia();
    }

    public function loadMedia(): void
    {
        $this->media = $this->product->getMedia('images')
            ->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'name' => $media->name,
                    'size' => $media->size,
                    'is_primary' => false, // Would need to track primary image
                ];
            })
            ->toArray();
    }

    public function updatedUploadedFiles(): void
    {
        $this->validate([
            'uploadedFiles.*' => 'image|max:10240', // 10MB max
        ]);

        foreach ($this->uploadedFiles as $file) {
            $media = $this->product->addMediaFromString($file->get())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('images');
            
            $this->media[] = [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
                'size' => $media->size,
                'is_primary' => false,
            ];
        }

        $this->uploadedFiles = [];
        $this->loadMedia();
    }

    public function setPrimary(int $mediaId): void
    {
        $this->primaryMediaId = $mediaId;
        // Update primary image logic
    }

    public function deleteMedia(int $mediaId): void
    {
        $media = Media::find($mediaId);
        if ($media && $media->model_id === $this->product->id) {
            $media->delete();
            $this->loadMedia();
        }
    }

    public function reorderMedia(array $order): void
    {
        // Update media order
        foreach ($order as $index => $mediaId) {
            Media::where('id', $mediaId)
                ->where('model_id', $this->product->id)
                ->update(['order_column' => $index]);
        }
        
        $this->loadMedia();
    }

    public function render()
    {
        return view('admin.livewire.drag-drop-media-manager');
    }
}

