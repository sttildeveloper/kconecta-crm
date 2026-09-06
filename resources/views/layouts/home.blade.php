<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kconecta | Profesionales de confianza para tu hogar</title>
    <meta name="description" content="Busca, compara y contacta profesionales de confianza para servicios del hogar sin necesidad de registrarte.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Kconecta">
    <meta name="cookie-consent-version" content="{{ config('compliance.cookies.consent_version') }}">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/page/home.css') }}?v={{ filemtime(public_path('css/page/home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/site-navbar.css') }}?v={{ filemtime(public_path('css/components/site-navbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/site-footer.css') }}?v={{ filemtime(public_path('css/components/site-footer.css')) }}">
    @yield('head')
</head>
<body>
    @include('layouts.partials.site-navbar')

    @yield('content')

    @include('layouts.partials.site-footer')

    <div id="cookieBanner" class="home-cookie-banner hide" role="dialog" aria-label="Preferencias de cookies">
        <p>Usamos cookies para mejorar tu experiencia. Consulta nuestra <a href="{{ route('legal.privacy') }}">Política de privacidad</a>.</p>
        <div>
            <button type="button" data-cookie-action="accept">Aceptar todas</button>
            <button type="button" data-cookie-action="deny">Solo necesarias</button>
        </div>
    </div>

    <script src="{{ asset('js/cookie_config.js') }}"></script>
    <script src="{{ asset('js/site-navbar.js') }}?v={{ filemtime(public_path('js/site-navbar.js')) }}" defer></script>
    <script src="{{ asset('js/home.js') }}" defer></script>
    @yield('scripts')
</body>
</html>
