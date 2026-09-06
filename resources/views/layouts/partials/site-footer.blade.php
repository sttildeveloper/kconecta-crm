<footer class="site-footer" id="site-footer">
    <div class="site-footer__shell site-footer__grid">
        <div class="site-footer__about">
            <a class="site-footer__brand" href="{{ route('home') }}" aria-label="Kconecta, inicio">
                <img src="{{ asset('img/kconecta_icon.webp') }}" alt="" width="36" height="36">
                <span>Kconecta</span>
            </a>
            <p>La forma más sencilla de encontrar profesionales de confianza para tu hogar.</p>
        </div>

        <nav aria-label="Recursos">
            <strong>Recursos</strong>
            <a href="{{ url('/blogs') }}">Blog</a>
            <a href="{{ route('home') }}#consejos">Guías y consejos</a>
            <a href="mailto:info@kconecta.com">Contacto</a>
        </nav>

        <nav aria-label="Para clientes">
            <strong>Para clientes</strong>
            <a href="{{ route('home') }}#como-funciona">¿Cómo funciona?</a>
            <a href="{{ route('legal.terms') }}">Términos de uso</a>
            <a href="{{ route('legal.privacy') }}">Política de privacidad</a>
            <a href="mailto:info@kconecta.com">Ayuda</a>
        </nav>

        <nav aria-label="Sobre Kconecta">
            <strong>Sobre nosotros</strong>
            <a href="{{ route('home') }}#nosotros">Quiénes somos</a>
            <a href="{{ route('provider.landing') }}">Trabaja con nosotros</a>
            <a href="{{ route('home') }}#nosotros">Nuestra misión</a>
        </nav>

        <div class="site-footer__apps">
            <strong>Descarga la app</strong>
            <img src="{{ asset('img/app_store.webp') }}" alt="Disponible en App Store" width="146" height="43" loading="lazy">
            <img src="{{ asset('img/google_play.webp') }}" alt="Disponible en Google Play" width="146" height="43" loading="lazy">
        </div>
    </div>

    <div class="site-footer__shell site-footer__bottom">
        <small>© {{ date('Y') }} Kconecta</small>
        <div>
            <a href="{{ route('legal.privacy') }}">Privacidad</a>
            <a href="{{ route('legal.account-deletion') }}">Eliminación de cuenta</a>
            <button type="button" data-cookie-manage>Configurar cookies</button>
            <a href="mailto:contacto@kconecta.com">contacto@kconecta.com</a>
        </div>
    </div>
</footer>
