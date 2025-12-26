@extends('admin.layout')

@section('title', 'Edit Size Guide')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.size-guides.update', $sizeGuide) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.size-guides._form', ['sizeGuide' => $sizeGuide])
        </form>
    </div>
</div>
@endsection
