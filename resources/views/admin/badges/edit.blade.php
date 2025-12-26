@extends('admin.layout')

@section('title', 'Edit Badge')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.badges.update', $badge) }}">
            @csrf
            @method('PUT')
            @include('admin.badges._form', ['badge' => $badge])
        </form>
    </div>
</div>
@endsection
