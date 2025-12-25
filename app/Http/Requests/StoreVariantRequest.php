<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Form request for storing a new product variant.
 */
class StoreVariantRequest extends FormRequest
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
        return Gate::forUser($user)->allows('create', ProductVariant::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $baseRules = [
            'sku' => ['required', 'string', 'max:255', 'unique:' . config('lunar.database.table_prefix') . 'product_variants,sku'],
            'gtin' => ['nullable', 'string', 'max:255'],
            'mpn' => ['nullable', 'string', 'max:255'],
            'ean' => ['nullable', 'string', 'max:255'],
            'unit_quantity' => ['required', 'integer', 'min:1'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'quantity_increment' => ['nullable', 'integer', 'min:1'],
            'stock' => ['required', 'integer', 'min:0'],
            'backorder' => ['nullable', 'integer', 'min:0'],
            'purchasable' => ['required', 'in:always,in_stock,never'],
            'shippable' => ['nullable', 'boolean'],
            'tax_class_id' => ['required', 'exists:' . config('lunar.database.table_prefix') . 'tax_classes,id'],
            'prices' => ['nullable', 'array'],
            'prices.*.price' => ['required', 'integer', 'min:0'],
            'prices.*.compare_price' => ['nullable', 'integer', 'min:0'],
            'prices.*.currency_id' => ['required', 'exists:' . config('lunar.database.table_prefix') . 'currencies,id'],
            'prices.*.min_quantity' => ['nullable', 'integer', 'min:1'],
            'prices.*.tier' => ['nullable', 'integer', 'min:1'],
        ];

        // Merge custom attribute validation rules
        $customRules = ProductVariant::getValidationRules();

        return array_merge($baseRules, $customRules);
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return array_merge([
            'sku.required' => 'The SKU is required.',
            'sku.unique' => 'The SKU has already been taken.',
            'unit_quantity.required' => 'The unit quantity is required.',
            'stock.required' => 'The stock quantity is required.',
            'purchasable.required' => 'The purchasable status is required.',
            'purchasable.in' => 'The purchasable status must be always, in_stock, or never.',
            'tax_class_id.required' => 'The tax class is required.',
            'tax_class_id.exists' => 'The selected tax class is invalid.',
        ], ProductVariant::getValidationMessages());
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize SKU (uppercase, trim)
        if ($this->has('sku')) {
            $this->merge([
                'sku' => strtoupper(trim($this->input('sku'))),
            ]);
        }

        // Set defaults
        $this->merge([
            'unit_quantity' => $this->input('unit_quantity', 1),
            'min_quantity' => $this->input('min_quantity', 1),
            'quantity_increment' => $this->input('quantity_increment', 1),
            'stock' => $this->input('stock', 0),
            'backorder' => $this->input('backorder', 0),
            'purchasable' => $this->input('purchasable', 'always'),
            'shippable' => $this->input('shippable', true),
            'enabled' => $this->input('enabled', true),
        ]);
    }
}

