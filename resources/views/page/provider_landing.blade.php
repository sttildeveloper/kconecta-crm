@extends('layouts.page')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/page/provider_landing.css') }}">
    <meta name="description" content="Convierte tu oficio en nuevos clientes con Kconecta. Registra tu perfil como proveedor y empieza a recibir visibilidad en Barcelona.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Quiero ser proveedor | Kconecta">
    <meta property="og:description" content="Publica tu perfil profesional, gana visibilidad y conecta con clientes reales desde Kconecta.">
    <meta property="og:image" content="{{ asset('img/img-hero-landing-quiero.webp') }}">
    <meta property="og:url" content="{{ route('provider.landing') }}">
@endsection

@section('nav_option')
    <a href="{{ url('/blogs') }}" aria-label="Novedades">
        <span>Novedades</span>
    </a>
@endsection

@section('content')
    <main class="provider-landing">
        <section class="provider-hero">
            <div class="provider-hero__content">
                <span class="provider-pill">Para proveedores de servicios en Barcelona</span>
                <h1>TU TIENES EL TALENTO. NOSOTROS TE LLEVAMOS CLIENTES.</h1>
                <p>Une a Konecta y recibe solicitudes de clientes reales en Barcelona cada dia.</p>

                <div class="provider-hero__metrics">
                    <article>
                        <strong>Clientes reales</strong>
                        <span>que buscan tus servicios cada dia.</span>
                    </article>
                    <article>
                        <strong>Sin cuotas</strong>
                        <span>ni permanencias.</span>
                    </article>
                    <article>
                        <strong>Tu eliges</strong>
                        <span>a que clientes responder.</span>
                    </article>
                </div>

                <div class="provider-hero__actions">
                    <a href="{{ url('/register') }}" class="provider-btn provider-btn--primary">Registrate gratis</a>
                </div>

                <div class="provider-hero__trust">
                    <span>Empieza hoy. Sin complicaciones.</span>
                </div>
            </div>

            <div class="provider-hero__scene">
                <div class="provider-hero__city"></div>
                <div class="provider-hero__image-card">
                    <img src="{{ asset('img/img-hero-landing-quiero.webp') }}" alt="Proveedor Kconecta listo para conseguir clientes">
                </div>

                <article class="provider-quote-card">
                    <div class="provider-quote-card__mark">&ldquo;</div>
                    <p>Desde que estoy en Konecta, tengo mas visibilidad y mas clientes.</p>
                    <div class="provider-quote-card__rating">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <strong>Jordi, electricista</strong>
                    <span>Proveedor Konecta</span>
                </article>
            </div>

            <aside class="provider-signup-card" id="registro">
                <h2>REGISTRATE COMO PROVEEDOR</h2>
                <p>Crea tu cuenta en menos de 2 minutos.</p>

                @if ($errors->any())
                    <div class="provider-signup-card__alert" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="provider-signup-card__form">
                    @csrf
                    <input type="hidden" name="user_level_id" value="{{ \App\Models\User::LEVEL_SERVICE_PROVIDER }}">
                    <input type="hidden" name="document_type" value="">
                    <input type="hidden" name="document_number" value="">
                    <input type="hidden" name="registration_form_started_at" value="{{ $registrationFormStartedAt ?? '' }}">
                    <div class="provider-signup-card__honeypot" aria-hidden="true">
                        <label for="provider-website">Sitio web</label>
                        <input type="text" id="provider-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="provider-signup-card__row provider-signup-card__row--single">
                        <label for="provider-company_name">Razon social (opcional)</label>
                        <input id="provider-company_name" name="company_name" type="text" value="{{ old('company_name') }}">
                    </div>

                    <div class="provider-signup-card__row">
                        <div>
                            <label for="provider-first_name">Nombre (opcional)</label>
                            <input id="provider-first_name" name="first_name" type="text" value="{{ old('first_name') }}">
                        </div>
                        <div>
                            <label for="provider-last_name">Apellido (opcional)</label>
                            <input id="provider-last_name" name="last_name" type="text" value="{{ old('last_name') }}">
                        </div>
                    </div>

                    <div class="provider-signup-card__row">
                        <div>
                            <label for="provider-phone">Movil (WhatsApp) (opcional)</label>
                            <input id="provider-phone" name="phone" type="text" value="{{ old('phone') }}">
                        </div>
                        <div>
                            <label for="provider-landline_phone">Telefono fijo (opcional)</label>
                            <input id="provider-landline_phone" name="landline_phone" type="text" value="{{ old('landline_phone') }}">
                        </div>
                    </div>

                    <div class="provider-signup-card__row provider-signup-card__row--single">
                        <label for="provider-email">E-mail *</label>
                        <input id="provider-email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="provider-signup-card__row">
                        <div>
                            <label for="provider-password">Contrasena *</label>
                            <input id="provider-password" name="password" type="password" required>
                        </div>
                        <div>
                            <label for="provider-password_confirmation">Repite la contrasena *</label>
                            <input id="provider-password_confirmation" name="password_confirmation" type="password" required>
                        </div>
                    </div>

                    <button type="submit" class="provider-btn provider-btn--primary provider-btn--block">REGISTRAR</button>

                    <small>
                        Al registrarte aceptas nuestros <a href="{{ route('legal.terms') }}">Terminos y Condiciones</a>
                        y nuestra <a href="{{ route('legal.privacy') }}">Politica de Privacidad</a>.
                    </small>
                </form>
            </aside>
            </div>
        </section>

        <section class="provider-strip">
            <article>
                <strong>Crece tu negocio en Barcelona</strong>
                <span>Llega a usuarios que ya estan buscando ayuda profesional.</span>
            </article>
            <article>
                <strong>Mas visibilidad local</strong>
                <span>Aparece en resultados, detalle publico y listados del portal.</span>
            </article>
            <article>
                <strong>Mas confianza</strong>
                <span>Ensena tus servicios, tu presencia y tus datos de contacto.</span>
            </article>
        </section>

        <section class="provider-section">
            <div class="provider-section__heading">
                <span>Una plataforma pensada para ti</span>
                <h2>Todo lo que necesita un proveedor para empezar fuerte</h2>
            </div>

            <div class="provider-grid provider-grid--four">
                <article class="provider-card">
                    <div class="provider-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 10h10M7 14h6m-8 7h14a2 2 0 0 0 2-2V7.5a2 2 0 0 0-.586-1.414l-2.5-2.5A2 2 0 0 0 16.5 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2"/></svg>
                    </div>
                    <h3>Perfil profesional</h3>
                    <p>Muestra tu marca, tu experiencia y tu propuesta de valor.</p>
                </article>
                <article class="provider-card">
                    <div class="provider-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 18h18M6 15V9m6 6V5m6 10v-3"/></svg>
                    </div>
                    <h3>Estadisticas visibles</h3>
                    <p>Sigue el interes que genera tu perfil y tus servicios publicados.</p>
                </article>
                <article class="provider-card">
                    <div class="provider-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 21s-6-4.35-6-10a6 6 0 0 1 12 0c0 5.65-6 10-6 10m0-7.5a2.5 2.5 0 1 0 0-5a2.5 2.5 0 0 0 0 5"/></svg>
                    </div>
                    <h3>Alcance local</h3>
                    <p>Ubicacion y contacto rapido para que el cliente te encuentre antes.</p>
                </article>
                <article class="provider-card">
                    <div class="provider-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m8 12l2.5 2.5L16 9m4 3a8 8 0 1 1-16 0a8 8 0 0 1 16 0"/></svg>
                    </div>
                    <h3>Imagen de confianza</h3>
                    <p>Presenta una ficha clara, ordenada y lista para convertir.</p>
                </article>
            </div>
        </section>

        <section class="provider-section provider-section--accent">
            <div class="provider-section__heading">
                <span>Ventajas reales</span>
                <h2>Mas oportunidades para captar clientes nuevos</h2>
            </div>

            <div class="provider-stats">
                <article>
                    <strong>+100K</strong>
                    <span>Visitas potenciales al ecosistema Kconecta</span>
                </article>
                <article>
                    <strong>+50K</strong>
                    <span>Busquedas con interes en servicios</span>
                </article>
                <article>
                    <strong>4.8</strong>
                    <span>Experiencia disenada para perfiles profesionales</span>
                </article>
            </div>
        </section>

        <section class="provider-section">
            <div class="provider-section__heading">
                <span>Asi de facil</span>
                <h2>Un recorrido simple para activar tu presencia</h2>
            </div>

            <div class="provider-grid provider-grid--steps">
                <article class="provider-card provider-card--step">
                    <span class="provider-step">01</span>
                    <h3>Registrate gratis</h3>
                    <p>Crea tu perfil profesional con acceso a tu panel de proveedor.</p>
                </article>
                <article class="provider-card provider-card--step">
                    <span class="provider-step">02</span>
                    <h3>Publica tus servicios</h3>
                    <p>Configura tu ficha con categorias, descripcion y datos de contacto.</p>
                </article>
                <article class="provider-card provider-card--step">
                    <span class="provider-step">03</span>
                    <h3>Conecta y convierte</h3>
                    <p>Recibe visibilidad y facilita que los clientes te contacten.</p>
                </article>
            </div>
        </section>

        <section class="provider-cta">
            <div>
                <strong>No pierdas mas oportunidades.</strong>
                <span>Empieza hoy a recibir clientes desde Kconecta.</span>
            </div>
            <a href="{{ url('/register') }}" class="provider-btn provider-btn--primary">Registrate gratis</a>
        </section>
    </main>
@endsection
