@extends('frontend.layout')

@section('title', $metaTags['title'] ?? 'Brands Directory')

@section('meta')
    <meta name="description" content="{{ $metaTags['description'] }}">
    <meta property="og:title" content="{{ $metaTags['og:title'] }}">
    <meta property="og:description" content="{{ $metaTags['og:description'] }}">
    <meta property="og:type" content="{{ $metaTags['og:type'] }}">
    <meta property="og:url" content="{{ $metaTags['og:url'] }}">
    <link rel="canonical" href="{{ $metaTags['canonical'] }}">
@endsection

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Brand Directory</h1>

    {{-- A-Z Navigation --}}
    <div class="mb-8">
        <div class="flex flex-wrap gap-2 justify-center">
            <a href="{{ route('frontend.brands.index') }}" 
               class="px-3 py-1 rounded {{ !$letter ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                All
            </a>
            @foreach(range('A', 'Z') as $char)
                @php
                    $hasBrands = in_array($char, $availableLetters);
                @endphp
                <a href="{{ route('frontend.brands.index', ['letter' => $char]) }}" 
                   class="px-3 py-1 rounded {{ ($letter === $char) ? 'bg-blue-600 text-white' : ($hasBrands ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-gray-100 text-gray-400 cursor-not-allowed') }}"
                   @if(!$hasBrands) onclick="return false;" @endif>
                    {{ $char }}
                </a>
            @endforeach
            @if(in_array('#', $availableLetters))
                <a href="{{ route('frontend.brands.index', ['letter' => '#']) }}" 
                   class="px-3 py-1 rounded {{ ($letter === '#') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    #
                </a>
            @endif
        </div>
    </div>

    {{-- Brands by Letter --}}
    @if($letter)
        <div class="mb-6">
            <h2 class="text-2xl font-semibold mb-4">Brands Starting with "{{ $letter }}"</h2>
            @if(isset($groupedBrands[$letter]) && $groupedBrands[$letter]->count() > 0)
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($groupedBrands[$letter] as $brand)
                        @include('frontend.brands._brand-card', ['brand' => $brand])
                    @endforeach
                </div>
            @else
                <p class="text-gray-600">No brands found starting with "{{ $letter }}".</p>
            @endif
        </div>
    @else
        {{-- All Brands Grouped by Letter --}}
        @foreach($groupedBrands as $letterKey => $brandsGroup)
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4 border-b pb-2">{{ $letterKey }}</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($brandsGroup as $brand)
                        @include('frontend.brands._brand-card', ['brand' => $brand])
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

    @if($allBrands->count() === 0)
        <div class="text-center py-12">
            <p class="text-gray-600">No brands available.</p>
        </div>
    @endif
</div>
@endsection


