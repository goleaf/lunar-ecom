@extends('admin.layout')

@section('title', 'Create Badge Rule')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.badges.rules.store') }}">
            @csrf
            @include('admin.badges.rules._form', ['rule' => null])
        </form>
    </div>
</div>
@endsection
