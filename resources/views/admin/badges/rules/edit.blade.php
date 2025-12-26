@extends('admin.layout')

@section('title', 'Edit Badge Rule')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.badges.rules.update', $rule) }}">
            @csrf
            @method('PUT')
            @include('admin.badges.rules._form', ['rule' => $rule])
        </form>
    </div>
</div>
@endsection
