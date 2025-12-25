<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for checkout validation.
 */
class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.line_one' => 'required|string|max:255',
            'shipping_address.line_two' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.state' => 'nullable|string|max:255',
            'shipping_address.postcode' => 'required|string|max:255',
            'shipping_address.country_id' => 'required|exists:lunar_countries,id',
            'billing_address.first_name' => 'required|string|max:255',
            'billing_address.last_name' => 'required|string|max:255',
            'billing_address.line_one' => 'required|string|max:255',
            'billing_address.line_two' => 'nullable|string|max:255',
            'billing_address.city' => 'required|string|max:255',
            'billing_address.state' => 'nullable|string|max:255',
            'billing_address.postcode' => 'required|string|max:255',
            'billing_address.country_id' => 'required|exists:lunar_countries,id',
            'payment_method' => 'required|string|in:card,paypal,bank_transfer',
            'payment_token' => 'nullable|string',
            'save_billing_address' => 'nullable|boolean',
            'save_shipping_address' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shipping_address.first_name.required' => 'Shipping first name is required.',
            'shipping_address.last_name.required' => 'Shipping last name is required.',
            'shipping_address.line_one.required' => 'Shipping address line 1 is required.',
            'shipping_address.city.required' => 'Shipping city is required.',
            'shipping_address.postcode.required' => 'Shipping postcode is required.',
            'shipping_address.country_id.required' => 'Shipping country is required.',
            'billing_address.first_name.required' => 'Billing first name is required.',
            'billing_address.last_name.required' => 'Billing last name is required.',
            'billing_address.line_one.required' => 'Billing address line 1 is required.',
            'billing_address.city.required' => 'Billing city is required.',
            'billing_address.postcode.required' => 'Billing postcode is required.',
            'billing_address.country_id.required' => 'Billing country is required.',
            'payment_method.required' => 'Payment method is required.',
        ];
    }
}

