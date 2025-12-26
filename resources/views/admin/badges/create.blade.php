@extends('admin.layout')

@section('title', 'Create Badge')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.badges.store') }}">
            @csrf
            @include('admin.badges._form', ['badge' => null])
        </form>
    </div>
</div>
@endsection
