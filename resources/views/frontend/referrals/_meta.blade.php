<meta name="robots" content="{{ $noindex ? 'noindex, follow' : 'index, follow' }}">
<link rel="canonical" href="{{ $canonical }}">
@foreach ($hreflangs as $alt)
    <link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}">
@endforeach


