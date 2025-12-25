<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Form request for storing a new product with custom attributes.
 */
class StoreProductRequest extends FormRequest
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
        
        // Support both User and Staff models
        return Gate::forUser($user)->allows('create', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $baseRules = [
            // Lunar product fields
            'product_type_id' => ['required', 'exists:' . config('lunar.database.table_prefix') . 'product_types,id'],
            'status' => ['required', 'string', 'in:draft,published'],
            'attribute_data' => ['nullable', 'array'],
        ];

        // Merge custom attribute validation rules
        $customRules = Product::getValidationRules();

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
            'status.in' => 'The status must be either draft or published.',
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

