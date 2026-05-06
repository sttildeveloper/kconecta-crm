@extends('layouts.backoffice')

@section('title', 'Kconecta - Dashboard')

@section('heading')
    Hola, {{ $user?->first_name ?: ($user?->user_name ?: 'Usuario') }}
@endsection

@section('subheading')
    {{ $userLevelName }} &middot; Panel de control
@endsection

@section('header_actions')
    @if ($isFinalClient ?? false)
        <a class="action-icon action-icon--accent" href="{{ url('/result/services') }}" data-tooltip="Explorar servicios" aria-label="Explorar servicios">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 4a7 7 0 1 0 4.9 12l4.1 4.1"/>
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 11h6M11 8v6"/>
            </svg>
        </a>
    @endif
    @if ($canManageProperties ?? false)
        <a class="action-icon action-icon--accent" href="{{ url('/post/index') }}" data-tooltip="Agregar propiedad" aria-label="Agregar propiedad">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 11.5l9-7 9 7"/>
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 10.5V20a1 1 0 0 0 1 1h5v-6h2v6h5a1 1 0 0 0 1-1v-9.5"/>
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 4v4M16 6h4"/>
            </svg>
        </a>
    @endif
    @if ($canManageServices ?? false)
        <a class="action-icon action-icon--accent" href="{{ url('/post/services') }}" data-tooltip="Agregar proveedor de servicios" aria-label="Agregar proveedor de servicios">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20c0-2.8 2.2-5 5-5s5 2.2 5 5"/>
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 8v4M16 10h4"/>
            </svg>
        </a>
    @endif
@endsection

