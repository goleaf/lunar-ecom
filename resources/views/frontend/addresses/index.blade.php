@extends('frontend.layout')

@section('title', __('frontend.addresses.title'))

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">{{ __('frontend.addresses.title') }}</h1>
        <a href="{{ route('frontend.addresses.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            {{ __('frontend.addresses.add_new') }}
        </a>
    </div>

    @if($addresses->isEmpty())
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-600 mb-4">{{ __('frontend.addresses.empty') }}</p>
            <a href="{{ route('frontend.addresses.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-block">
                {{ __('frontend.addresses.add_first') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($addresses as $address)
                <div class="bg-white rounded-lg shadow p-6 relative">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            @if($address->shipping_default)
                                <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mb-2">{{ __('frontend.addresses.default_shipping') }}</span>
                            @endif
                            @if($address->billing_default)
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mb-2">{{ __('frontend.addresses.default_billing') }}</span>
                            @endif
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('frontend.addresses.edit', $address) }}" class="text-blue-600 hover:text-blue-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <form action="{{ route('frontend.addresses.destroy', $address) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('frontend.addresses.confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        @if($address->company_name)
                            <p class="font-semibold">{{ $address->company_name }}</p>
                        @endif
                        <p>
                            @if($address->title)
                                {{ $address->title }}
                            @endif
                            {{ $address->first_name }} {{ $address->last_name }}
                        </p>
                        <p>{{ $address->line_one }}</p>
                        @if($address->line_two)
                            <p>{{ $address->line_two }}</p>
                        @endif
                        @if($address->line_three)
                            <p>{{ $address->line_three }}</p>
                        @endif
                        <p>
                            {{ $address->city }}
                            @if($address->state)
                                , {{ $address->state }}
                            @endif
                            @if($address->postcode)
                                {{ $address->postcode }}
                            @endif
                        </p>
                        @if($address->country)
                            <p>{{ $address->country->name }}</p>
                        @endif
                        @if($address->contact_phone)
                            <p class="text-gray-600">{{ __('frontend.addresses.phone') }}: {{ $address->contact_phone }}</p>
                        @endif
                        @if($address->delivery_instructions)
                            <p class="text-gray-600 italic">{{ __('frontend.addresses.note') }}: {{ $address->delivery_instructions }}</p>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t flex space-x-2">
                        @if(!$address->shipping_default)
                            <form action="{{ route('frontend.addresses.set-default-shipping', $address) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">{{ __('frontend.addresses.set_default_shipping') }}</button>
                            </form>
                        @endif
                        @if(!$address->billing_default)
                            <form action="{{ route('frontend.addresses.set-default-billing', $address) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">{{ __('frontend.addresses.set_default_billing') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection


