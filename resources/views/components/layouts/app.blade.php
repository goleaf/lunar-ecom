{{-- 
| Livewire v3 Page Components default layout.
| Livewire expects this view to exist at "components.layouts.app".
| We proxy to the existing frontend layout, and render the page content via $slot.
--}}

@extends('frontend.layout')

@section('content')
    {{ $slot }}
@endsection



