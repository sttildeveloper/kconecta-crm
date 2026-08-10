<!DOCTYPE html>
<html lang="es" data-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kconecta</title>
        <link rel="preload" href="{{ asset('css/libraries/bulma.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ asset('css/libraries/bulma.css') }}"></noscript>
        <link rel="stylesheet" href="{{ asset('css/page/index.css') }}">
        <link rel="stylesheet" href="{{ asset('css/page/cookie.css') }}">
        <link rel="stylesheet" href="{{ asset('css/components/site-navbar.css') }}?v={{ filemtime(public_path('css/components/site-navbar.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/components/site-footer.css') }}?v={{ filemtime(public_path('css/components/site-footer.css')) }}">
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
        <meta name="robots" content="index, follow">
        <meta name="author" content="Kconecta">

        @yield('css')
    </head>
    <body>
        <div class="loader-page-change" id="loader-page-change">
            <img src="{{ asset('img/kconecta_icon.webp') }}" alt="Kconecta">
        </div>
        @include('layouts.partials.site-navbar')
        <span id="contenido-principal"></span>
        @yield('content')
        @include('layouts.partials.site-footer')

            <div id="cookieBanner" class="cookie-banner hide">
                <img src="{{ asset('img/cookie-monster-clipart-24.webp') }}" class="img-cookie" alt="Cookie Kconecta">
                <h1>COOKIES</h1>
                <p>Usamos cookies para mejorar tu experiencia en el sitio, analizar el trafico y personalizar contenido. Al hacer clic en "Aceptar", consientes su uso. Consulta nuestra <a href="{{ route('legal.privacy') }}">Politica de Privacidad</a> para mas informacion</p>
                <div class="cookie-buttons">
                    <button class="accept" onclick="cookieConfig()">Aceptar</button>
                    <button class="deny" onclick="cookieConfig()">Denegar</button>
                </div>
            </div>
        <script src="{{ asset('js/control_page_show.js') }}"></script>
        <script src="{{ asset('js/cookie_config.js') }}"></script>
        <script src="{{ asset('js/site-navbar.js') }}?v={{ filemtime(public_path('js/site-navbar.js')) }}" defer></script>
        @yield('js')
    </body>
</html>
