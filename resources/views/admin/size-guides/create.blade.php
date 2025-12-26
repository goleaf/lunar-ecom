@extends('admin.layout')

@section('title', 'Create Size Guide')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.size-guides.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.size-guides._form', ['sizeGuide' => null])
        </form>
    </div>
</div>
@endsection
