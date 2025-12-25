<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Lunar\Media\MediaHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Product;

/**
 * Controller for handling media uploads and management.
 */
class MediaController extends Controller
{
    /**
     * Upload images for a product.
     * 
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function uploadProductImages(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $this->authorize('update', $product);
        
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $files = $request->file('images');
        $mediaItems = MediaHelper::addMultipleImages($product, $files, 'images');

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'media' => $mediaItems->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb_url' => $media->getUrl('thumb'),
                    'name' => $media->name,
                ];
            }),
        ]);
    }

    /**
     * Upload images for a collection.
     * 
     * @param Request $request
     * @param int $collectionId
     * @return JsonResponse
     */
    public function uploadCollectionImages(Request $request, int $collectionId): JsonResponse
    {
        $collection = Collection::findOrFail($collectionId);
        $this->authorize('update', $collection);
        
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $files = $request->file('images');
        $mediaItems = MediaHelper::addMultipleImages($collection, $files, 'images');

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'media' => $mediaItems->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb_url' => $media->getUrl('thumb'),
                    'name' => $media->name,
                ];
            }),
        ]);
    }

    /**
     * Upload logo for a brand.
     * 
     * @param Request $request
     * @param int $brandId
     * @return JsonResponse
     */
    public function uploadBrandLogo(Request $request, int $brandId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $brand = Brand::findOrFail($brandId);

        // Brand doesn't have a policy yet, but we require staff/admin access
        if (!auth()->check() || (!auth()->user() instanceof \Lunar\Admin\Models\Staff)) {
            abort(403, 'Unauthorized');
        }

        // Delete existing logo if any
        $brand->clearMediaCollection('logo');

        $file = $request->file('logo');
        $media = $brand->addMedia($file)
            ->toMediaCollection('logo');

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'media' => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'name' => $media->name,
            ],
        ]);
    }

    /**
     * Delete a media item.
     * 
     * @param Request $request
     * @param string $modelType (product, collection, brand)
     * @param int $modelId
     * @param int $mediaId
     * @return JsonResponse
     */
    public function deleteMedia(Request $request, string $modelType, int $modelId, int $mediaId): JsonResponse
    {
        $model = match($modelType) {
            'product' => Product::findOrFail($modelId),
            'collection' => Collection::findOrFail($modelId),
            'brand' => Brand::findOrFail($modelId),
            default => throw new \InvalidArgumentException("Invalid model type: {$modelType}"),
        };

        // Authorize based on model type
        if ($model instanceof Product) {
            $this->authorize('update', $model);
        } elseif ($model instanceof Collection) {
            $this->authorize('update', $model);
        } elseif ($model instanceof Brand) {
            // Brand doesn't have a policy yet, but we can add authorization check here if needed
            // For now, we'll require staff/admin access
            if (!auth()->check() || (!auth()->user() instanceof \Lunar\Admin\Models\Staff)) {
                abort(403, 'Unauthorized');
            }
        }

        $deleted = MediaHelper::deleteImage($model, $mediaId);

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        }

        return response()->json([
            'error' => 'Media not found',
        ], 404);
    }

    /**
     * Reorder media items.
     * 
     * @param Request $request
     * @param string $modelType
     * @param int $modelId
     * @return JsonResponse
     */
    public function reorderMedia(Request $request, string $modelType, int $modelId): JsonResponse
    {
        $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer',
        ]);

        $model = match($modelType) {
            'product' => Product::findOrFail($modelId),
            'collection' => Collection::findOrFail($modelId),
            'brand' => Brand::findOrFail($modelId),
            default => throw new \InvalidArgumentException("Invalid model type: {$modelType}"),
        };

        // Authorize based on model type
        if ($model instanceof Product) {
            $this->authorize('update', $model);
        } elseif ($model instanceof Collection) {
            $this->authorize('update', $model);
        } elseif ($model instanceof Brand) {
            // Brand doesn't have a policy yet, but we can add authorization check here if needed
            // For now, we'll require staff/admin access
            if (!auth()->check() || (!auth()->user() instanceof \Lunar\Admin\Models\Staff)) {
                abort(403, 'Unauthorized');
            }
        }

        MediaHelper::reorderImages($model, $request->input('media_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Media reordered successfully',
        ]);
    }
}

