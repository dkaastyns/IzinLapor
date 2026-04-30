<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Pengaduan SMAN 11') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="description"
        content="Sistem Pengaduan Fasilitas SMAN 11 - Laporkan kerusakan fasilitas sekolah dengan mudah dan cepat.">

    <!-- Security: Prevent search engine indexing (private school app) -->
    <meta name="robots" content="noindex, nofollow">

    <!-- Fonts (optimized with dns-prefetch) -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Scripts (CSP-compliant with nonce) -->
    @routes(nonce: Vite::cspNonce())
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>