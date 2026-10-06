<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f0e8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $meta['title'] }}</title>
        <meta name="description" content="{{ $meta['description'] }}">
        @if ($meta['keywords'])
            <meta name="keywords" content="{{ $meta['keywords'] }}">
        @endif
        <meta name="robots" content="{{ $meta['robots'] }}">
        <link rel="canonical" href="{{ $meta['canonical'] }}">
        <link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
        @if ($meta['googleVerification'])
            <meta name="google-site-verification" content="{{ $meta['googleVerification'] }}">
        @endif
        @if ($meta['bingVerification'])
            <meta name="msvalidate.01" content="{{ $meta['bingVerification'] }}">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $meta['siteName'] }}">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta property="og:url" content="{{ $meta['canonical'] }}">
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
        @endif
        <meta name="twitter:card" content="{{ $meta['image'] ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $meta['title'] }}">
        <meta name="twitter:description" content="{{ $meta['description'] }}">
        @if ($meta['image'])
            <meta name="twitter:image" content="{{ $meta['image'] }}">
        @endif
        @if ($meta['twitter'])
            <meta name="twitter:site" content="{{ '@'.ltrim($meta['twitter'], '@') }}">
        @endif

        <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)</script>

        @if ($analytics['gtm_container_id'])
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($analytics['gtm_container_id']));</script>
        @endif
        @if ($analytics['ga4_measurement_id'])
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($analytics['ga4_measurement_id']) }}"></script>
            <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@json($analytics['ga4_measurement_id']));</script>
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        @if ($analytics['gtm_container_id'])
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($analytics['gtm_container_id']) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endif
        <div id="app"></div>
    </body>
</html>
