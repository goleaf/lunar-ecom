@extends('layouts.app')

@section('title', 'Product Comparison')

@section('content')
<div id="comparison-page-container"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const comparisonPage = new ComparisonPage('comparison-page-container', {
            apiUrl: '{{ route('storefront.comparison.index') }}'
        });
    });
</script>
@endsection
