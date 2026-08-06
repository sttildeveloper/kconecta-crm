<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Legal' }} - {{ $legalConfig['brand_name'] ?? 'Kconecta' }}</title>
    <link rel="stylesheet" href="{{ asset('css/legal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/site-footer.css') }}?v={{ filemtime(public_path('css/components/site-footer.css')) }}">
</head>
<body>
<div class="legal-wrap">
    <div class="legal-card">
        <h1>{{ $title ?? 'Legal' }}</h1>
        <p class="legal-muted">Ultima actualizacion: {{ $lastUpdated }}</p>

        @include('legal.partials.nav')

        @yield('content')
    </div>
</div>
@include('layouts.partials.site-footer')
</body>
</html>