@section('styles')
    @if ($isFinalClient ?? false)
        <style>
            .client-rating-card {
                border-radius: 18px;
                background: #ffffff;
                border: 1px solid #dce7ea;
                box-shadow: 0 10px 18px rgba(15, 23, 42, 0.06);
                padding: 0;
                overflow: hidden;
            }
            .client-rating-card h3 {
                margin: 0;
                font-size: 1.7rem;
                font-weight: 800;
                color: #0f172a;
            }
            .client-rating-header {
                padding: 18px 20px 12px;
                background: #eef8f7;
                border-bottom: 1px solid #dce7ea;
            }
            .client-rating-header p {
                margin: 6px 0 0;
                color: #5b6780;
                font-size: .95rem;
            }
            .client-rating-form-body {
                padding: 16px 20px 20px;
            }
            .client-rating-field {
                display: grid;
                gap: 8px;
                margin-bottom: 16px;
            }
            .client-rating-field span {
                font-size: 0.95rem;
                font-weight: 700;
                color: #64748b;
            }
            .client-rating-input {
                border: 0;
                border-radius: 14px;
                background: #eef2f7;
                padding: 14px 16px 14px 44px;
                font-size: 1rem;
                color: #334155;
                outline: none;
                width: 100%;
            }
            .client-rating-input::placeholder {
                color: #9aa7b8;
            }
            .client-rating-input-wrap {
                position: relative;
            }
            .client-rating-ticket-icon {
                position: absolute;
                inset-block: 0;
                left: 14px;
                display: flex;
                align-items: center;
                color: #94a3b8;
                pointer-events: none;
            }
            .client-rating-stars {
                display: flex;
                gap: 6px;
                margin: 6px 0 14px;
            }
            .client-rating-star {
                border: 0;
                background: transparent;
                padding: 0;
                line-height: 1;
                font-size: 2rem;
                color: #dbe2ea;
                cursor: pointer;
                transition: transform .15s ease, color .15s ease;
            }
            .client-rating-star.is-active {
                color: #f2c94c;
            }
            .client-rating-star:hover {
                transform: translateY(-1px) scale(1.03);
            }
            .client-rating-save {
                width: 100%;
                border: 0;
                border-radius: 14px;
                padding: 13px 16px;
                font-size: 1.05rem;
                font-weight: 800;
                color: #ffffff;
                background: #1fb8ad;
                box-shadow: 0 8px 16px rgba(31, 184, 173, 0.3);
                cursor: pointer;
            }
            .client-rating-save:disabled {
                opacity: .75;
                cursor: wait;
            }
            .client-rating-feedback {
                margin-top: 12px;
                min-height: 22px;
                font-size: .93rem;
            }
            .final-client-shell {
                display: grid;
                gap: 16px;
            }
            .final-client-stats .stat-card {
                border: 1px solid #e3e8ef;
                box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
            }
            .final-client-main-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
                gap: 16px;
                align-items: start;
            }
            .client-activity-card {
                min-height: 420px;
            }
            .client-activity-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            .client-activity-head h3 {
                margin: 0;
            }
            .client-activity-link {
                font-size: .92rem;
                color: #0f9488;
                font-weight: 700;
                text-decoration: none;
            }
            @media (max-width: 1100px) {
                .final-client-main-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endif
@endsection

@section('content')
    @php
        $photoUrl = $user && $user->photo
            ? asset('img/photo_profile/' . $user->photo)
            : asset('img/default-avatar-profile-icon.webp');
    @endphp
    @if (! empty($alerts))
        <div class="alert-stack">
            @foreach ($alerts as $alert)
                <div class="alert-card">{{ $alert }}</div>
            @endforeach
        </div>
    @endif

    @if ($isFinalClient ?? false)
        <section class="final-client-shell">
            <div class="stats-grid final-client-stats">
                <div class="stat-card metric-card">
                    <div class="stat-label">Valoraciones realizadas</div>
                    <div class="stat-value">{{ number_format($finalClientStats['ratingsCount'] ?? 0) }}</div>
                    <div class="stat-sparkline stat-sparkline--soft">
                        <svg viewBox="0 0 120 40" aria-hidden="true" focusable="false">
                            <path class="sparkline-fill" d="M2 28L18 24L32 30L48 14L60 26L76 18L92 22L108 12L118 20L118 40L2 40Z"/>
                            <path class="sparkline-line" d="M2 28L18 24L32 30L48 14L60 26L76 18L92 22L108 12L118 20"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-card metric-card">
                    <div class="stat-label">Proveedores valorados</div>
                    <div class="stat-value">{{ number_format($finalClientStats['providersRatedCount'] ?? 0) }}</div>
                    <div class="stat-sparkline stat-sparkline--muted">
                        <svg viewBox="0 0 120 40" aria-hidden="true" focusable="false">
                            <path class="sparkline-fill" d="M2 14L16 20L30 16L44 22L58 12L72 18L86 10L100 16L118 8L118 40L2 40Z"/>
                            <path class="sparkline-line" d="M2 14L16 20L30 16L44 22L58 12L72 18L86 10L100 16L118 8"/>
                        </svg>
                    </div>
                </div>
                <div class="stat-card metric-card">
                    <div class="stat-label">Promedio de estrellas</div>
                    <div class="stat-value">{{ number_format((float) ($finalClientStats['averageStars'] ?? 0), 2) }} <span style="font-size:1.6rem;color:#0f9488;">★</span></div>
                    <div class="stat-sparkline stat-sparkline--accent">
                        <svg viewBox="0 0 120 40" aria-hidden="true" focusable="false">
                            <path class="sparkline-fill" d="M2 26L16 22L30 28L44 18L58 24L72 14L86 20L100 12L118 18L118 40L2 40Z"/>
                            <path class="sparkline-line" d="M2 26L16 22L30 28L44 18L58 24L72 14L86 20L100 12L118 18"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="final-client-main-grid">
                <div class="card client-activity-card">
                    <div class="client-activity-head">
                        <h3>Tu actividad de valoraciones</h3>
                        <a href="{{ url('/result/services') }}" class="client-activity-link">Ver todo</a>
                    </div>
                    <div class="list">
                        @forelse (($finalClientStats['recentRatings'] ?? []) as $rating)
                            <div class="list-item">
                                <strong>{{ $rating['provider'] }}</strong>
                                <span>{{ str_repeat('★', max(0, min(5, (int) $rating['stars']))) }}{{ str_repeat('☆', max(0, 5 - (int) $rating['stars'])) }}</span>
                                @if (!empty($rating['updated_at']))
                                    <span>Actualizado: {{ $rating['updated_at'] }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="list-item" style="text-align:center;padding:44px 18px;">
                                <strong>Aun no has valorado servicios</strong>
                                <span>Cuando valores proveedores con tu codigo de trabajo, apareceran aqui.</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card client-rating-card">
                    <div class="client-rating-header">
                        <h3>Realizar valoracion</h3>
                        <p>Envia tu feedback sobre un servicio recibido</p>
                    </div>
                    <div class="client-rating-form-body">
                <input type="hidden" id="client-rating-csrf" value="{{ csrf_token() }}">
                <form id="client-rating-form" style="display:grid;gap:12px;">
                    <label class="client-rating-field">
                        <span>Codigo de trabajo</span>
                        <div class="client-rating-input-wrap">
                            <div class="client-rating-ticket-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 7.5A1.5 1.5 0 0 1 6.5 6H17.5A1.5 1.5 0 0 1 19 7.5V10a2 2 0 1 0 0 4v2.5a1.5 1.5 0 0 1-1.5 1.5H6.5A1.5 1.5 0 0 1 5 16.5V14a2 2 0 1 0 0-4V7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 8V16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-dasharray="2.5 2.5"/>
                                </svg>
                            </div>
                            <input id="client-rating-work-code" type="text" class="client-rating-input" maxlength="64" placeholder="Ej: WK-XXXXXXX" required>
                        </div>
                    </label>
                    <label class="client-rating-field">
                        <span>Calidad del servicio</span>
                        <input id="client-rating-stars" type="hidden" value="">
                        <div class="client-rating-stars" id="client-rating-stars-ui" aria-label="Seleccion de estrellas">
                            <button type="button" class="client-rating-star" data-star="1" aria-label="1 estrella">★</button>
                            <button type="button" class="client-rating-star" data-star="2" aria-label="2 estrellas">★</button>
                            <button type="button" class="client-rating-star" data-star="3" aria-label="3 estrellas">★</button>
                            <button type="button" class="client-rating-star" data-star="4" aria-label="4 estrellas">★</button>
                            <button type="button" class="client-rating-star" data-star="5" aria-label="5 estrellas">★</button>
                        </div>
                    </label>
                    <div>
                        <button id="client-rating-submit" type="submit" class="client-rating-save">Guardar</button>
                    </div>
                    <p id="client-rating-feedback" class="client-rating-feedback" style="margin:0;color:#5b6780;"></p>
                </form>
                    </div>
                </div>
            </div>
        </section>
    @else
    <section class="stats-grid">
        <div class="stat-card metric-card">
            <div class="stat-label">Clicks en alguna propiedad</div>
            <div class="stat-value">{{ number_format($viewsCount) }}</div>
            <div class="stat-sparkline stat-sparkline--soft">
                <svg viewBox="0 0 120 40" aria-hidden="true" focusable="false">
                    <path class="sparkline-fill" d="M2 28L18 24L32 30L48 14L60 26L76 18L92 22L108 12L118 20L118 40L2 40Z"/>
                    <path class="sparkline-line" d="M2 28L18 24L32 30L48 14L60 26L76 18L92 22L108 12L118 20"/>
                </svg>
            </div>
        </div>
        <div class="stat-card metric-card">
            <div class="stat-label">Usuarios que revisaron propiedades</div>
            <div class="stat-value">{{ number_format($uniqueViewersCount) }}</div>
            <div class="stat-sparkline stat-sparkline--muted">
                <svg viewBox="0 0 120 40" aria-hidden="true" focusable="false">
                    <path class="sparkline-fill" d="M2 14L16 20L30 16L44 22L58 12L72 18L86 10L100 16L118 8L118 40L2 40Z"/>
                    <path class="sparkline-line" d="M2 14L16 20L30 16L44 22L58 12L72 18L86 10L100 16L118 8"/>
                </svg>
            </div>
        </div>
        <div class="stat-card metric-card">
            <div class="stat-label">Clicks en contacto</div>
            <div class="stat-value">{{ number_format($contactClicks) }}</div>
            <div class="stat-sparkline stat-sparkline--accent">
                <svg viewBox="0 0 120 40" aria-hidden="true" focusable="false">
                    <path class="sparkline-fill" d="M2 26L16 22L30 28L44 18L58 24L72 14L86 20L100 12L118 18L118 40L2 40Z"/>
                    <path class="sparkline-line" d="M2 26L16 22L30 28L44 18L58 24L72 14L86 20L100 12L118 18"/>
                </svg>
            </div>
        </div>
    </section>

    <section class="insights-grid">
        <div class="insight-stack">
            <div class="card welcome-card">
                <div class="welcome-avatar">
                    <img src="{{ $photoUrl }}" alt="Usuario">
                </div>
                <div class="welcome-info">
                    <span class="welcome-label">Bienvenido</span>
                    <h3>{{ $user?->first_name ?: ($user?->user_name ?: 'Usuario') }}</h3>
                    <p>{{ $user?->email ?? '' }}</p>
                </div>
            </div>
            @if ($isAdmin)
                <div class="card user-metrics-card">
                    <div class="user-metrics-header">
                        <h3>Usuarios registrados</h3>
                        <span>Por tipo</span>
                    </div>
                    <div class="user-metrics-list">
                        @foreach ($userTypeMetrics as $metric)
                            <div class="user-metrics-item">
                                <div class="metric-label">
                                    <span class="metric-dot" style="background: {{ $metric['color'] }}"></span>
                                    <span>{{ $metric['label'] }}</span>
                                </div>
                                <div class="metric-count">{{ number_format($metric['count']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="card donut-card">
            <div class="donut-header">
                <h3>Tipo de inmueble visitado</h3>
                <span>Distribucion por visitas</span>
            </div>
            <div class="donut-body">
                <div class="donut-chart" style="--donut-fill: {{ $propertyTypeGradient }};"></div>
                <div class="donut-legend">
                    @forelse ($propertyTypeStats as $stat)
                        <div class="legend-item">
                            <div class="legend-label">
                                <span class="legend-dot" style="background: {{ $stat['color'] }}"></span>
                                <span>{{ $stat['label'] }}</span>
                            </div>
                            <strong>{{ number_format($stat['value']) }}</strong>
                        </div>
                    @empty
                        <div class="legend-empty">Sin datos de visitas</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="content-grid">
        @if ($canManageProperties ?? false)
            <div class="card">
                <h3>&Uacute;ltimos anuncios de propiedades</h3>
                <div class="list">
                    @forelse ($recentProperties as $property)
                        <div class="list-item">
                            <strong>{{ $property['title'] }}</strong>
                            <span>{{ $property['type'] }} &middot; {{ $property['category'] }}</span>
                            <span>
                                {{ $property['address'] }}{{ $property['city'] ? ', ' . $property['city'] : '' }}
                            </span>
                            <span class="pill">
                                {{ $property['price'] ? number_format($property['price']) . ' ' . html_entity_decode('&euro;', ENT_QUOTES, 'UTF-8') : 'Sin precio' }}
                            </span>
                        </div>
                    @empty
                        <div class="list-item">
                            <strong>Sin anuncios recientes</strong>
                            <span>Publica tu primer anuncio desde el panel.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        <div class="card">
            <h3>Actividad</h3>
            <div class="kpi-grid">
                <div class="kpi-item">
                    <span>Vistas en detalle</span>
                    <strong>{{ number_format($viewsCount) }}</strong>
                </div>
                <div class="kpi-item">
                    <span>Vistas en b&uacute;squeda</span>
                    <strong>{{ number_format($searchViewsCount) }}</strong>
                </div>
            </div>
            <h3 style="margin-top:1.4rem;">Acciones r&aacute;pidas</h3>
            <div class="quick-actions">
                @if ($canManageProperties ?? false)
                    <a href="{{ url('/post/index') }}">
                        <span>Crear anuncio</span>
                        <span>&rsaquo;</span>
                    </a>
                    <a href="{{ url('/post/my_posts') }}">
                        <span>Gestionar propiedades</span>
                        <span>&rsaquo;</span>
                    </a>
                @endif
                @if ($canManageServices ?? false)
                    <a href="{{ url('/post/services') }}">
                        <span>Gestionar proveedores</span>
                        <span>&rsaquo;</span>
                    </a>
                @endif
            </div>
        </div>

        @if ($canManageServices ?? false)
            <div class="card">
                <h3>&Uacute;ltimos proveedores de servicios</h3>
                <div class="list">
                    @forelse ($recentServices as $service)
                        <div class="list-item">
                            <strong>{{ $service['name'] }}</strong>
                            <span>{{ $service['address'] }}{{ $service['city'] ? ', ' . $service['city'] : '' }}</span>
                            @if ($service['phone'])
                                <span>{{ $service['phone'] }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="list-item">
                            <strong>Sin proveedores recientes</strong>
                            <span>Agrega tu primer proveedor de servicio desde el panel.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </section>
    @endif
@endsection

@section('scripts')
    @if ($isFinalClient ?? false)
        <script>
            (() => {
                const form = document.getElementById('client-rating-form');
                if (!form) return;

                const csrf = document.getElementById('client-rating-csrf');
                const code = document.getElementById('client-rating-work-code');
                const stars = document.getElementById('client-rating-stars');
                const starsUi = document.getElementById('client-rating-stars-ui');
                const submit = document.getElementById('client-rating-submit');
                const feedback = document.getElementById('client-rating-feedback');
                const starButtons = starsUi ? Array.from(starsUi.querySelectorAll('[data-star]')) : [];

                const setFeedback = (text, isError = false) => {
                    if (!feedback) return;
                    feedback.textContent = text;
                    feedback.style.color = isError ? '#b91c1c' : '#15803d';
                };

                const paintStars = (value) => {
                    const selected = Number(value || 0);
                    starButtons.forEach((button) => {
                        const level = Number(button.dataset.star || 0);
                        button.classList.toggle('is-active', level <= selected);
                    });
                };

                starButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const value = Number(button.dataset.star || 0);
                        if (stars) {
                            stars.value = String(value);
                        }
                        paintStars(value);
                    });
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const workCode = (code?.value || '').trim();
                    const starsValue = Number(stars?.value || 0);
                    if (!workCode || !starsValue) {
                        setFeedback('Completa codigo y estrellas.', true);
                        return;
                    }

                    submit?.setAttribute('disabled', 'disabled');
                    setFeedback('Guardando...');

                    try {
                        const response = await fetch('/service-ratings/by-code', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf?.value || '',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                work_code: workCode,
                                stars: starsValue,
                            }),
                        });

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            setFeedback(payload?.message || 'No se pudo guardar la valoracion.', true);
                            return;
                        }

                        setFeedback('Valoracion guardada correctamente.');
                        form.reset();
                        paintStars(0);
                    } catch (error) {
                        setFeedback('Error de conexion al guardar la valoracion.', true);
                    } finally {
                        submit?.removeAttribute('disabled');
                    }
                });
            })();
        </script>
    @endif
@endsection
