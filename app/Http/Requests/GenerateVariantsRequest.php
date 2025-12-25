<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Form request for generating product variants.
 */
class GenerateVariantsRequest extends FormRequest
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
        return [
            'option_ids' => ['required', 'array', 'min:1'],
            'option_ids.*' => ['required', 'exists:' . config('lunar.database.table_prefix') . 'product_options,id'],
            'defaults' => ['nullable', 'array'],
            'defaults.stock' => ['nullable', 'integer', 'min:0'],
            'defaults.backorder' => ['nullable', 'integer', 'min:0'],
            'defaults.purchasable' => ['nullable', 'in:always,in_stock,never'],
            'defaults.shippable' => ['nullable', 'boolean'],
            'defaults.enabled' => ['nullable', 'boolean'],
            'defaults.weight' => ['nullable', 'integer', 'min:0'],
            'defaults.price' => ['nullable', 'integer', 'min:0'],
            'defaults.currency_id' => ['nullable', 'exists:' . config('lunar.database.table_prefix') . 'currencies,id'],
            'defaults.compare_price' => ['nullable', 'integer', 'min:0'],
            'defaults.sku_prefix' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'option_ids.required' => 'At least one product option must be selected.',
            'option_ids.array' => 'Option IDs must be an array.',
            'option_ids.min' => 'At least one product option must be selected.',
            'option_ids.*.exists' => 'One or more selected product options are invalid.',
        ];
    }
}

