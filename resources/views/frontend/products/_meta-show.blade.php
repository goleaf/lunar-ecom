<meta name="description" content="{{ $metaTags['description'] ?? '' }}">
@if(!empty($metaTags['keywords']))
    <meta name="keywords" content="{{ $metaTags['keywords'] }}">
@endif
@if(!empty($robotsMeta))
    <meta name="robots" content="{{ $robotsMeta }}">
@endif

{{-- Open Graph --}}
<meta property="og:title" content="{{ $metaTags['og:title'] ?? '' }}">
<meta property="og:description" content="{{ $metaTags['og:description'] ?? '' }}">
@if(!empty($metaTags['og:image']))
    <meta property="og:image" content="{{ $metaTags['og:image'] }}">
@endif
<meta property="og:url" content="{{ $metaTags['og:url'] ?? request()->url() }}">
<meta property="og:type" content="{{ $metaTags['og:type'] ?? 'website' }}">
@if(!empty($metaTags['og:site_name']))
    <meta property="og:site_name" content="{{ $metaTags['og:site_name'] }}">
@endif

{{-- Twitter Card --}}
@if(!empty($metaTags['twitter:card']))
    <meta name="twitter:card" content="{{ $metaTags['twitter:card'] }}">
    <meta name="twitter:title" content="{{ $metaTags['twitter:title'] ?? '' }}">
    <meta name="twitter:description" content="{{ $metaTags['twitter:description'] ?? '' }}">
    @if(!empty($metaTags['twitter:image']))
        <meta name="twitter:image" content="{{ $metaTags['twitter:image'] }}">
    @endif
@endif

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $metaTags['canonical'] ?? request()->url() }}">

{{-- Structured Data (JSON-LD) --}}
@if(!empty($structuredData))
    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endif


