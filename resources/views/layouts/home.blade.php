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
    @yield('head')
</head>
<body>
    <a class="home-skip-link" href="#contenido-principal">Saltar al contenido</a>

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

    @yield('content')

    <footer class="home-footer">
        <div class="home-shell home-footer__grid">
            <div class="home-footer__about">
                <a class="home-brand home-brand--footer" href="{{ route('home') }}" aria-label="Kconecta, inicio">
                    <img src="{{ asset('img/kconecta_icon.webp') }}" alt="" width="36" height="36">
                    <span>Kconecta</span>
                </a>
                <p>La forma más sencilla de encontrar profesionales de confianza para tu hogar.</p>
            </div>

            <nav aria-label="Recursos">
                <strong>Recursos</strong>
                <a href="{{ url('/blogs') }}">Blog</a>
                <a href="#consejos">Guías y consejos</a>
                <a href="mailto:info@kconecta.com">Contacto</a>
            </nav>

            <nav aria-label="Para clientes">
                <strong>Para clientes</strong>
                <a href="#como-funciona">¿Cómo funciona?</a>
                <a href="{{ route('legal.terms') }}">Términos de uso</a>
                <a href="{{ route('legal.privacy') }}">Política de privacidad</a>
                <a href="mailto:info@kconecta.com">Ayuda</a>
            </nav>

            <nav aria-label="Sobre Kconecta">
                <strong>Sobre nosotros</strong>
                <a href="#nosotros">Quiénes somos</a>
                <a href="{{ route('provider.landing') }}">Trabaja con nosotros</a>
                <a href="#nosotros">Nuestra misión</a>
            </nav>

            <div class="home-footer__apps">
                <strong>Descarga la app</strong>
                <img src="{{ asset('img/app_store.webp') }}" alt="Disponible en App Store" width="146" height="43" loading="lazy">
                <img src="{{ asset('img/google_play.webp') }}" alt="Disponible en Google Play" width="146" height="43" loading="lazy">
            </div>
        </div>

        <div class="home-shell home-footer__bottom">
            <small>© {{ date('Y') }} Kconecta</small>
            <div>
                <a href="{{ route('legal.privacy') }}">Privacidad</a>
                <a href="{{ route('legal.account-deletion') }}">Eliminación de cuenta</a>
                <a href="mailto:contacto@kconecta.com">contacto@kconecta.com</a>
            </div>
        </div>
    </footer>

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
