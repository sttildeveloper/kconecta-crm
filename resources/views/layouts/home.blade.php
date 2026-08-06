<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kconecta | Profesionales de confianza para tu hogar</title>
    <meta name="description" content="Busca, compara y contacta profesionales de confianza para servicios del hogar sin necesidad de registrarte.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Kconecta">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/page/home.css') }}?v={{ filemtime(public_path('css/page/home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/site-footer.css') }}?v={{ filemtime(public_path('css/components/site-footer.css')) }}">
    @yield('head')
</head>
<body>
    <a class="home-skip-link" href="#contenido-principal">Saltar al contenido</a>

    <div class="home-sticky-navigation">
    @if (! View::hasSection('hide_announcement'))
        <div class="home-announcement" role="status">
            <span aria-hidden="true">★</span>
            Encuentra profesionales confiables y mejora tu hogar.
            <button type="button" aria-label="Ocultar aviso" data-home-announcement-close>×</button>
        </div>
    @endif

    <header class="home-header" data-home-header>
        <div class="home-shell home-header__inner">
            <a class="home-brand" href="{{ route('home') }}" aria-label="Kconecta, inicio">
                <img src="{{ asset('img/kconecta_icon.webp') }}" alt="" width="42" height="42">
            </a>

            <button
                class="home-menu-button"
                type="button"
                aria-label="Abrir menú"
                aria-expanded="false"
                aria-controls="home-navigation"
                data-home-menu-button
            >
                <span></span><span></span><span></span>
            </button>

            <nav class="home-navigation" id="home-navigation" aria-label="Navegación principal" data-home-navigation>
                <div class="home-navigation__links">
                    <a href="{{ route('home') }}#servicios">Servicios</a>
                    <a href="{{ route('home') }}#como-funciona">¿Cómo funciona?</a>
                    <a href="{{ route('home') }}#nosotros">Sobre nosotros</a>
                    <a href="{{ route('home') }}#consejos">Consejos</a>
                    <a href="{{ url('/blogs') }}">Novedades</a>
                    <a href="{{ route('provider.landing') }}">Quiero ser proveedor</a>
                </div>
                <div class="home-navigation__actions">
                    @auth
                        <a href="{{ route('dashboard') }}">Mi panel</a>
                    @else
                        <a href="{{ route('login') }}">Inicia sesión</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>
    </div>

    @yield('content')

    @include('layouts.partials.site-footer')

    <div id="cookieBanner" class="home-cookie-banner hide" role="dialog" aria-label="Preferencias de cookies">
        <p>Usamos cookies para mejorar tu experiencia. Consulta nuestra <a href="{{ route('legal.privacy') }}">Política de privacidad</a>.</p>
        <div>
            <button type="button" onclick="cookieConfig()">Aceptar</button>
            <button type="button" onclick="cookieConfig()">Denegar</button>
        </div>
    </div>

    <script src="{{ asset('js/cookie_config.js') }}"></script>
    <script src="{{ asset('js/home.js') }}" defer></script>
    @yield('scripts')
</body>
</html>
