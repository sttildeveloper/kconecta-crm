@extends('layouts.backoffice')

@section('title', 'Kconecta - Editar ficha del proveedor')

@section('heading')
    Editar proveedor de servicio
@endsection

@section('subheading')
    Actualiza la informacion publica del proveedor
@endsection

@section('header_actions')
    <a class="secondary" href="{{ url('/post/services') }}">Ver proveedor</a>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/app/user_update.css') }}">
    <style>
        .provider-edit-shell {
            display: grid;
            gap: 1.35rem;
        }

        .provider-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.2rem 0;
        }

        .provider-topbar h2 {
            margin: 0;
            font-size: 1.15rem;
            color: var(--dash-text);
        }

        .provider-topbar p {
            margin: 0.2rem 0 0;
            color: var(--dash-muted);
            font-size: 0.9rem;
        }

        .provider-edit-form {
            display: grid;
            gap: 1.25rem;
        }

        .provider-edit-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 1rem;
            padding: 1.25rem 1.35rem;
            box-shadow: 0 12px 26px rgba(12, 21, 43, 0.08);
        }

        .provider-edit-card h3 {
            margin: 0 0 0.9rem;
            font-size: 1rem;
            color: var(--dash-text);
        }

        .provider-summary__logo {
            width: 92px;
            height: 92px;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.1);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .provider-summary__logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .provider-summary__meta {
            display: grid;
            gap: 0.2rem;
        }

        .provider-summary__meta strong {
            color: var(--dash-text);
            font-size: 1rem;
        }

        .provider-summary__meta span {
            color: var(--dash-muted);
            font-size: 0.9rem;
        }

        .provider-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .provider-form-field {
            display: grid;
            gap: 0.4rem;
            font-size: 0.88rem;
            color: var(--dash-muted);
        }

        .provider-form-field--full {
            grid-column: 1 / -1;
        }

        .provider-form-field label,
        .provider-form-field > span {
            font-weight: 700;
            color: var(--dash-text);
        }

        .provider-form-field input,
        .provider-form-field textarea {
            border: 1px solid var(--dash-border);
            border-radius: 0.8rem;
            padding: 0.72rem 0.88rem;
            background: #fff;
            color: var(--dash-text);
            font-size: 0.95rem;
        }

        .provider-form-field textarea {
            min-height: 150px;
            resize: vertical;
        }

        .provider-form-field small {
            color: var(--dash-muted);
            font-size: 0.78rem;
        }

        .provider-service-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem 1rem;
        }

        .provider-service-option {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            padding: 0.15rem 0;
            color: var(--dash-text);
            font-size: 0.93rem;
        }

        .provider-service-option input {
            margin-top: 0.2rem;
        }

        .provider-media-stack {
            display: grid;
            gap: 1rem;
        }

        .provider-media-panel {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 0.95rem;
            background: #fcfeff;
            padding: 1rem;
        }

        .provider-media-panel h4 {
            margin: 0 0 0.8rem;
            color: var(--dash-text);
            font-size: 0.95rem;
        }

        .provider-cover-preview {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 0.9rem;
            overflow: hidden;
            background: #f8fafc;
        }

        .provider-cover-preview img {
            display: block;
            width: 100%;
            max-height: 260px;
            object-fit: cover;
        }

        .provider-gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.95rem;
        }

        .provider-gallery-item {
            display: grid;
            gap: 0.45rem;
        }

        .provider-gallery-item img {
            width: 100%;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            border-radius: 0.8rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .provider-gallery-delete {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.88rem;
            color: var(--dash-muted);
        }

        .provider-video-preview video {
            width: 100%;
            border-radius: 0.9rem;
            background: #111827;
        }

        .provider-empty {
            margin: 0;
            color: var(--dash-muted);
            font-size: 0.9rem;
        }

        .provider-file-field {
            display: grid;
            gap: 0.45rem;
            margin-top: 0.9rem;
        }

        .provider-submit-row {
            display: flex;
            justify-content: flex-start;
        }

        .provider-submit-row button {
            border: none;
            background: var(--dash-accent);
            color: #fff;
            padding: 0.8rem 1.65rem;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(99, 196, 202, 0.3);
        }

        .provider-submit-row button:disabled {
            opacity: 0.75;
            cursor: wait;
        }

        @media (max-width: 1100px) {
            .provider-service-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .provider-form-grid,
            .provider-service-grid,
            .provider-gallery-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $persistedTypeIds = collect($serviceTypes)
            ->pluck('service_type_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $selectedTypeIds = collect(old('service_type', $persistedTypeIds))
            ->map(fn ($value) => (int) $value)
            ->all();

        $coverUrl = ! empty($coverImage[0]['url'])
            ? asset('img/uploads/' . $coverImage[0]['url'])
            : asset('img/image-icon-1280x960.png');
    @endphp

    <div class="provider-edit-shell">
        <div class="provider-topbar">
            <div>
                <h2>Actualizar servicio</h2>
                <p>Edita los datos visibles, los tipos de servicio y la multimedia del proveedor.</p>
            </div>
        </div>

        <form id="provider-profile-form" class="provider-edit-form" action="{{ url('/post/services/update/save') }}" method="post" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="service_id" value="{{ $service[0]['id'] }}">

            <section class="provider-edit-card">
                <h3>Datos del anunciante</h3>
                <div class="provider-form-grid">
                    <div class="provider-form-field">
                        <span>Titulo publico *</span>
                        <input type="text" name="title" value="{{ old('title', $providerProfileTitle) }}" required>
                    </div>
                    <div class="provider-form-field">
                        <span>Sitio web</span>
                        <input type="url" name="page_url" value="{{ old('page_url', $provider->provider_page_url ?? $service[0]['page_url'] ?? '') }}" placeholder="https://">
                    </div>
                    <div class="provider-form-field provider-form-field--full">
                        <span>Descripcion publica *</span>
                        <textarea name="description" rows="6" required>{{ old('description', $provider->provider_description ?? $service[0]['description'] ?? '') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="provider-edit-card">
                <h3>Tipo de servicio</h3>
                <div class="provider-service-grid">
                    @foreach ($serviceType as $type)
                        <label class="provider-service-option">
                            <input type="checkbox" name="service_type[]" value="{{ $type['id'] }}" {{ in_array((int) $type['id'], $selectedTypeIds, true) ? 'checked' : '' }}>
                            <span>{{ $type['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="provider-edit-card">
                <h3>Fotos y videos</h3>
                <div class="provider-media-stack">
                    <div class="provider-media-panel">
                        <h4>Portada actual</h4>
                        <div class="provider-cover-preview">
                            <img id="cover-image-preview" src="{{ $coverUrl }}" alt="Portada del proveedor">
                        </div>
                        <div class="provider-file-field">
                            <label class="provider-form-field">
                                <span>Subir imagen de portada</span>
                                <input id="cover-image-input" type="file" name="cover_image" accept="image/png,image/jpeg,image/jpg,image/webp">
                            </label>
                        </div>
                    </div>

                    <div class="provider-media-panel">
                        <h4>Galeria actual</h4>
                        @if (! empty($moreImages))
                            <div class="provider-gallery-grid">
                                @foreach ($moreImages as $image)
                                    <div class="provider-gallery-item">
                                        <img src="{{ asset('img/uploads/' . $image['url']) }}" alt="Imagen del proveedor">
                                        <label class="provider-gallery-delete">
                                            <input type="checkbox" name="delete_more_images[]" value="{{ $image['id'] }}">
                                            <span>Eliminar</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="provider-empty">No hay imagenes adicionales.</p>
                        @endif

                        <div class="provider-file-field">
                            <label class="provider-form-field">
                                <span>Agregar imagenes</span>
                                <input type="file" name="more_images[]" multiple accept="image/png,image/jpeg,image/jpg,image/webp">
                            </label>
                        </div>
                    </div>

                    <div class="provider-media-panel">
                        <h4>Video actual</h4>
                        <div class="provider-video-preview">
                            @if (! empty($video[0]['url']))
                                <video src="{{ asset('video/uploads/' . $video[0]['url']) }}" controls></video>
                            @else
                                <p class="provider-empty">No hay video cargado.</p>
                            @endif
                        </div>
                        <div class="provider-file-field">
                            <label class="provider-form-field">
                                <span>Subir video de presentacion</span>
                                <input type="file" name="video" accept=".mp4,.mov,.avi,.mpeg,video/mp4,video/quicktime,video/x-msvideo,video/mpeg">
                                <small>Se mantiene el flujo actual de validacion y guardado del video.</small>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <div class="provider-submit-row">
                <button type="submit" id="provider-profile-submit-btn">Guardar y publicar</button>
            </div>

            <input type="hidden" name="availability" value="{{ old('availability', $provider->provider_availability ?? $service[0]['availability'] ?? '24/7') }}">
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const form = document.getElementById('provider-profile-form');
            const submitBtn = document.getElementById('provider-profile-submit-btn');
            const coverInput = document.getElementById('cover-image-input');
            const coverPreview = document.getElementById('cover-image-preview');

            coverInput?.addEventListener('change', () => {
                const file = coverInput.files && coverInput.files[0];
                if (file && coverPreview) {
                    coverPreview.src = URL.createObjectURL(file);
                }
            });

            form?.addEventListener('submit', () => {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Guardando...';
                }
            });
        })();
    </script>
@endsection
