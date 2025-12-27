<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCustomization;
use App\Models\OrderItemCustomization;
use App\Models\CustomizationTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CustomizationService
{
    /**
     * Validate customization values.
     *
     * @param  Product  $product
     * @param  array  $customizations  ['field_name' => 'value']
     * @return array  ['valid' => bool, 'errors' => [], 'price' => decimal]
     */
    public function validateCustomization(Product $product, array $customizations): array
    {
        $errors = [];
        $totalPrice = 0;
        $validatedData = [];

        $productCustomizations = ProductCustomization::where('product_id', $product->id)
            ->active()
            ->orderBy('display_order')
            ->get()
            ->keyBy('field_name');

        foreach ($productCustomizations as $customization) {
            $fieldName = $customization->field_name;
            $value = $customizations[$fieldName] ?? null;

            // Check required fields
            if ($customization->is_required && empty($value)) {
                $errors[$fieldName] = "{$customization->field_label} is required.";
                continue;
            }

            // Skip validation if value is empty and not required
            if (empty($value) && !$customization->is_required) {
                continue;
            }

            // Validate based on type
            $validation = $this->validateCustomizationValue($customization, $value);
            
            if (!$validation['valid']) {
                $errors[$fieldName] = $validation['error'];
                continue;
            }

            // Calculate price
            $price = $this->calculateCustomizationPrice($customization, $value);
            $totalPrice += $price;

            $validatedData[$fieldName] = [
                'customization_id' => $customization->id,
                'value' => $validation['processed_value'],
                'value_type' => $customization->customization_type,
                'additional_cost' => $price,
            ];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'price' => $totalPrice,
            'data' => $validatedData,
        ];
    }

    /**
     * Validate a single customization value.
     *
     * @param  ProductCustomization  $customization
     * @param  mixed  $value
     * @return array
     */
    protected function validateCustomizationValue(ProductCustomization $customization, $value): array
    {
        switch ($customization->customization_type) {
            case 'text':
                return $this->validateTextCustomization($customization, $value);
            
            case 'image':
                return $this->validateImageCustomization($customization, $value);
            
            case 'option':
            case 'color':
                return $this->validateOptionCustomization($customization, $value);
            
            case 'number':
                return $this->validateNumberCustomization($customization, $value);
            
            case 'date':
                return $this->validateDateCustomization($customization, $value);
            
            default:
                return ['valid' => false, 'error' => 'Unknown customization type.'];
        }
    }

    /**
     * Validate text customization.
     */
    protected function validateTextCustomization(ProductCustomization $customization, $value): array
    {
        if (!is_string($value)) {
            return ['valid' => false, 'error' => 'Invalid text value.'];
        }

        $length = mb_strlen($value);

        // Check length
        if ($customization->min_length && $length < $customization->min_length) {
            return [
                'valid' => false,
                'error' => "Text must be at least {$customization->min_length} characters.",
            ];
        }

        if ($customization->max_length && $length > $customization->max_length) {
            return [
                'valid' => false,
                'error' => "Text must not exceed {$customization->max_length} characters.",
            ];
        }

        // Check pattern
        if ($customization->pattern && !preg_match("/{$customization->pattern}/", $value)) {
            return ['valid' => false, 'error' => 'Text does not match required format.'];
        }

        // Profanity filter
        if ($this->containsProfanity($value)) {
            return ['valid' => false, 'error' => 'Text contains inappropriate content.'];
        }

        return [
            'valid' => true,
            'processed_value' => trim($value),
        ];
    }

    /**
     * Validate image customization.
     */
    protected function validateImageCustomization(ProductCustomization $customization, $value): array
    {
        if (!$value instanceof UploadedFile) {
            return ['valid' => false, 'error' => 'Invalid image file.'];
        }

        // Check file format
        $extension = strtolower($value->getClientOriginalExtension());
        $allowedFormats = $customization->allowed_formats ?? ['jpg', 'jpeg', 'png', 'svg'];
        
        if (!in_array($extension, $allowedFormats)) {
            return [
                'valid' => false,
                'error' => 'Invalid file format. Allowed: ' . implode(', ', $allowedFormats),
            ];
        }

        // Check file size
        $fileSizeKb = $value->getSize() / 1024;
        if ($customization->max_file_size_kb && $fileSizeKb > $customization->max_file_size_kb) {
            return [
                'valid' => false,
                'error' => "File size exceeds maximum of {$customization->max_file_size_kb} KB.",
            ];
        }

        // Check image dimensions
        try {
            $image = Image::make($value);
            $width = $image->width();
            $height = $image->height();

            if ($customization->min_width && $width < $customization->min_width) {
                return [
                    'valid' => false,
                    'error' => "Image width must be at least {$customization->min_width} pixels.",
                ];
            }

            if ($customization->max_width && $width > $customization->max_width) {
                return [
                    'valid' => false,
                    'error' => "Image width must not exceed {$customization->max_width} pixels.",
                ];
            }

            if ($customization->min_height && $height < $customization->min_height) {
                return [
                    'valid' => false,
                    'error' => "Image height must be at least {$customization->min_height} pixels.",
                ];
            }

            if ($customization->max_height && $height > $customization->max_height) {
                return [
                    'valid' => false,
                    'error' => "Image height must not exceed {$customization->max_height} pixels.",
                ];
            }

            // Check aspect ratio
            if ($customization->aspect_ratio_width && $customization->aspect_ratio_height) {
                $expectedRatio = $customization->aspect_ratio_width / $customization->aspect_ratio_height;
                $actualRatio = $width / $height;
                
                if (abs($expectedRatio - $actualRatio) > 0.1) {
                    return [
                        'valid' => false,
                        'error' => "Image aspect ratio must be {$customization->aspect_ratio_width}:{$customization->aspect_ratio_height}.",
                    ];
                }
            }

            // Basic content check (could be enhanced with AI)
            // For now, just return success

            return [
                'valid' => true,
                'processed_value' => $value, // Will be processed in processCustomization
                'image_data' => [
                    'width' => $width,
                    'height' => $height,
                    'size_kb' => round($fileSizeKb, 2),
                ],
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Failed to process image: ' . $e->getMessage()];
        }
    }

    /**
     * Validate option customization.
     */
    protected function validateOptionCustomization(ProductCustomization $customization, $value): array
    {
        $allowedValues = $customization->allowed_values ?? [];

        if (empty($allowedValues)) {
            return ['valid' => true, 'processed_value' => $value];
        }

        if (!in_array($value, $allowedValues)) {
            return [
                'valid' => false,
                'error' => 'Invalid option selected.',
            ];
        }

        return ['valid' => true, 'processed_value' => $value];
    }

    /**
     * Validate number customization.
     */
    protected function validateNumberCustomization(ProductCustomization $customization, $value): array
    {
        if (!is_numeric($value)) {
            return ['valid' => false, 'error' => 'Invalid number value.'];
        }

        $num = (float) $value;

        // Could add min/max validation here if needed
        return ['valid' => true, 'processed_value' => $num];
    }

    /**
     * Validate date customization.
     */
    protected function validateDateCustomization(ProductCustomization $customization, $value): array
    {
        try {
            $date = \Carbon\Carbon::parse($value);
            return ['valid' => true, 'processed_value' => $date->toDateString()];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Invalid date format.'];
        }
    }

    /**
     * Calculate customization price.
     *
     * @param  ProductCustomization  $customization
     * @param  mixed  $value
     * @return float
     */
    public function calculateCustomizationPrice(ProductCustomization $customization, $value): float
    {
        if ($customization->price_modifier <= 0) {
            return 0;
        }

        switch ($customization->price_modifier_type) {
            case 'per_character':
                if (is_string($value)) {
                    return $customization->price_modifier * mb_strlen($value);
                }
                return 0;

            case 'per_image':
                return $customization->price_modifier;

            case 'fixed':
            default:
                return $customization->price_modifier;
        }
    }

    /**
     * Process and store customization (for cart/order).
     *
     * @param  array  $validatedData  From validateCustomization
     * @param  int  $orderItemId
     * @return array  Created OrderItemCustomization models
     */
    public function processCustomization(array $validatedData, int $orderItemId): array
    {
        $customizations = [];

        DB::transaction(function () use ($validatedData, $orderItemId, &$customizations) {
            foreach ($validatedData as $fieldData) {
                $customization = ProductCustomization::find($fieldData['customization_id']);
                $value = $fieldData['value'];

                // Handle image upload
                if ($customization->isImageType() && $value instanceof UploadedFile) {
                    $imageData = $this->processImageUpload($value, $customization);
                    $value = $imageData['path'];
                }

                $orderItemCustomization = OrderItemCustomization::create([
                    'order_item_id' => $orderItemId,
                    'customization_id' => $customization->id,
                    'value' => is_string($value) ? $value : null,
                    'value_type' => $fieldData['value_type'],
                    'image_path' => $customization->isImageType() ? $value : null,
                    'image_original_name' => $customization->isImageType() ? ($value instanceof UploadedFile ? $value->getClientOriginalName() : null) : null,
                    'image_width' => $customization->isImageType() ? ($imageData['width'] ?? null) : null,
                    'image_height' => $customization->isImageType() ? ($imageData['height'] ?? null) : null,
                    'image_size_kb' => $customization->isImageType() ? ($imageData['size_kb'] ?? null) : null,
                    'additional_cost' => $fieldData['additional_cost'],
                    'currency_code' => \Lunar\Models\Currency::getDefault()->code ?? 'USD',
                ]);

                $customizations[] = $orderItemCustomization;
            }
        });

        return $customizations;
    }

    /**
     * Process image upload.
     *
     * @param  UploadedFile  $file
     * @param  ProductCustomization  $customization
     * @return array
     */
    public function processImageUpload(UploadedFile $file, ProductCustomization $customization): array
    {
        $path = 'customizations/' . date('Y/m') . '/' . Str::random(40) . '.' . $file->getClientOriginalExtension();
        
        // Store original
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        // Get image dimensions
        $image = Image::make($file);
        $width = $image->width();
        $height = $image->height();
        $sizeKb = round($file->getSize() / 1024, 2);

        return [
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'size_kb' => $sizeKb,
        ];
    }

    /**
     * Generate preview image.
     *
     * @param  Product  $product
     * @param  array  $customizations
     * @return string  Base64 encoded preview image
     */
    public function generatePreview(Product $product, array $customizations): string
    {
        // Get product base image
        $baseImage = $product->thumbnail?->getUrl() ?? $product->images->first()?->getUrl();
        
        if (!$baseImage) {
            throw new \Exception('Product image not found for preview.');
        }

        // Load base image
        $image = Image::make($baseImage);

        // Get customizations for preview
        $productCustomizations = ProductCustomization::where('product_id', $product->id)
            ->forPreview()
            ->get()
            ->keyBy('field_name');

        foreach ($customizations as $fieldName => $value) {
            $customization = $productCustomizations[$fieldName] ?? null;
            
            if (!$customization || !$value) {
                continue;
            }

            $previewSettings = $customization->preview_settings ?? [];

            switch ($customization->customization_type) {
                case 'text':
                    $this->applyTextToPreview($image, $value, $previewSettings);
                    break;

                case 'image':
                    if ($value instanceof UploadedFile) {
                        $this->applyImageToPreview($image, $value, $previewSettings);
                    }
                    break;
            }
        }

        // Return base64 encoded image
        return (string) $image->encode('data-url');
    }

    /**
     * Apply text to preview image.
     */
    protected function applyTextToPreview($image, string $text, array $settings): void
    {
        $x = $settings['position']['x'] ?? 100;
        $y = $settings['position']['y'] ?? 100;
        $font = $settings['font'] ?? 'Arial';
        $fontSize = $settings['font_size'] ?? 24;
        $color = $settings['color'] ?? '#000000';
        $rotation = $settings['rotation'] ?? 0;
        $opacity = $settings['opacity'] ?? 1.0;

        // Note: Intervention Image text support may vary
        // This is a simplified version - you may need to use GD or Imagick directly
        $image->text($text, $x, $y, function ($font) use ($fontSize, $color) {
            $font->size($fontSize);
            $font->color($color);
        });
    }

    /**
     * Apply uploaded image to preview.
     */
    protected function applyImageToPreview($image, UploadedFile $uploadedImage, array $settings): void
    {
        $x = $settings['position']['x'] ?? 100;
        $y = $settings['position']['y'] ?? 100;
        $width = $settings['width'] ?? 200;
        $height = $settings['height'] ?? 200;
        $opacity = $settings['opacity'] ?? 1.0;

        $overlay = Image::make($uploadedImage->getRealPath());
        $overlay->resize($width, $height);
        $overlay->opacity($opacity * 100);

        $image->insert($overlay, 'top-left', $x, $y);
    }

    /**
     * Check if text contains profanity.
     *
     * @param  string  $text
     * @return bool
     */
    protected function containsProfanity(string $text): bool
    {
        // Simple profanity filter - in production, use a proper library or API
        $profanityWords = config('customization.profanity_words', []);
        
        $textLower = mb_strtolower($text);
        
        foreach ($profanityWords as $word) {
            if (mb_strpos($textLower, mb_strtolower($word)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get customizations for a product.
     *
     * @param  Product  $product
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductCustomizations(Product $product)
    {
        return ProductCustomization::where('product_id', $product->id)
            ->active()
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Get customization templates.
     *
     * @param  string|null  $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTemplates(?string $category = null)
    {
        $query = CustomizationTemplate::active();

        if ($category) {
            $query->byCategory($category);
        }

        return $query->orderBy('usage_count', 'desc')->get();
    }

    /**
     * Get customization examples for a product.
     *
     * @param  Product  $product
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExamples(Product $product)
    {
        return \App\Models\CustomizationExample::where('product_id', $product->id)
            ->active()
            ->orderBy('display_order')
            ->get();
    }
}

