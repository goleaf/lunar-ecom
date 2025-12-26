@extends('storefront.layout')

@section('title', 'Edit Address')

@section('content')
<div class="px-4 py-6 max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Edit Address</h1>

    <form action="{{ route('storefront.addresses.update', $address) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <select name="title" id="title" class="border rounded px-3 py-2 w-full">
                        <option value="">Select Title</option>
                        <option value="Mr" {{ old('title', $address->title) == 'Mr' ? 'selected' : '' }}>Mr</option>
                        <option value="Mrs" {{ old('title', $address->title) == 'Mrs' ? 'selected' : '' }}>Mrs</option>
                        <option value="Miss" {{ old('title', $address->title) == 'Miss' ? 'selected' : '' }}>Miss</option>
                        <option value="Ms" {{ old('title', $address->title) == 'Ms' ? 'selected' : '' }}>Ms</option>
                        <option value="Dr" {{ old('title', $address->title) == 'Dr' ? 'selected' : '' }}>Dr</option>
                    </select>
                </div>
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company Name (Optional)</label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $address->company_name) }}" class="border rounded px-3 py-2 w-full">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $address->first_name) }}" required class="border rounded px-3 py-2 w-full @error('first_name') border-red-500 @enderror">
                    @error('first_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $address->last_name) }}" required class="border rounded px-3 py-2 w-full @error('last_name') border-red-500 @enderror">
                    @error('last_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="line_one" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                <input type="text" name="line_one" id="line_one" value="{{ old('line_one', $address->line_one) }}" required class="border rounded px-3 py-2 w-full @error('line_one') border-red-500 @enderror">
                @error('line_one')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="line_two" class="block text-sm font-medium text-gray-700 mb-1">Address Line 2 (Optional)</label>
                <input type="text" name="line_two" id="line_two" value="{{ old('line_two', $address->line_two) }}" class="border rounded px-3 py-2 w-full">
            </div>

            <div>
                <label for="line_three" class="block text-sm font-medium text-gray-700 mb-1">Address Line 3 (Optional)</label>
                <input type="text" name="line_three" id="line_three" value="{{ old('line_three', $address->line_three) }}" class="border rounded px-3 py-2 w-full">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $address->city) }}" required class="border rounded px-3 py-2 w-full @error('city') border-red-500 @enderror">
                    @error('city')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State/Province (Optional)</label>
                    <input type="text" name="state" id="state" value="{{ old('state', $address->state) }}" class="border rounded px-3 py-2 w-full">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="postcode" class="block text-sm font-medium text-gray-700 mb-1">Postcode/ZIP (Optional)</label>
                    <input type="text" name="postcode" id="postcode" value="{{ old('postcode', $address->postcode) }}" class="border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                    <select name="country_id" id="country_id" required class="border rounded px-3 py-2 w-full @error('country_id') border-red-500 @enderror">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ old('country_id', $address->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email (Optional)</label>
                    <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $address->contact_email) }}" class="border rounded px-3 py-2 w-full @error('contact_email') border-red-500 @enderror">
                    @error('contact_email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Contact Phone (Optional)</label>
                    <input type="text" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $address->contact_phone) }}" class="border rounded px-3 py-2 w-full">
                </div>
            </div>

            <div>
                <label for="delivery_instructions" class="block text-sm font-medium text-gray-700 mb-1">Delivery Instructions (Optional)</label>
                <textarea name="delivery_instructions" id="delivery_instructions" rows="3" class="border rounded px-3 py-2 w-full">{{ old('delivery_instructions', $address->delivery_instructions) }}</textarea>
            </div>

            <div class="flex space-x-4">
                <div class="flex items-center">
                    <input type="checkbox" name="shipping_default" id="shipping_default" value="1" {{ old('shipping_default', $address->shipping_default) ? 'checked' : '' }} class="mr-2">
                    <label for="shipping_default" class="text-sm text-gray-700">Set as default shipping address</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="billing_default" id="billing_default" value="1" {{ old('billing_default', $address->billing_default) ? 'checked' : '' }} class="mr-2">
                    <label for="billing_default" class="text-sm text-gray-700">Set as default billing address</label>
                </div>
            </div>

            <div class="flex space-x-4 pt-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Update Address
                </button>
                <a href="{{ route('storefront.addresses.index') }}" class="bg-gray-200 text-gray-800 px-6 py-2 rounded hover:bg-gray-300">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

