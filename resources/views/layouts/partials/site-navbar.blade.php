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

            <div class="home-navigation-disclosure" data-home-navigation-disclosure>
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
        </div>
    </header>
</div>
