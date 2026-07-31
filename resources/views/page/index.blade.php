@extends('layouts.home')

@section('head')
    <meta property="og:type" content="website">
    <meta property="og:title" content="Kconecta | Profesionales de confianza para tu hogar">
    <meta property="og:description" content="Compara valoraciones y contacta profesionales verificados sin registrarte.">
    <meta property="og:image" content="{{ asset('img/hero-bg.webp') }}">
    <meta property="og:url" content="{{ route('home') }}">
@endsection

@section('content')
<main id="contenido-principal">
    <section class="home-hero" aria-labelledby="home-hero-title">
        <div class="home-shell home-hero__layout">
            <div class="home-hero__content">
                <p class="home-eyebrow">Profesionales cerca de ti</p>
                <h1 id="home-hero-title">Encuentra profesionales de confianza para tu hogar</h1>
                <p class="home-hero__lead">Compara valoraciones, contacta y confirma profesionales verificados en pocos clics.</p>

                <form class="home-search" action="{{ url('/result/services') }}" method="get" data-home-search>
                    <input type="hidden" name="mode" value="1">
                    <input type="hidden" name="latitude" value="" data-home-latitude>
                    <input type="hidden" name="longitude" value="" data-home-longitude>

                    <label class="home-search__field" for="home-location">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s7-5.6 7-12A7 7 0 1 0 5 9c0 6.4 7 12 7 12Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                        <span class="sr-only">Ubicación</span>
                        <input id="home-location" name="address" type="text" autocomplete="postal-code" placeholder="Busca por ubicación" data-home-location>
                    </label>

                    <label class="home-search__field home-search__field--service">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5-5L12 3.6 9.6 6 7.3 3.7a4 4 0 0 0 5 5L4 17l3 3 8.3-8.3a4 4 0 0 0 5-5L18 9l-2.4-2.4 2.3-2.3a4 4 0 0 0-3.2 2Z"/></svg>
                        <span class="sr-only">Servicio</span>
                        <select name="sti[]" data-home-service>
                            <option value="">¿Qué servicio necesitas?</option>
                            @foreach ($serviceTypes as $serviceType)
                                <option value="{{ $serviceType['id'] }}">{{ $serviceType['name'] }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button class="home-button home-button--primary home-search__submit" type="submit">Buscar</button>
                </form>

                <div class="home-search__meta">
                    <button type="button" data-home-use-location>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm9 3h-2.1A7 7 0 0 0 13 5.1V3h-2v2.1A7 7 0 0 0 5.1 11H3v2h2.1a7 7 0 0 0 5.9 5.9V21h2v-2.1a7 7 0 0 0 5.9-5.9H21v-2Z"/></svg>
                        Usar mi ubicación
                    </button>
                    <span role="status" aria-live="polite" data-home-location-status>Busca y contacta sin registrarte.</span>
                </div>

                <a class="home-provider-link" href="{{ route('provider.landing') }}">¿Eres profesional? Regístrate como proveedor <span aria-hidden="true">→</span></a>
            </div>

            <div class="home-hero__visual">
                <img src="{{ asset('img/hero-bg.webp') }}" alt="Sala de estar luminosa y cuidada" width="640" height="480" fetchpriority="high">
                <div class="home-hero__trust">
                    <strong>Profesionales verificados</strong>
                    <span>Contacta directamente y elige con confianza.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="home-section" id="servicios" aria-labelledby="services-title">
        <div class="home-shell">
            <div class="home-section__heading home-section__heading--centered">
                <p class="home-kicker">Soluciones para cada tarea</p>
                <h2 id="services-title">Servicios más buscados</h2>
                <p>Encuentra verdaderos expertos para las necesidades de tu hogar.</p>
            </div>

            <div class="home-services-grid">
                @foreach ($featuredServices as $featuredService)
                    <a
                        class="home-service-card"
                        href="{{ url('/result/services') }}{{ $featuredService['id'] ? '?mode=1&sti%5B%5D='.$featuredService['id'] : '' }}"
                    >
                        <span class="home-service-card__icon-wrap">
                            @include('page.partials.home.service-icon', ['icon' => $featuredService['icon']])
                        </span>
                        <h3>{{ $featuredService['name'] }}</h3>
                        <p>{{ $featuredService['description'] }}</p>
                    </a>
                @endforeach
            </div>

            <a class="home-text-link" href="{{ url('/result/services') }}">Explorar todos los servicios <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <section class="home-section home-how" id="como-funciona" aria-labelledby="how-title">
        <div class="home-shell">
            <div class="home-section__heading home-section__heading--centered">
                <p class="home-kicker">Simple, rápido y seguro</p>
                <h2 id="how-title">Así funciona Kconecta</h2>
                <p>Encuentra al profesional adecuado en tres pasos.</p>
            </div>

            <div class="home-how__steps">
                <article>
                    <span class="home-how__number">1</span>
                    <span class="home-how__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                    </span>
                    <h3>Busca el servicio</h3>
                    <p>Explora categorías y encuentra al profesional ideal para tu hogar.</p>
                </article>
                <article>
                    <span class="home-how__number">2</span>
                    <span class="home-how__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/></svg>
                    </span>
                    <h3>Compara y selecciona</h3>
                    <p>Revisa perfiles, especialidades y valoraciones para elegir con confianza.</p>
                </article>
                <article>
                    <span class="home-how__number">3</span>
                    <span class="home-how__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/><path d="M8 9h8M8 13h5"/></svg>
                    </span>
                    <h3>Contacta y elige</h3>
                    <p>Conversa con el profesional y acuerda directamente los detalles.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="home-section home-reviews" id="nosotros" aria-labelledby="reviews-title">
        <div class="home-shell">
            <div class="home-section__heading home-section__heading--centered">
                <p class="home-kicker">Confianza compartida</p>
                <h2 id="reviews-title">Lo que dicen nuestros usuarios</h2>
                <p>Experiencias que reflejan cómo queremos que se viva cada servicio.</p>
            </div>

            {{-- Contenido editorial de referencia pendiente de validación por negocio antes de producción. --}}
            <div class="home-review-grid">
                <article class="home-review-card" data-editorial-review>
                    <div class="home-review-card__person">
                        <img src="{{ asset('img/img-review-1.webp') }}" alt="Retrato de Óscar Zamora" width="72" height="72" loading="lazy">
                        <div><strong>Óscar Zamora</strong><span>Les Corts</span></div>
                    </div>
                    <div class="home-review-card__stars" aria-label="Cinco estrellas">★★★★★</div>
                    <blockquote>“Excelente servicio, llegó muy puntual y dejó todo en perfecto estado.”</blockquote>
                </article>
                <article class="home-review-card" data-editorial-review>
                    <div class="home-review-card__person">
                        <img src="{{ asset('img/img-review-2.webp') }}" alt="Retrato de José María" width="72" height="72" loading="lazy">
                        <div><strong>José María M.</strong><span>Sant Miquel</span></div>
                    </div>
                    <div class="home-review-card__stars" aria-label="Cinco estrellas">★★★★★</div>
                    <blockquote>“Encontré al técnico ideal, solucionó mi problema y transmitió mucha confianza.”</blockquote>
                </article>
                <article class="home-review-card" data-editorial-review>
                    <div class="home-review-card__person">
                        <img src="{{ asset('img/img-review-3.webp') }}" alt="Retrato de María Dolores" width="72" height="72" loading="lazy">
                        <div><strong>María Dolores R.</strong><span>Sants</span></div>
                    </div>
                    <div class="home-review-card__stars" aria-label="Cinco estrellas">★★★★★</div>
                    <blockquote>“Publiqué mi necesidad y pude elegir entre varias opciones en pocas horas.”</blockquote>
                </article>
            </div>
        </div>
    </section>

    <section class="home-section home-advice" id="consejos" aria-labelledby="advice-title">
        <div class="home-shell">
            <div class="home-section__heading home-section__heading--centered">
                <p class="home-kicker">Ideas que ayudan</p>
                <h2 id="advice-title">Consejos para tu hogar</h2>
                <p>Guías y recomendaciones para cuidar, mantener y mejorar tu casa.</p>
            </div>

            <div class="home-article-grid">
                @forelse ($homeArticles as $article)
                    <article class="home-article-card">
                        <a href="{{ url('/blogs/'.$article['slug']) }}" tabindex="-1" aria-hidden="true">
                            <img
                                src="{{ $article['image'] !== '' ? asset($article['image']) : asset('img/image-icon-1280x960.png') }}"
                                alt=""
                                width="560"
                                height="320"
                                loading="lazy"
                            >
                        </a>
                        <div class="home-article-card__body">
                            <h3><a href="{{ url('/blogs/'.$article['slug']) }}">{{ $article['title'] }}</a></h3>
                            <p>{{ $article['summary'] }}</p>
                            <a class="home-article-card__link" href="{{ url('/blogs/'.$article['slug']) }}">Leer más <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                @empty
                    <div class="home-article-empty">
                        <h3>Estamos preparando nuevos consejos</h3>
                        <p>Muy pronto encontrarás aquí ideas prácticas para tu hogar.</p>
                    </div>
                @endforelse
            </div>

            <a class="home-button home-button--outline home-advice__all" href="{{ url('/blogs') }}">Ver todos los consejos</a>
        </div>
    </section>

    <section class="home-provider-cta" id="profesionales" aria-labelledby="providers-title">
        <div class="home-shell home-provider-cta__layout">
            <div class="home-provider-cta__copy">
                <p class="home-kicker">Para profesionales</p>
                <h2 id="providers-title">Haz crecer tu negocio con Kconecta</h2>
                <p>Únete a nuestra comunidad y conecta con personas que ya están buscando tus servicios.</p>
                <ul>
                    <li><span aria-hidden="true">✓</span> Más visibilidad y clientes potenciales.</li>
                    <li><span aria-hidden="true">✓</span> Sin comisiones por contacto.</li>
                    <li><span aria-hidden="true">✓</span> Gestiona tu perfil, especialidades y trabajos.</li>
                </ul>
            </div>

            <div class="home-provider-signup">
                <span class="home-provider-signup__badge">Registro gratuito</span>
                <h3>Regístrate como proveedor</h3>
                <p>Crea tu cuenta sin introducir una dirección. Después de verificar tu correo podrás completar tu perfil y ubicación.</p>
                <div class="home-provider-signup__steps">
                    <span><strong>1</strong> Crea tu cuenta</span>
                    <span><strong>2</strong> Verifica tu e-mail</span>
                    <span><strong>3</strong> Activa tu perfil</span>
                </div>
                <a class="home-button home-button--primary" href="{{ route('provider.landing') }}">Regístrate y recibe clientes</a>
                <small>Al continuar aceptas nuestros <a href="{{ route('legal.terms') }}">términos y condiciones</a>.</small>
            </div>
        </div>
    </section>
</main>
@endsection

@section('scripts')
    @if (filled(config('services.google.maps_key')))
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&libraries=places&loading=async"
            async
            referrerpolicy="strict-origin-when-cross-origin"
        ></script>
    @endif
@endsection
