<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Form request for updating an existing product with custom attributes.
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        $product = $this->route('product');
        if (!$product) {
            return false;
        }
        
        // Support both User and Staff models
        return Gate::forUser($user)->allows('update', $product);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('id');

        $baseRules = [
            // Lunar product fields
            'product_type_id' => ['sometimes', 'required', 'exists:' . config('lunar.database.table_prefix') . 'product_types,id'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,active,published,archived,discontinued'],
            'visibility' => ['sometimes', 'nullable', 'string', 'in:public,private,scheduled'],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'full_description' => ['sometimes', 'nullable', 'string'],
            'technical_description' => ['sometimes', 'nullable', 'string'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string'],
            'meta_keywords' => ['sometimes', 'nullable', 'string'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'scheduled_publish_at' => ['sometimes', 'nullable', 'date'],
            'scheduled_unpublish_at' => ['sometimes', 'nullable', 'date'],
            'attribute_data' => ['nullable', 'array'],
        ];

        // Merge custom attribute validation rules (with product ID for unique checks)
        $customRules = Product::getValidationRules($productId);

        return array_merge($baseRules, $customRules);
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return array_merge([
            'product_type_id.required' => 'The product type is required.',
            'product_type_id.exists' => 'The selected product type is invalid.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be draft, active, published, archived, or discontinued.',
        ], Product::getValidationMessages());
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize barcode (remove spaces, dashes)
        if ($this->has('barcode')) {
            $this->merge([
                'barcode' => preg_replace('/[\s-]/', '', $this->input('barcode')),
            ]);
        }

        // Normalize SKU (uppercase, trim)
        if ($this->has('sku')) {
            $this->merge([
                'sku' => strtoupper(trim($this->input('sku'))),
            ]);
        }

        // Normalize origin country (uppercase)
        if ($this->has('origin_country')) {
            $this->merge([
                'origin_country' => strtoupper($this->input('origin_country')),
            ]);
        }
    }
}
