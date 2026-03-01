@extends('layouts.backoffice')

@section('title', $pageTitle ?? 'Kconecta - Mi perfil')

@section('heading')
    {{ $pageHeading ?? 'Mi perfil' }}
@endsection

@section('subheading')
    {{ $pageSubheading ?? 'Actualiza tus datos personales' }}
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/app/user_update.css') }}">
@endsection

@section('content')
    @php
        $photoUrl = $user->photo ? asset('img/photo_profile/' . $user->photo) : asset('img/default-avatar-profile-icon.webp');
        $addressValue = old('address', $address?->address ?? $user->address ?? '');
        $addressValidated = $addressValidated ?? false;
        $targetUserId = $targetUserId ?? null;
    @endphp

    @if (session('status'))
        <div class="profile-alert success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="profile-alert error">
            <strong>Revisa los datos:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="profile-wrapper">
        <div class="profile-card">
            <form id="profile-form" class="profile-form" method="POST" action="{{ url('/user/update/save') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="profile-grid">
                    <label class="profile-field">
                        <span>Nombre *</span>
                        <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                    </label>
                    <label class="profile-field">
                        <span>Apellidos</span>
                        <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}">
                    </label>
                    <label class="profile-field">
                        <span>E-mail *</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </label>
                    <label class="profile-field">
                        <span>Telefono</span>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                    </label>
                    <label class="profile-field">
                        <span>Telefono fijo</span>
                        <input type="text" name="landline_phone" value="{{ old('landline_phone', $user->landline_phone) }}">
                    </label>
                    <label class="profile-field">
                        <span>Documento</span>
                        <div class="profile-inline">
                            <select name="document_type">
                                <option value="">Seleccione</option>
                                @foreach ($documentTypes as $docType)
                                    <option value="{{ $docType }}" {{ old('document_type', $user->document_type) === $docType ? 'selected' : '' }}>
                                        {{ $docType }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="text" name="document_number" value="{{ old('document_number', $user->document_number) }}" placeholder="Numero de documento">
                        </div>
                    </label>
                    <label class="profile-field profile-field--full">
                        <span>Direccion *</span>
                        <input id="address-input" type="text" name="address" value="{{ $addressValue }}" placeholder="Escribe y selecciona una sugerencia" autocomplete="off" required>
                        <small>Selecciona una direccion sugerida por Google para validar la ubicacion.</small>
                        <div class="address-status {{ $addressValidated ? 'is-valid' : '' }}" id="address-status">
                            {{ $addressValidated ? 'Direccion validada' : 'Direccion pendiente' }}
                        </div>
                    </label>
                    <label class="profile-field">
                        <span>Nombre de usuario</span>
                        <input type="text" name="user_name" value="{{ old('user_name', $user->user_name) }}">
                    </label>
                    <label class="profile-field">
                        <span>Nueva contrase&ntilde;a</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="Dejar vacio para mantener">
                    </label>
                </div>

                <div class="profile-grid">
                    <label class="profile-field profile-field--full">
                        <span>Logo o foto (opcional)</span>
                        <input id="photo-input" type="file" name="photo" accept="image/*">
                        <small>Formatos JPG, PNG o WEBP. Maximo 2MB.</small>
                    </label>
                </div>

                @if (! empty($targetUserId))
                    <input type="hidden" name="target_user_id" value="{{ $targetUserId }}">
                @endif
                <input type="hidden" name="address_place_id" id="address-place-id" value="{{ old('address_place_id') }}">
                <input type="hidden" name="address_street_name" id="address-street-name" value="{{ old('address_street_name', $address?->street_name ?? '') }}">
                <input type="hidden" name="address_street_number" id="address-street-number" value="{{ old('address_street_number', $address?->street_number ?? '') }}">
                <input type="hidden" name="address_neighborhood" id="address-neighborhood" value="{{ old('address_neighborhood', $address?->neighborhood ?? '') }}">
                <input type="hidden" name="address_city" id="address-city" value="{{ old('address_city', $address?->city ?? '') }}">
                <input type="hidden" name="address_province" id="address-province" value="{{ old('address_province', $address?->province ?? '') }}">
                <input type="hidden" name="address_state" id="address-state" value="{{ old('address_state', $address?->state ?? '') }}">
                <input type="hidden" name="address_postal_code" id="address-postal-code" value="{{ old('address_postal_code', $address?->postal_code ?? '') }}">
                <input type="hidden" name="address_country" id="address-country" value="{{ old('address_country', $address?->country ?? '') }}">
                <input type="hidden" name="address_lat" id="address-lat" value="{{ old('address_lat', $address?->latitude ?? '') }}">
                <input type="hidden" name="address_lng" id="address-lng" value="{{ old('address_lng', $address?->longitude ?? '') }}">

                <div class="profile-actions">
                    <button type="submit">Guardar cambios</button>
                </div>
            </form>
        </div>

        <aside class="profile-side">
            <div class="profile-photo-card">
                <div class="photo-preview" id="photo-preview">
                    <img src="{{ $photoUrl }}" alt="Logo del usuario">
                </div>
                <div class="photo-meta">
                    <strong>{{ $user->user_name ?: $user->first_name }}</strong>
                    <span>{{ $user->email }}</span>
                </div>
            </div>
            <div class="profile-note">
                <h4>Direccion validada</h4>
                <p>Usa el autocompletado de Google para mejorar la visibilidad en el mapa.</p>
            </div>
        </aside>
    </div>
@endsection

@section('scripts')
    @if (! empty($mapsKey))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places"></script>
    @endif
    <script>
        (() => {
            const addressInput = document.getElementById('address-input');
            const placeIdInput = document.getElementById('address-place-id');
            const statusBadge = document.getElementById('address-status');
            const form = document.getElementById('profile-form');
            const initialAddress = addressInput ? addressInput.value.trim() : '';

            const updateStatus = (isValid, message) => {
                if (!statusBadge) {
                    return;
                }
                statusBadge.textContent = message;
                statusBadge.classList.toggle('is-valid', isValid);
            };

            const setValue = (id, value) => {
                const field = document.getElementById(id);
                if (field) {
                    field.value = value || '';
                }
            };

            const clearAddressFields = () => {
                setValue('address-place-id', '');
                setValue('address-street-name', '');
                setValue('address-street-number', '');
                setValue('address-neighborhood', '');
                setValue('address-city', '');
                setValue('address-province', '');
                setValue('address-state', '');
                setValue('address-postal-code', '');
                setValue('address-country', '');
                setValue('address-lat', '');
                setValue('address-lng', '');
            };

            if (addressInput) {
                addressInput.addEventListener('input', () => {
                    clearAddressFields();
                    updateStatus(false, 'Direccion pendiente');
                });
            }

            if (addressInput && window.google && google.maps && google.maps.places) {
                const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                    types: ['geocode'],
                    componentRestrictions: { country: 'ES' }
                });

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place || !place.geometry) {
                        updateStatus(false, 'Selecciona una direccion valida');
                        return;
                    }

                    const components = place.address_components || [];
                    const getComponent = (type) => {
                        const component = components.find((item) => item.types.includes(type));
                        return component ? component.long_name : '';
                    };

                    addressInput.value = place.formatted_address || addressInput.value;
                    placeIdInput.value = place.place_id || 'validated';

                    setValue('address-street-name', getComponent('route'));
                    setValue('address-street-number', getComponent('street_number'));
                    setValue('address-neighborhood', getComponent('sublocality') || getComponent('neighborhood'));
                    setValue('address-city', getComponent('locality'));
                    setValue('address-province', getComponent('administrative_area_level_2'));
                    setValue('address-state', getComponent('administrative_area_level_1'));
                    setValue('address-postal-code', getComponent('postal_code'));
                    setValue('address-country', getComponent('country'));
                    setValue('address-lat', place.geometry.location.lat());
                    setValue('address-lng', place.geometry.location.lng());

                    updateStatus(true, 'Direccion validada');
                });
            }

            if (form) {
                form.addEventListener('submit', (event) => {
                    const currentAddress = addressInput ? addressInput.value.trim() : '';
                    if (currentAddress && currentAddress !== initialAddress && !placeIdInput.value) {
                        event.preventDefault();
                        alert('Selecciona una direccion sugerida por Google.');
                    }
                });
            }

            const photoInput = document.getElementById('photo-input');
            const previewImg = document.querySelector('#photo-preview img');
            if (photoInput && previewImg) {
                photoInput.addEventListener('change', () => {
                    const file = photoInput.files && photoInput.files[0];
                    if (!file) {
                        return;
                    }
                    previewImg.src = URL.createObjectURL(file);
                });
            }
        })();
    </script>
@endsection
