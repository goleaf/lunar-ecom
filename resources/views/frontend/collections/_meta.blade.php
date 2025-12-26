<meta name="description" content="{{ $metaTags['description'] ?? '' }}">
<meta property="og:title" content="{{ $metaTags['og:title'] ?? '' }}">
<meta property="og:description" content="{{ $metaTags['og:description'] ?? '' }}">
<meta property="og:type" content="{{ $metaTags['og:type'] ?? 'website' }}">
<meta property="og:url" content="{{ $metaTags['og:url'] ?? request()->url() }}">
<link rel="canonical" href="{{ $metaTags['canonical'] ?? request()->url() }}">


